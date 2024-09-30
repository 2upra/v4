<?php



/*
tengo que ajustar a la nueva forma de generar el nonce supongo al como se guardan los mensajes o quitar la seguridad en esta parte, lo que hice fue generar nonce manulamente, ya no uso wp verify nonce y necesito ajustar la funcion de procesar y guardar el mensaje

da este error 
{"code":"rest_cookie_invalid_nonce","message":"Ha fallado la comprobaci\u00f3n de la cookie","data":{"status":403}}

te muestro el contexto

*/


add_action('rest_api_init', function () {
    chatLog('Registrando la ruta /procesarmensaje en la API REST.');
    register_rest_route('galle/v2', '/procesarmensaje', array(
        'methods' => 'POST',
        'callback' => 'procesarMensaje',
        'permission_callback' => function ($request) {
            // Obtener el token y user_id desde los headers o los parámetros de la solicitud
            $token = $request->get_header('X-WP-Token');  // Asegúrate de enviar este encabezado en tu solicitud
            $user_id = $request->get_header('X-User-ID'); // O puedes obtenerlo de los parámetros del cuerpo de la solicitud

            if (!$token || !$user_id) {
                chatLog('Error: Token o User ID no proporcionados');
                return false;
            }

            // Reutilizamos la función verificarToken para validar el token
            $is_valid = verificarToken(new WP_REST_Request('POST', '/galle/v2/verificartoken', [
                'token' => $token,
                'user_id' => $user_id
            ]));

            chatLog('Verificación del token en /procesarmensaje: ' . (is_wp_error($is_valid) || !$is_valid['valid'] ? 'Inválido' : 'Válido'));

            return (is_wp_error($is_valid) || !$is_valid['valid']) ? false : true;
        }
    ));
});

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


function verificarToken($request) {
    // Obtener el token y user_id desde los parámetros o los headers
    $token = $request->get_param('token') ?: $request->get_header('X-WP-Token');
    $user_id = $request->get_param('user_id') ?: $request->get_header('X-User-ID');

    chatLog('Iniciando verificación del token. Token recibido: ' . ($token ? $token : 'No proporcionado') . ' para el usuario ID: ' . ($user_id ? $user_id : 'No proporcionado'));

    if (empty($token) || empty($user_id)) {
        chatLog('Error: No se proporcionó token o el token/ID de usuario está vacío.');
        return new WP_REST_Response(['valid' => false], 401);
    }

    $secret_key = ($_ENV['GALLEKEY']);
    $current_time = time();
    $rounded_time = floor($current_time / 300);
    
    $expected_token = hash_hmac('sha256', $user_id . $rounded_time, $secret_key);
    $previous_rounded_time = $rounded_time - 1;
    $previous_expected_token = hash_hmac('sha256', $user_id . $previous_rounded_time, $secret_key);

    if (hash_equals($expected_token, $token) || hash_equals($previous_expected_token, $token)) {
        chatLog('Token válido para el usuario ID: ' . $user_id);
        return ['valid' => true, 'user_id' => $user_id];
    } else {
        chatLog('Error: Token inválido. Token esperado: ' . $expected_token . ', Token recibido: ' . $token);
        return new WP_REST_Response(['valid' => false], 401);
    }
}

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



