<?php

// Refactor(Org): Función config() movida a app/View/Components/Profile/ConfigForm.php

function cambiar_imagen_perfil()
{
    $user_id = get_current_user_id();

    if (isset($_FILES['file']) && $user_id > 0) {
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('error' => 'Error en la subida del archivo.'));
            return;
        }
        $previous_attachment_id = get_user_meta($user_id, 'imagen_perfil_id', true);
        $user_info = get_userdata($user_id);
        $username = $user_info->user_login;
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $username . '_' . time() . '.' . $extension;
        add_filter('wp_handle_upload_prefilter', function ($file) use ($new_filename) {
            $file['name'] = $new_filename;
            return $file;
        });
        $upload = wp_handle_upload($file, array('test_form' => false));

        if ($upload && !isset($upload['error'])) {
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($new_filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attachment_id = wp_insert_attachment($attachment, $upload['file']);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            update_user_meta($user_id, 'imagen_perfil_id', $attachment_id);
            $url_imagen_perfil = wp_get_attachment_url($attachment_id);

            // Eliminar el adjunto anterior si existe
            if ($previous_attachment_id) {
                wp_delete_attachment($previous_attachment_id, true);
            }

            wp_send_json_success(array('url_imagen_perfil' => esc_url($url_imagen_perfil)));
        } else {
            wp_send_json_error(array('error' => $upload['error']));
        }
    } else {
        wp_send_json_error(array('error' => 'No se pudo subir la imagen.'));
    }
}
add_action('wp_ajax_cambiar_imagen_perfil', 'cambiar_imagen_perfil');

// Functions cambiar_nombre, cambiar_descripcion, cambiar_enlace and their hooks moved to app/Services/UserService.php

?>