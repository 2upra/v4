<?php


/*

Mensaje recibido de 89: {"emisor":"44","type":"auth","token":"810e8fb11c"}
Mensaje de autenticación recibido. Verificando token...
Iniciando verificación del token para el emisor: 44 en la conexión 89
Token recibido: 810e8fb11c
Enviando solicitud de verificación a WordPress con las siguientes opciones:
URL: https://2upra.com/wp-json/galle/v1/verificarToken
Contenido: {"token":"810e8fb11c"}
Error: No se pudo contactar con el servidor de autenticación de WordPress.

*/

add_action('rest_api_init', function () {
    register_rest_route('galle/v2', '/procesartmensaje', array(
        'methods' => 'POST',
        'callback' => 'procesarMensaje',
        'permission_callback' => function ($request) {
            $token = $request->get_header('X-WP-Nonce');
            return wp_verify_nonce($token, 'mi_chat_nonce');
        }
    ));
});

/*

curl -I https://2upra.com/wp-json/galle/v2/verificartoken
Status:
404 (Not Found)
Time:
428 ms
Size:
0.00 kb
*/

add_action('rest_api_init', function () {
    register_rest_route('galle/v2', '/verificartoken', array(
        'methods' => 'POST',
        'callback' => 'verificarToken',
        'permission_callback' => '__return_true'
    ));
});

add_action('wp_ajax_generarToken', 'generarToken');

function generarToken() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
    }
    $user_id = get_current_user_id();
    $token = wp_create_nonce('mi_chat_nonce');
    wp_send_json_success(['token' => $token]);
}

function verificarToken($request) {
    $token = $request->get_param('token');
    $user_id = wp_verify_nonce($token, 'mi_chat_nonce');
    if ($user_id) {
        return new WP_REST_Response(['valid' => true, 'user_id' => $user_id], 200);
    } else {
        return new WP_REST_Response(['valid' => false], 401);
    }
}

/*
websocket

 private function verificarToken(ConnectionInterface $conn, $token, $emisor)
    {
        // Log para ver el token y el emisor que se están verificando
        echo "Iniciando verificación del token para el emisor: {$emisor} en la conexión {$conn->resourceId}\n";
        echo "Token recibido: {$token}\n";
    
        // URL del endpoint de verificación de token
        $url = 'https://2upra.com/wp-json/galle/v1/verificarToken';
    
        // Opciones de la solicitud HTTP para verificar el token
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode(['token' => $token])
            ]
        ];
    
        // Log para mostrar las opciones enviadas en la solicitud
        echo "Enviando solicitud de verificación a WordPress con las siguientes opciones:\n";
        echo "URL: {$url}\n";
        echo "Contenido: " . json_encode(['token' => $token]) . "\n";
    
        // Enviar la solicitud y obtener el resultado
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);  // Usa @ para suprimir errores visibles
    
        // Si la solicitud falla
        if ($result === FALSE) {
            echo "Error: No se pudo contactar con el servidor de autenticación de WordPress.\n";
            $conn->send(json_encode(['type' => 'auth', 'status' => 'error', 'message' => 'No se pudo contactar con el servidor de autenticación.']));
            return;
        }
    
        // Log para mostrar la respuesta recibida de WordPress
        echo "Respuesta recibida de WordPress: {$result}\n";
    
        // Decodificar la respuesta de WordPress
        $response = json_decode($result, true);
    
        // Verificar si la respuesta es válida y correcta
        if ($response && isset($response['valid']) && $response['valid']) {
            // Asociar el emisor y el token con la conexión
            $this->users[$conn->resourceId] = $emisor; 
            $this->autenticados[$conn->resourceId] = $token;
    
            // Enviar respuesta de éxito al cliente
            $conn->send(json_encode(['type' => 'auth', 'status' => 'success']));
            echo "Autenticación exitosa para el emisor: {$emisor} en la conexión {$conn->resourceId}\n";
        } else {
            // Si el token es inválido
            echo "Error: Token inválido para el emisor: {$emisor} en la conexión {$conn->resourceId}\n";
            $conn->send(json_encode(['type' => 'auth', 'status' => 'failed', 'message' => 'Token inválido.']));
        }
    }

*/

function procesarMensaje($request) {
    chatLog($request, 'Iniciando procesarMensaje');
    
    // Obtener el usuario actual autenticado
    $usuario_actual = wp_get_current_user();
    
    // Si no hay usuario autenticado o ID no es válido
    if (!$usuario_actual->exists()) {
        chatLog($request, 'Error: Usuario no autenticado');
        return new WP_Error('usuario_no_autenticado', 'Usuario no autenticado', array('status' => 403));
    }

    $params = $request->get_json_params();
    chatLog($request, 'Parámetros recibidos: ' . json_encode($params));
    
    $emisor = isset($params['emisor']) ? $params['emisor'] : null;
    $receptor = isset($params['receptor']) ? $params['receptor'] : null;
    $mensaje = isset($params['mensaje']) ? $params['mensaje'] : null;
    $adjunto = isset($params['adjunto']) ? $params['adjunto'] : null;
    $metadata = isset($params['metadata']) ? $params['metadata'] : null;

    // Verificar si los parámetros requeridos están presentes
    if (!$emisor || !$receptor || !$mensaje) {
        chatLog($request, 'Error: Datos incompletos');
        return new WP_Error('datos_incompletos', 'Faltan datos requeridos', array('status' => 400));
    }

    // Verificar si el emisor es el mismo que el usuario autenticado
    if ($emisor != $usuario_actual->ID) {
        chatLog($request, 'Error: El emisor no coincide con el usuario autenticado. Emisor: ' . $emisor . ', Usuario autenticado: ' . $usuario_actual->ID);
        return new WP_Error('emisor_no_autorizado', 'El emisor no coincide con el usuario autenticado', array('status' => 403));
    }

    chatLog($request, 'Intentando guardar mensaje');
    
    try {
        // Intentar guardar el mensaje
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



