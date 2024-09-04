<?php

function config() {
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    ob_start(); ?>

    <div class="LEDDCN">
        <p class="ONDNYU">Configuración de Perfil</p>
        <form class="PVSHOT">
            <div class="PTORKC">
                <div class="previewAreaArchivos" id="previewAreaImagenPerfil">Arrastra tu foto de perfil</div>
                <input type="file" id="profilePicture" accept="image/*" style="display:none;">
            </div>
            <div class="PTORKC">
                <label for="username">Nombre de Usuario:</label>
                <input type="text" id="username" name="username" value="<?php echo esc_attr($current_user->display_name); ?>">
            </div>
            <div class="PTORKC">
                <label for="description">Descripción:</label>
                <textarea id="description" name="description" rows="2"><?php echo esc_attr(get_user_meta($user_id, 'profile_description', true)); ?></textarea>
            </div>
            <div class="PTORKC">
                <label for="link">Enlace:</label>
                <input type="url" id="link" name="link" placeholder="Ingresa un enlace (opcional)" value="<?php echo esc_attr(get_user_meta($user_id, 'user_link', true)); ?>">
            </div>
        </form>
    </div>
    <?php return ob_get_clean();
}

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

function update_user_profile($meta_key, $error_msg, $sanitize_callback = null, $max_length = null) {
    if (!is_user_logged_in()) wp_send_json_error('No estás autorizado para realizar esta acción.');

    $user_id = get_current_user_id();
    $new_value = $_POST[$meta_key];
    
    if ($sanitize_callback) $new_value = $sanitize_callback($new_value);
    if (empty($new_value)) wp_send_json_error($error_msg);
    if ($max_length && strlen($new_value) > $max_length) wp_send_json_error("El campo no puede tener más de $max_length caracteres.");
    
    if (!update_user_meta($user_id, $meta_key, $new_value)) wp_send_json_error("Error al actualizar $meta_key.");
    
    wp_send_json_success("El campo $meta_key ha sido actualizado exitosamente.");
}

function cambiar_nombre() {
    update_user_profile('display_name', 'El nuevo nombre de usuario no puede estar vacío.', 'sanitize_text_field');
}
add_action('wp_ajax_cambiar_nombre', 'cambiar_nombre');

function cambiar_descripcion() {
    update_user_profile('profile_description', 'La descripción no puede estar vacía.', 'sanitize_text_field', 300);
}
add_action('wp_ajax_cambiar_descripcion', 'cambiar_descripcion');

function cambiar_enlace() {
    update_user_profile('user_link', 'El enlace no puede estar vacío.', 'esc_url_raw', 100);
}
add_action('wp_ajax_cambiar_enlace', 'cambiar_enlace');