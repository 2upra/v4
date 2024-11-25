<?php

add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/user_audio_downloads/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'get_user_audio_downloads',
        'permission_callback' => 'check_electron_app_header' // Función para verificar el encabezado
    ));
});

/*
necesito que cuando envie una url, envie una url segura temporal de un solo uso para seguridad, 

     validateStatus: [Function: validateStatus],
      headers: [Object [AxiosHeaders]],
      method: 'get',
      url: 'https://2upra.com/wp-content/uploads/2024/11/Memphis-Snare_TSN5_2upra.wav',
*/


function get_user_audio_downloads(WP_REST_Request $request)
{
    // Verify the X-Electron-App header
    $is_electron_app = check_electron_app_header();
    if (is_wp_error($is_electron_app)) {
        return $is_electron_app;
    }

    $user_id = $request->get_param('user_id');
    $descargas = get_user_meta($user_id, 'descargas', true);

    $downloads = [];

    if (is_array($descargas)) {
        foreach ($descargas as $post_id => $count) {
            $attachment_id = get_post_meta($post_id, 'post_audio', true);
            if ($attachment_id) {
                $audio_url = wp_get_attachment_url($attachment_id);
                $audio_filename = basename($audio_url);

                // Generate a unique token
                $token = wp_generate_password(20, false);
                // Store the token with the audio URL and set expiration time
                set_transient('download_token_' . $token, $audio_url, 60 * 5); // Expires in 5 minutes

                // Generate the temporary download URL
                $download_url = home_url('/wp-json/my-custom-download/v1/download/?token=' . $token);

                $downloads[] = [
                    'post_id' => $post_id,
                    'download_url' => $download_url,
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
    error_log("X-Electron-App: " . (isset($_SERVER['HTTP_X_ELECTRON_APP']) ? $_SERVER['HTTP_X_ELECTRON_APP'] : 'No header'));

    if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
        error_log("Acceso permitido");
        return true;
    } else {
        error_log("Acceso denegado");
        return new WP_Error( 'forbidden', 'Acceso no autorizado', array( 'status' => 403 ) );
    }
}

function register_download_endpoint() {
    register_rest_route('my-custom-download/v1', '/download/', array(
        'methods' => 'GET',
        'callback' => 'serve_download',
        'args' => array(
            'token' => array(
                'required' => true,
                'type' => 'string',
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_download_endpoint');

function serve_download(WP_REST_Request $request) {
    // Verify the X-Electron-App header
    $is_electron_app = check_electron_app_header();
    if (is_wp_error($is_electron_app)) {
        return $is_electron_app;
    }

    $token = $request->get_param('token');

    // Retrieve the audio URL associated with the token
    $audio_url = get_transient('download_token_' . $token);

    if ($audio_url) {
        // Delete the token to prevent reuse
        delete_transient('download_token_' . $token);

        // Serve the file
        $file_path = wp_normalize_path(ABSPATH . str_replace(home_url('/'), '', $audio_url));
        if (file_exists($file_path)) {
            // Get file info
            $file_name = basename($file_path);
            $file_size = filesize($file_path);
            $file_type = mime_content_type($file_path);

            // Output headers
            header('Cache-Control: public');
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Content-Type: ' . $file_type);
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . $file_size);

            // Read the file and output it
            readfile($file_path);
            exit;
        } else {
            return new WP_Error('file_not_found', 'File not found.', array('status' => 404));
        }
    } else {
        return new WP_Error('invalid_token', 'Invalid or expired token.', array('status' => 403));
    }
}