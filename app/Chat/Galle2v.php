<?php



/*
2024-09-30 22:00:02 - Iniciando verificación del token. Token recibido: 131d7fe2270e17e1a4ed10f190cda533c5f53d688277163645457f5251aeff53 para el usuario ID: 44
2024-09-30 22:00:02 - Token válido para el usuario ID: 44
2024-09-30 22:00:16 - Registrando la ruta /procesarmensaje en la API REST.
2024-09-30 22:00:16 - Registrando la ruta /verificartoken en la API REST.
2024-09-30 22:00:16 - Iniciando verificación del token. Token recibido: 131d7fe2270e17e1a4ed10f190cda533c5f53d688277163645457f5251aeff53 para el usuario ID: 44
2024-09-30 22:00:16 - Token válido para el usuario ID: 44
2024-09-30 22:00:16 - Verificación del token en /procesarmensaje: Válido
2024-09-30 22:00:16 - {}
2024-09-30 22:00:16 - {}
2024-09-30 22:00:16 - Iniciando verificación del token. Token recibido: 131d7fe2270e17e1a4ed10f190cda533c5f53d688277163645457f5251aeff53 para el usuario ID: 44
2024-09-30 22:00:16 - Token válido para el usuario ID: 44
2024-09-30 22:00:16 - Verificación del token en /procesarmensaje: Válido

Mensaje de autenticación recibido. Verificando token...
Iniciando verificación del token para el emisor: 44 en la conexión 94
Token recibido: 131d7fe2270e17e1a4ed10f190cda533c5f53d688277163645457f5251aeff53
Datos enviados a WordPress: {"token":"131d7fe2270e17e1a4ed10f190cda533c5f53d688277163645457f5251aeff53","user_id":"44"}
Respuesta recibida de WordPress: 


{"valid":true,"user_id":"44"}
Autenticación exitosa para el emisor: 44 en la conexión 94
Mensaje recibido de 94: {"emisor":"44","receptor":"1","mensaje":"hola","adjunto":null,"metadata":null}
Buscando receptor con ID: 1
Mensaje enviado al receptor 1 (conexión 88)
Intentando guardar mensaje en WordPress...
Token autenticado: 131d7fe2270e17e1a4ed10f190cda533c5f53d688277163645457f5251aeff53
Datos a enviar a WordPress: {"emisor":"44","receptor":"1","mensaje":"hola","adjunto":null,"metadata":null}
Token usado para autenticar en WordPress: 131d7fe2270e17e1a4ed10f190cda533c5f53d688277163645457f5251aeff53
User ID usado para autenticar en WordPress: 44
Respuesta de WordPress: 


{"code":"usuario_no_autenticado","message":"Usuario no autenticado","data":{"status":403}}


*/


add_action('rest_api_init', function () {
    chatLog('Registrando la ruta /procesarmensaje en la API REST.');
    register_rest_route('galle/v2', '/procesarmensaje', array(
        'methods' => 'POST',
        'callback' => 'procesarMensaje',
        'permission_callback' => function ($request) {
            // Obtener el token y user_id desde los headers
            $token = $request->get_header('X-WP-Token');
            $user_id = $request->get_header('X-User-ID');

            if (!$token || !$user_id) {
                chatLog('Error: Token o User ID no proporcionados');
                return false;
            }

            // Verificar el token personalizado
            $response = verificarToken($request); // Obtener el objeto WP_REST_Response

            // Asegúrate de que la respuesta es un WP_REST_Response válido
            if (is_wp_error($response)) {
                chatLog('Error en la verificación del token: ' . $response->get_error_message());
                return false;
            }

            // Decodificar el contenido del cuerpo de la respuesta
            $response_data = json_decode(wp_json_encode($response->get_data()), true);

            // Verificar si el token es válido
            if (isset($response_data['valid']) && $response_data['valid']) {
                chatLog('Verificación del token en /procesarmensaje: Válido');
                return true;
            } else {
                chatLog('Verificación del token en /procesarmensaje: Inválido');
                return false;
            }
        }
    ));
});

function verificarToken($request) {
    // Obtener el token y user_id desde los parámetros o los headers
    $token = $request->get_param('token') ?: $request->get_header('X-WP-Token');
    $user_id = $request->get_param('user_id') ?: $request->get_header('X-User-ID');

    chatLog('Iniciando verificación del token. Token recibido: ' . ($token ? $token : 'No proporcionado') . ' para el usuario ID: ' . ($user_id ? $user_id : 'No proporcionado'));

    if (empty($token) || empty($user_id)) {
        chatLog('Error: No se proporcionó token o el token/ID de usuario está vacío.');
        return new WP_REST_Response([
            'valid' => false,
            'message' => 'Token o ID de usuario faltante'
        ], 400); // Devuelve un error 400 si faltan parámetros
    }

    $secret_key = ($_ENV['GALLEKEY']);
    $current_time = time();
    $rounded_time = floor($current_time / 300); // Redondea el tiempo a intervalos de 5 minutos
    
    // Genera el token esperado para el tiempo actual y el anterior
    $expected_token = hash_hmac('sha256', $user_id . $rounded_time, $secret_key);
    $previous_rounded_time = $rounded_time - 1;
    $previous_expected_token = hash_hmac('sha256', $user_id . $previous_rounded_time, $secret_key);

    // Verificar si el token recibido coincide con el token esperado o el anterior
    if (hash_equals($expected_token, $token) || hash_equals($previous_expected_token, $token)) {
        chatLog('Token válido para el usuario ID: ' . $user_id);
        return new WP_REST_Response([
            'valid' => true,
            'user_id' => $user_id
        ], 200); // Devuelve 200 si el token es válido
    } else {
        chatLog('Error: Token inválido. Token esperado: ' . $expected_token . ', Token recibido: ' . $token);
        return new WP_REST_Response([
            'valid' => false,
            'message' => 'Token inválido'
        ], 401); // Devuelve 401 si el token es inválido
    }
}

add_action('rest_api_init', function () {
    chatLog('Registrando la ruta /verificartoken en la API REST.');
    register_rest_route('galle/v2', '/verificartoken', array(
        'methods' => 'POST',
        'callback' => 'verificarToken',
        'permission_callback' => '__return_true'
    ));
});

add_action('wp_ajax_generarToken', 'generarToken');

function generarToken() {
    if (!is_user_logged_in()) {
        chatLog('Error: Intento de generación de token sin usuario autenticado. Usuario no ha iniciado sesión.');
        wp_send_json_error('Usuario no autenticado');
    }

    $user_id = get_current_user_id();
    chatLog('Usuario autenticado con ID: ' . $user_id);

    // Generar un token manualmente usando el ID del usuario y el timestamp redondeado
    $secret_key = ($_ENV['GALLEKEY']); // Cambia esta clave secreta a algo más seguro
    $rounded_time = floor(time() / 300); // Redondear el tiempo a intervalos de 5 minutos
    $token = hash_hmac('sha256', $user_id . $rounded_time, $secret_key);

    chatLog('Token generado manualmente para el usuario ID: ' . $user_id . '. Token: ' . $token);
    
    wp_send_json_success(['token' => $token, 'user_id' => $user_id]);
}




function procesarMensaje($request) {
    chatLog('Iniciando procesarMensaje');

    // Obtener el usuario actual autenticado
    $usuario_actual = wp_get_current_user();

    // Si no hay usuario autenticado, intentar autenticación manual con el user_id
    if (!$usuario_actual->exists()) {
        $user_id = $request->get_header('X-User-ID');
        if ($user_id) {
            wp_set_current_user($user_id); // Forzar autenticación de usuario
            $usuario_actual = wp_get_current_user();
            if (!$usuario_actual->exists()) {
                chatLog('Error: Usuario no autenticado después de intentar forzar autenticación');
                return new WP_Error('usuario_no_autenticado', 'Usuario no autenticado', array('status' => 403));
            }
        } else {
            chatLog('Error: Usuario no autenticado y no se pudo obtener el ID de usuario');
            return new WP_Error('usuario_no_autenticado', 'Usuario no autenticado', array('status' => 403));
        }
    }

    $params = $request->get_json_params();
    chatLog('Parámetros recibidos: ' . json_encode($params));

    $emisor = isset($params['emisor']) ? $params['emisor'] : null;
    $receptor = isset($params['receptor']) ? $params['receptor'] : null;
    $mensaje = isset($params['mensaje']) ? $params['mensaje'] : null;
    $adjunto = isset($params['adjunto']) ? $params['adjunto'] : null;
    $metadata = isset($params['metadata']) ? $params['metadata'] : null;

    // Verificar si los parámetros requeridos están presentes
    if (!$emisor || !$receptor || !$mensaje) {
        chatLog('Error: Datos incompletos');
        return new WP_Error('datos_incompletos', 'Faltan datos requeridos', array('status' => 400));
    }

    // Verificar si el emisor es el mismo que el usuario autenticado
    if ($emisor != $usuario_actual->ID) {
        chatLog('Error: El emisor no coincide con el usuario autenticado. Emisor: ' . $emisor . ', Usuario autenticado: ' . $usuario_actual->ID);
        return new WP_Error('emisor_no_autorizado', 'El emisor no coincide con el usuario autenticado', array('status' => 403));
    }

    chatLog('Intentando guardar mensaje');

    try {
        // Intentar guardar el mensaje
        $resultado = guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata);

        if ($resultado) {
            chatLog('Mensaje guardado con éxito');
            return new WP_REST_Response(['success' => true], 200);
        } else {
            chatLog('Error: No se pudo guardar el mensaje');
            return new WP_Error('error_guardado', 'No se pudo guardar el mensaje', array('status' => 500));
        }
    } catch (Exception $e) {
        chatLog('Excepción al guardar mensaje: ' . $e->getMessage());
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



