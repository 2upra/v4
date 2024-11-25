<?php
//api.php
add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/user_audio_downloads/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'get_user_audio_downloads',
        'permission_callback' => 'check_electron_app_header'
    ));
});

/*

se supone que el audio tiene que descargarse desde la url generada temporalmente pero se ve que lo hace asi: 
no funciona porque https://2upra.com/wp-content/uploads es es innacesible por seguridad 
que esta
Received response: [
  {
    post_id: 319580,
    audio_url: 'https://2upra.com/wp-content/uploads/2024/11/Memphis-Snare_TSN5_2upra.wav',
    audio_filename: 'Memphis-Snare_TSN5_2upra.wav'
  }
]
Skipping audio with invalid download_url: {
  post_id: 319580,
  audio_url: 'https://2upra.com/wp-content/uploads/2024/11/Memphis-Snare_TSN5_2upra.wav',
  audio_filename: 'Memphis-Snare_TSN5_2upra.wav'
}
*/

// Function to verify the X-Electron-App header
function check_electron_app_header() {
    error_log("Headers: " . print_r($_SERVER, true));
    error_log("X-Electron-App: " . (isset($_SERVER['HTTP_X_ELECTRON_APP']) ? $_SERVER['HTTP_X_ELECTRON_APP'] : 'No header'));

    if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
        error_log("Access allowed");
        return true;
    } else {
        error_log("Access denied");
        return new WP_Error('forbidden', 'Access not authorized', array('status' => 403));
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
            'nonce' => array(
                'required' => true,
                'type' => 'string',
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_download_endpoint');

function get_user_audio_downloads(WP_REST_Request $request) {
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
            if ($attachment_id && get_post_type($attachment_id) === 'attachment') {
                $file_path = wp_get_attachment_path($attachment_id);
                $mime_type = mime_content_type($file_path);

                if (strpos($mime_type, 'audio/') === 0) {
                    $token = wp_generate_password(20, false);
                    $nonce = wp_create_nonce('download_' . $token);
                    set_transient('download_token_' . $token, $attachment_id, 60 * 5);
                    $download_url = home_url("/wp-json/my-custom-download/v1/download/?token=$token&nonce=$nonce");

                    $downloads[] = [
                        'post_id' => $post_id,
                        'download_url' => $download_url,
                        'audio_filename' => get_the_title($attachment_id) . '.' . pathinfo($file_path, PATHINFO_EXTENSION)
                    ];
                } else {
                    error_log("File is not an audio: $file_path");
                }
            } else {
                error_log("Attachment not found for post ID: $post_id");
            }
        }
    }

    return rest_ensure_response($downloads);
}

function serve_download(WP_REST_Request $request) {
    $token = $request->get_param('token');
    $nonce = $request->get_param('nonce');

    // Verify nonce
    if (!wp_verify_nonce($nonce, 'download_' . $token)) {
        error_log("Invalid nonce");
        return new WP_Error('invalid_nonce', 'Invalid nonce.', array('status' => 403));
    }

    // Retrieve the attachment ID associated with the token
    $attachment_id = get_transient('download_token_' . $token);

    if ($attachment_id) {
        // Delete the token to prevent reuse
        delete_transient('download_token_' . $token);

        // Get the file path
        $file_path = wp_get_attachment_path($attachment_id);

        if (file_exists($file_path)) {
            // Get the mime type
            $mime_type = mime_content_type($file_path);

             // Check if the mime type starts with 'audio/'
             if (strpos($mime_type, 'audio/') !== 0) {
                error_log("File is not an audio: $file_path");
                return new WP_Error('invalid_file_type', 'Invalid file type.', array('status' => 400));
            }

            // Set headers for the download
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