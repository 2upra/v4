<?php
// Refactor: Token generation, verification, and endpoint logic moved from app/Authentication/Iniciar.php

function generate_secure_token($user_id)
{
    // Genera un token único y define la expiración
    $token = bin2hex(random_bytes(32));
    $expiration = time() + 36000; // 10 horas

    // Elimina posibles entradas duplicadas para este usuario
    delete_user_meta($user_id, 'session_token');
    delete_user_meta($user_id, 'session_token_expiration');

    // Debug: verifica si los metadatos fueron realmente eliminados
    $existing_token = get_user_meta($user_id, 'session_token', true);
    $existing_expiration = get_user_meta($user_id, 'session_token_expiration', true);

    if ($existing_token || $existing_expiration) {
        error_log('generate_secure_token - No se pudieron eliminar las entradas duplicadas.');
        return false; // Devuelve false si no se eliminan correctamente
    }

    // Añade los nuevos valores
    $result_token = update_user_meta($user_id, 'session_token', $token);
    $result_expiration = update_user_meta($user_id, 'session_token_expiration', $expiration);

    error_log('generate_secure_token - user_id: ' . $user_id . ' token: ' . $token . ' expiration: ' . $expiration . ' result_token: ' . ($result_token ? 'true' : 'false') . ' result_expiration: ' . ($result_expiration ? 'true' : 'false'));

    return $token; // Devuelve el token generado
}

function verify_secure_token($token)
{
    global $wpdb;

    // Cambiamos la subconsulta para usar MAX() y asegurarnos de que solo devuelva un valor
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id
FROM $wpdb->usermeta
WHERE meta_key = 'session_token'
AND meta_value = %s
AND CAST((
SELECT MAX(meta_value)
FROM $wpdb->usermeta
WHERE user_id = $wpdb->usermeta.user_id
AND meta_key = 'session_token_expiration'
) AS UNSIGNED) > %d",
        $token,
        time()
    ));

    // Log para depuración
    error_log('verify_secure_token - token: ' . $token . ' user_id: ' . ($user_id ? $user_id : 'not found'));

    // Devuelve el ID del usuario si es válido, de lo contrario devuelve false
    if ($user_id) {
        return $user_id;
    }

    error_log('verify_secure_token - invalid token: ' . $token);
    return false;
}

function verify_token_endpoint(WP_REST_Request $request)
{
    // Obtiene el token enviado en el request
    $token = $request->get_param('token');

    // Verifica el token
    $user_id = verify_secure_token($token);

    if ($user_id) {
        error_log('verify_token_endpoint - token: ' . $token . ' user_id: ' . $user_id);
        return new WP_REST_Response(array('user_id' => $user_id, 'status' => 'valid'), 200);
    } else {
        error_log('verify_token_endpoint - invalid token: ' . $token);
        return new WP_REST_Response(array('message' => 'Token inválido', 'status' => 'invalid'), 401);
    }
}

add_action('rest_api_init', function () {
    register_rest_route('2upra/v1', '/verify_token', array(
        'methods' => 'POST',
        'callback' => 'verify_token_endpoint',
    ));
});
