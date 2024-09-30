<?php

/*
curl -X POST https://2upra.com/wp-json/mi-chat/v1/procesarMensaje \
-H "Content-Type: application/json" \
-d '{"emisor":"1","receptor":"44","mensaje":"Hola"}'

{
    "code": "internal_server_error",
    "message": "<p>Ha habido un error cr\u00edtico en esta web.<\/p><p><a href=\"https:\/\/wordpress.org\/documentation\/article\/faq-troubleshooting\/\">Aprende m\u00e1s sobre el diagn\u00f3stico de WordPress.<\/a><\/p>",
    "data": {
        "status": 500
    },
    "additional_errors": []
}

*/

add_action('rest_api_init', function () {
    register_rest_route('mi-chat/v1', '/procesarMensaje', array(
        'methods' => 'POST',
        'callback' => 'procesarMensaje',
        'permission_callback' => function () {
            return true; 
        }
    ));
});

function procesarMensaje($request) {
    //esto realmente parece que nunca se inicia
    chatLog($request, 'Iniciando procesarMensaje');
    //aqui necesito una medida de seguridad que verifique si el emisor es el mismo que el usuario actual, en caso de que no lo sea, no permite
    $params = $request->get_json_params();
    chatLog($request, 'Parámetros recibidos: ' . json_encode($params));
    
    $emisor = isset($params['emisor']) ? $params['emisor'] : null;
    $receptor = isset($params['receptor']) ? $params['receptor'] : null;
    $mensaje = isset($params['mensaje']) ? $params['mensaje'] : null;
    $adjunto = isset($params['adjunto']) ? $params['adjunto'] : null;
    $metadata = isset($params['metadata']) ? $params['metadata'] : null;

    if (!$emisor || !$receptor || !$mensaje) {
        chatLog($request, 'Error: Datos incompletos');
        return new WP_Error('datos_incompletos', 'Faltan datos requeridos', array('status' => 400));
    }
    
    chatLog($request, 'Intentando guardar mensaje');
    
    try {
        $resultado = guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata);
        
        if ($resultado) {
            chatLog($request, 'Mensaje guardado con éxito');
            return new WP_REST_Response(['success' => true], 200);
        } else {
            chatLog($request, 'Error: No se pudo guardar el mensaje');
            return new WP_Error('error_guardado', 'No se pudo guardar el mensaje', array('status' => 500));
        }
    } catch (Exception $e) {
        chatLog($request, 'Excepción al guardar mensaje: ' . $e->getMessage());
        return new WP_Error('error_interno', 'Se produjo un error interno', array('status' => 500));
    }
}


function guardarMensaje($emisor, $receptor, $mensaje, $adjunto = null, $metadata = null)
{
    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $tablaConversacion = $wpdb->prefix . 'conversacion';

    // Asegurarse de que los valores de emisor y receptor sean enteros
    $emisor = (int) $emisor;
    $receptor = (int) $receptor;

    // Iniciar la transacción
    $wpdb->query('START TRANSACTION');

    try {
        // Intentar obtener la conversación
        $query = $wpdb->prepare("
            SELECT id FROM $tablaConversacion
            WHERE tipo = 1
            AND JSON_CONTAINS(participantes, %s)
            AND JSON_CONTAINS(participantes, %s)
        ", json_encode($emisor), json_encode($receptor));

        $conversacionID = $wpdb->get_var($query);

        if (!$conversacionID) {
            // Guardar los participantes como enteros en formato JSON
            $participantes = json_encode([$emisor, $receptor], JSON_NUMERIC_CHECK);
            $wpdb->insert($tablaConversacion, [
                'tipo' => 1,
                'participantes' => $participantes,
                'fecha' => current_time('mysql')
            ]);
            $conversacionID = $wpdb->insert_id;
            chatLog("Nueva conversación creada con ID: $conversacionID");
        } else {
            chatLog("Conversación existente encontrada con ID: $conversacionID");
        }

        $resultado = $wpdb->insert($tablaMensajes, [
            'conversacion' => $conversacionID,
            'emisor' => $emisor,
            'mensaje' => $mensaje,
            'fecha' => current_time('mysql'),
            'adjunto' => isset($adjunto) ? json_encode($adjunto) : null,
            'metadata' => isset($metadata) ? json_encode($metadata) : null,
        ]);

        if ($resultado === false) {
            throw new Exception("Error al insertar el mensaje: " . $wpdb->last_error);
        }

        $mensajeID = $wpdb->insert_id;

        // Confirmar la transacción
        $wpdb->query('COMMIT');
        chatLog("Mensaje guardado con ID: $mensajeID en la conversación: $conversacionID");

        return $mensajeID;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log($e->getMessage());
        chatLog("Error al guardar el mensaje: " . $e->getMessage());
        return false;
    }
}



