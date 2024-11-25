<?php

add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/user_audio_downloads/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'get_user_audio_downloads',
        'permission_callback' => 'check_electron_app_header' // Función para verificar el encabezado
    ));
});



function get_user_audio_downloads(WP_REST_Request $request)
{
    $user_id = $request->get_param('user_id');
    $descargas = get_user_meta($user_id, 'descargas', true);

    $downloads = [];

    if (is_array($descargas)) {
        foreach ($descargas as $post_id => $count) {
            $attachment_id = get_post_meta($post_id, 'post_audio', true);
            if ($attachment_id) {
                $audio_url = wp_get_attachment_url($attachment_id);
                $audio_filename = basename($audio_url);
                $downloads[] = [
                    'post_id' => $post_id,
                    'audio_url' => $audio_url,
                    'audio_filename' => $audio_filename
                ];
            }
        }
    }


    return rest_ensure_response($downloads);
}

// Función para verificar el encabezado X-Electron-App
function check_electron_app_header() {
    error_log("Encabezados: " . print_r($_SERVER, true)); // Imprime todos los encabezados del servidor
    error_log("X-Electron-App: " . $_SERVER['HTTP_X_ELECTRON_APP']); // Imprime el valor del encabezado específico

    if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
        error_log("Acceso permitido");
        return true;
    } else {
        error_log("Acceso denegado");
        return new WP_Error( 'forbidden', 'Acceso no autorizado', array( 'status' => 403 ) );
    }
}