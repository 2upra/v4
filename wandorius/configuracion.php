<?php

function config()
{
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_name = $current_user->display_name;
    $descripcion = get_user_meta($user_id, 'profile_description', true);
    $linkUser = get_user_meta($user_id, 'user_link', true);
    ob_start();

?>

    <div class="LEDDCN">
        <p class="ONDNYU">Configuración de Perfil</p>

        <form class="PVSHOT">

            <!-- Cambiar foto de perfil -->
            <div class="PTORKC">
                <div class="previewAreaArchivos" id="previewAreaImagenPerfil">Arrastra tu foto de perfil
                    <label></label>
                </div>
                <input type="file" id="profilePicture" accept="image/*" style="display:none;">
            </div>

            <!-- Cambiar nombre de usuario -->
            <div class="PTORKC">
                <label for="username">Nombre de Usuario:</label>
                <input type="text" id="username" name="username" value="<?php echo esc_attr($user_name); ?>">
            </div>

            <!-- Cambiar descripción -->
            <div class="PTORKC">
                <label for="description">Descripción:</label>
                <textarea id="description" name="description" rows="2"><?php echo esc_attr($descripcion); ?></textarea>
            </div>

            <!-- Agregar un enlace -->
            <div class="PTORKC">
                <label for="link">Enlace:</label>
                <input type="url" id="link" name="link" placeholder="Ingresa un enlace (opcional)" value="<?php echo esc_attr($linkUser); ?>">
            </div>

        </form>
    </div>
<?php

    return ob_get_clean();
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
function cambiar_nombre()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('No estás autorizado para realizar esta acción.');
        exit;
    }
    $user_id = get_current_user_id();
    $new_username = sanitize_text_field($_POST['new_username']);

    if (empty($new_username)) {
        wp_send_json_error('El nuevo nombre de usuario no puede estar vacío.');
        exit;
    }
    if (username_exists($new_username)) {
        wp_send_json_error('El nombre de usuario ya está en uso.');
        exit;
    }
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $new_username,
    ]);
    if (is_wp_error($user_id)) {
        wp_send_json_error('Error al actualizar el nombre de usuario.');
        exit;
    }
    wp_send_json_success('El nombre de usuario ha sido cambiado exitosamente.');
}
add_action('wp_ajax_cambiar_nombre', 'cambiar_nombre');

function cambiar_descripcion()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('No estás autorizado para realizar esta acción.');
        exit;
    }

    $user_id = get_current_user_id();
    $new_description = sanitize_text_field($_POST['new_description']);

    if (empty($new_description)) {
        wp_send_json_error('La descripción no puede estar vacía.');
        exit;
    }

    if (strlen($new_description) > 300) {
        $new_description = substr($new_description, 0, 300);
    }

    $updated = update_user_meta($user_id, 'profile_description', $new_description);

    if (!$updated) {
        wp_send_json_error('Error al actualizar la descripción.');
        exit;
    }

    wp_send_json_success('La descripción ha sido actualizada exitosamente.');
}
add_action('wp_ajax_cambiar_descripcion', 'cambiar_descripcion');
function cambiar_enlace()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('No estás autorizado para realizar esta acción.');
        exit;
    }

    $user_id = get_current_user_id();
    $new_link = esc_url_raw($_POST['new_link']);

    if (empty($new_link)) {
        wp_send_json_error('El enlace no puede estar vacío.');
        exit;
    }

    if (strlen($new_link) > 100) {
        wp_send_json_error('El enlace no puede tener más de 200 caracteres.');
        exit;
    }

    $updated = update_user_meta($user_id, 'user_link', $new_link);

    if (!$updated) {
        wp_send_json_error('Error al actualizar el enlace.');
        exit;
    }

    wp_send_json_success('El enlace ha sido actualizado exitosamente.');
}
add_action('wp_ajax_cambiar_enlace', 'cambiar_enlace');
