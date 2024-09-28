<?php

add_action('rest_api_init', function () {
    register_rest_route('mi-chat/v1', '/procesarMensaje', array(
        'methods' => 'POST',
        'callback' => 'procesarMensaje',
        'permission_callback' => function () {
            return is_user_logged_in();
        }

    ));
});

function procesarMensaje($request)
{
    $emisor = get_current_user_id();
    $params = $request->get_json_params();
    chatLog($params);

    $receptor = $params['receptor'];
    $mensaje = $params['mensaje'];
    $adjunto = isset($params['adjunto']) ? $params['adjunto'] : null;
    $metadata = isset($params['metadata']) ? $params['metadata'] : null;

    if (!$emisor || !$receptor || !$mensaje) {
        return;
    }
    guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata);
}

function guardarMensaje($emisor, $receptor, $mensaje, $adjunto = null, $metadata = null)
{
    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $clave = $_ENV['GALLEKEY'];
    $iv = openssl_random_pseudo_bytes(16);
    $mensajeCifrado = cifrarMensaje($mensaje, $clave, $iv);

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
            $participantes = json_encode([$emisor, $receptor]);
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
            'mensaje' => $mensajeCifrado,
            'fecha' => current_time('mysql'),
            'adjunto' => isset($adjunto) ? json_encode($adjunto) : null,
            'metadata' => isset($metadata) ? json_encode($metadata) : null,
            'iv' => base64_encode($iv)
        ]);

        if ($resultado === false) {
            throw new Exception("Error al insertar el mensaje: " . $wpdb->last_error);
        }

        $mensajeID = $wpdb->insert_id;

        // Confirmar la transacción
        $wpdb->query('COMMIT');
        chatLog("Mensaje cifrado guardado con ID: $mensajeID en la conversación: $conversacionID");

        return $mensajeID;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log($e->getMessage());
        chatLog("Error al guardar el mensaje: " . $e->getMessage());
        return false;
    }
}


