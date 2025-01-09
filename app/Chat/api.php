<?

add_action('rest_api_init', function () {
    register_rest_route('galle/v2', '/verificartoken', array(
        'methods' => 'POST',
        'callback' => 'verificarToken',
        'permission_callback' => '__return_true'
    ));
});

add_action('wp_ajax_generarToken', 'generarToken');

add_action('rest_api_init', function () {
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

            $response = verificarToken($request);
            if (is_wp_error($response)) {
                chatLog('Error en la verificación del token: ' . $response->get_error_message());
                return false;
            }

            $response_data = json_decode(wp_json_encode($response->get_data()), true);

            if (isset($response_data['valid']) && $response_data['valid']) {
                return true;
            } else {
                return false;
            }
        }
    ));
});

function verificarToken($request)
{
    // Obtener el token y user_id desde los parámetros o los headers
    $token = $request->get_param('token') ?: $request->get_header('X-WP-Token');
    $user_id = $request->get_param('user_id') ?: $request->get_header('X-User-ID');

    if (empty($token) || empty($user_id)) {
        chatLog('Error: No se proporcionó token o el token/ID de usuario está vacío.');
        return new WP_REST_Response([
            'valid' => false,
            'message' => 'Token o ID de usuario faltante'
        ], 400);
    }

    $secret_key = ($_ENV['GALLEKEY']);
    $current_time = time();
    $rounded_time = floor($current_time / 86400);

    // Generar el token esperado para el día actual y el día anterior
    $expected_token = hash_hmac('sha256', $user_id . $rounded_time, $secret_key);
    $previous_rounded_time = $rounded_time - 1;
    $previous_expected_token = hash_hmac('sha256', $user_id . $previous_rounded_time, $secret_key);

    // Verificar si el token recibido coincide con el token esperado o el del día anterio
    if (hash_equals($expected_token, $token) || hash_equals($previous_expected_token, $token)) {
        return new WP_REST_Response([
            'valid' => true,
            'user_id' => $user_id
        ], 200);
    } else {
        chatLog('Error: Token inválido. Token esperado: ' . $expected_token . ', Token recibido: ' . $token);
        return new WP_REST_Response([
            'valid' => false,
            'message' => 'Token inválido'
        ], 401);
    }
}

function generarToken()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
    }

    $usu = get_current_user_id();
    $claveSecreta = $_ENV['GALLEKEY'] ?? '';
    $tiempoRedondeado = floor(time() / 86400);
    $log = "generarToken:";

    if (empty($claveSecreta)) {
        $log .= "Error: La clave secreta no está definida.";
        //guardarLog($log);
        wp_send_json_error('Error interno del servidor');
    }

    $token = hash_hmac('sha256', $usu . $tiempoRedondeado, $claveSecreta);
    $log .= "\n Token generado para el usuario $usu";
    //guardarLog($log);
    wp_send_json_success(['token' => $token, 'usu' => $usu]);
}
