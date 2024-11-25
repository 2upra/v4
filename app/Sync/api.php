<?php
// Registrar la ruta del API REST
add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/user_audio_downloads/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'get_user_audio_downloads',
        'permission_callback' => 'check_electron_app_header', // Verifica el header
    ));

    // Registrar el endpoint para descargar los archivos
    register_rest_route('my-custom-download/v1', '/download/', array(
        'methods' => 'GET',
        'callback' => 'serve_download',
        'args' => array(
            'token' => array(
                'required' => true,
                'type' => 'string',
            ),
            'nonce' => array(
                'required' => true,
                'type' => 'string',
            ),
        ),
    ));
});

// Función para verificar el header personalizado
function check_electron_app_header() {
    // Registrar los headers en el log para depuración
    error_log("Headers: " . print_r($_SERVER, true));
    error_log("X-Electron-App: " . (isset($_SERVER['HTTP_X_ELECTRON_APP']) ? $_SERVER['HTTP_X_ELECTRON_APP'] : 'No header'));

    // Verificar si el header está presente y tiene el valor correcto
    if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
        error_log("Access allowed");
        return true; // Autorizado
    } else {
        error_log("Access denied");
        return new WP_Error('forbidden', 'Access not authorized', array('status' => 403));
    }
}

// Función para obtener las descargas del usuario
function get_user_audio_downloads(WP_REST_Request $request) {
    $user_id = $request->get_param('user_id');
    error_log("Fetching audio downloads for user ID: $user_id");

    // Obtener las descargas del usuario desde la meta
    $descargas = get_user_meta($user_id, 'descargas', true);
    error_log("User downloads meta: " . print_r($descargas, true));

    $downloads = [];

    // Verificar que las descargas sean un array válido
    if (is_array($descargas)) {
        foreach ($descargas as $post_id => $count) {
            $attachment_id = get_post_meta($post_id, 'post_audio', true);
            error_log("Post ID: $post_id, Attachment ID: $attachment_id");

            // Validar que el attachment sea correcto
            if ($attachment_id && get_post($attachment_id)) {
                $file_path = wp_get_attachment_path($attachment_id); // Obtener la ruta del archivo
                if ($file_path && file_exists($file_path)) {
                    $mime_type = mime_content_type($file_path); // Obtener el MIME type
                    if (strpos($mime_type, 'audio/') === 0) {
                        // Generar token y nonce para la descarga
                        $token = wp_generate_password(20, false);
                        $nonce = wp_create_nonce('download_' . $token);
                        set_transient('download_token_' . $token, $attachment_id, 60 * 5); // Token válido por 5 minutos
                        $download_url = home_url("/wp-json/my-custom-download/v1/download/?token=$token&nonce=$nonce");

                        // Agregar al array de descargas
                        $downloads[] = [
                            'post_id' => $post_id,
                            'download_url' => $download_url,
                            'audio_filename' => get_the_title($attachment_id) . '.' . pathinfo($file_path, PATHINFO_EXTENSION),
                        ];
                    } else {
                        error_log("Invalid audio MIME type: $mime_type");
                    }
                } else {
                    error_log("File does not exist: $file_path");
                }
            } else {
                error_log("Invalid attachment ID for post ID: $post_id");
            }
        }
    } else {
        error_log("No downloads found for user ID: $user_id");
    }

    return rest_ensure_response($downloads);
}

// Función para servir la descarga del archivo
function serve_download(WP_REST_Request $request) {
    $token = $request->get_param('token');
    $nonce = $request->get_param('nonce');

    // Verificar el nonce
    if (!wp_verify_nonce($nonce, 'download_' . $token)) {
        error_log("Invalid nonce");
        return new WP_Error('invalid_nonce', 'Invalid nonce.', array('status' => 403));
    }

    // Recuperar el attachment ID asociado al token
    $attachment_id = get_transient('download_token_' . $token);

    if ($attachment_id) {
        // Eliminar el token para evitar reutilización
        delete_transient('download_token_' . $token);

        // Obtener la ruta del archivo
        $file_path = wp_get_attachment_path($attachment_id);

        if ($file_path && file_exists($file_path)) {
            // Obtener el MIME type
            $mime_type = mime_content_type($file_path);

            // Verificar que sea un archivo de audio
            if (strpos($mime_type, 'audio/') !== 0) {
                error_log("File is not an audio: $file_path");
                return new WP_Error('invalid_file_type', 'Invalid file type.', array('status' => 400));
            }

            // Establecer los headers para la descarga
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: no-cache');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            error_log("File not found at path: $file_path");
            return new WP_Error('file_not_found', 'File not found.', array('status' => 404));
        }
    } else {
        error_log("Invalid or expired token");
        return new WP_Error('invalid_token', 'Invalid or expired token.', array('status' => 403));
    }
}