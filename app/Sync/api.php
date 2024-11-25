<?php

add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/user_audio_downloads/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'get_user_audio_downloads',
        'permission_callback' => 'check_electron_app_header' // Function to verify the header
    ));
});

function get_user_audio_downloads(WP_REST_Request $request) {
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
                // Generate a unique token and nonce
                $token = wp_generate_password(20, false);
                $nonce = wp_create_nonce('download_' . $token);

                // Store the attachment ID with the token
                set_transient('download_token_' . $token, $attachment_id, 60 * 5); // Expires in 5 minutes

                // Generate the temporary download URL
                $download_url = home_url("/wp-json/my-custom-download/v1/download/?token=$token&nonce=$nonce");

                $downloads[] = [
                    'post_id' => $post_id,
                    'download_url' => $download_url,
                    'audio_filename' => get_the_title($attachment_id)
                ];
            }
        }
    }

    return rest_ensure_response($downloads);
}
// Function to verify the X-Electron-App header
function check_electron_app_header() {
    error_log("Headers: " . print_r($_SERVER, true)); // Prints all server headers
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

function serve_download(WP_REST_Request $request) {
    // Verify the X-Electron-App header
    $is_electron_app = check_electron_app_header();
    if (is_wp_error($is_electron_app)) {
        return $is_electron_app;
    }

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
            // Serve the file using WordPress' file serving function
            wp_serve_file($file_path, array('force_download' => true));
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