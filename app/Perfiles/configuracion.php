<?

function config()
{
    $u = wp_get_current_user();
    $uid = $u->ID;
    $desc = get_user_meta($uid, 'profile_description', true);
    $link = get_user_meta($uid, 'user_link', true);
    $tipo = get_user_meta($uid, 'tipoUsuario', true);

    ob_start();
?>
    <div class="LEDDCN modal" id="modalConfig" style="display: none;">
        <p class="ONDNYU">Configuración de Perfil</p>
        <form class="PVSHOT">
            <div class="PTORKC">
                <div class="previewAreaArchivos" id="previewAreaImagenPerfil">Arrastra tu foto de perfil
                    <label></label>
                </div>
                <input type="file" id="profilePicture" accept="image/*" style="display:none;">
            </div>
            <div class="PTORKC">
                <label for="nombreUsuario">Nombre de Usuario:</label>
                <input type="text" id="nombreUsuario" name="nombreUsuario" value="<?= esc_attr($u->user_login) ?>">
            </div>
            <div class="PTORKC">
                <label for="username">Nombre:</label>
                <input type="text" id="username" name="username" value="<?= esc_attr($u->display_name) ?>">
            </div>
            <div class="PTORKC">
                <label for="description">Descripción:</label>
                <textarea id="description" name="description" rows="2"><?= esc_attr($desc) ?></textarea>
            </div>
            <div class="PTORKC">
                <label for="link">Enlace:</label>
                <input type="url" id="link" name="link" placeholder="Ingresa un enlace (opcional)" value="<?= esc_attr($link) ?>">
            </div>
            <div class="PTORKC ADGOR3">
                <label for="typeUser">Tipo de usuario:</label>
                <div class="DRHMDE">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="fanTipoCheck" name="fanTipoCheck" value="1" <?= $tipo === 'Fan' ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Fan
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" id="artistaTipoCheck" name="artistaTipoCheck" value="1" <?= $tipo === 'Artista' ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Artista
                    </label>
                </div>
            </div>
        </form>
        <button class="guardarConfig">Guardar cambios</button>
    </div>
<?
    return ob_get_clean();
}

function cambiarImgPerfil()
{
    $uid = get_current_user_id();

    if (isset($_FILES['file']) && $uid > 0) {
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['error' => 'Error en la subida del archivo.']);
            return;
        }
        $prevAttId = get_user_meta($uid, 'imagen_perfil_id', true);
        $uInfo = get_userdata($uid);
        $uname = $uInfo->user_login;
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = $uname . '_' . time() . '.' . $ext;

        add_filter('wp_handle_upload_prefilter', function ($file) use ($newFilename) {
            $file['name'] = $newFilename;
            return $file;
        });

        $upload = wp_handle_upload($file, ['test_form' => false]);

        if ($upload && !isset($upload['error'])) {
            $att = [
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($newFilename),
                'post_content' => '',
                'post_status' => 'inherit'
            ];
            $attId = wp_insert_attachment($att, $upload['file']);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attData = wp_generate_attachment_metadata($attId, $upload['file']);
            wp_update_attachment_metadata($attId, $attData);
            update_user_meta($uid, 'imagen_perfil_id', $attId);
            $urlImgPerfil = wp_get_attachment_url($attId);

            if ($prevAttId) {
                wp_delete_attachment($prevAttId, true);
            }

            wp_send_json_success(['url_imagen_perfil' => esc_url($urlImgPerfil)]);
        } else {
            wp_send_json_error(['error' => $upload['error']]);
        }
    } else {
        wp_send_json_error(['error' => 'No se pudo subir la imagen.']);
    }
}
add_action('wp_ajax_cambiar_imagen_perfil', 'cambiarImgPerfil');

function cambiarUsername()
{
    $logMsg = "cambiarUsername: ";
    if (!is_user_logged_in()) {
        error_log($logMsg . "Usuario no logueado");
        wp_send_json_error('No autorizado.');
        exit;
    }
    $uid = get_current_user_id();
    $newUsername = sanitize_text_field($_POST['new_username']);
    error_log($logMsg . "Nuevo Username: " . $newUsername);

    if (empty($newUsername)) {
        error_log($logMsg . "Username vacío");
        wp_send_json_error('Username vacío.');
        exit;
    }
    if (username_exists($newUsername)) {
        error_log($logMsg . "Username ya existe");
        wp_send_json_error('Username ya en uso.');
        exit;
    }

    $result = wp_update_user([
        'ID' => $uid,
        'user_login' => $newUsername
    ]);
    error_log($logMsg . "wp_update_user Result: " . print_r($result, true));


    if (is_wp_error($result)) {
        error_log($logMsg . "Error al actualizar: " . $result->get_error_message());
        wp_send_json_error('Error al actualizar.');
        exit;
    }

    wp_send_json_success('Username cambiado.');
}
add_action('wp_ajax_cambiar_username', 'cambiarUsername');

function cambiarNombre()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('No autorizado.');
        return;
    }
    $uid = get_current_user_id();
    $newName = sanitize_text_field($_POST['new_username']);

    if (empty($newName)) {
        wp_send_json_error('Nombre vacío.');
        return;
    }

    $result =  wp_update_user(['ID' => $uid, 'display_name' => $newName]);

    if (is_wp_error($result)) {
        wp_send_json_error('Error al actualizar.');
        return;
    }
    wp_send_json_success('Nombre cambiado.');
}
add_action('wp_ajax_cambiar_nombre', 'cambiarNombre');

function cambiarDesc()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('No autorizado.');
        return;
    }

    $uid = get_current_user_id();
    $newDesc = sanitize_text_field($_POST['new_description']);

    if (strlen($newDesc) > 300) {
        $newDesc = substr($newDesc, 0, 300);
    }

    $updated = update_user_meta($uid, 'profile_description', $newDesc);

    if (!$updated) {
        wp_send_json_error('Error al actualizar.');
        return;
    }
    wp_send_json_success('Descripción actualizada.');
}
add_action('wp_ajax_cambiar_descripcion', 'cambiarDesc');

function cambiarEnlace()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('No autorizado.');
        return;
    }

    $uid = get_current_user_id();
    $newLink = esc_url_raw($_POST['new_link']);

    if (strlen($newLink) > 100) {
        wp_send_json_error('Enlace muy largo.');
        return;
    }

    $updated = update_user_meta($uid, 'user_link', $newLink);

    if (!$updated) {
        wp_send_json_error('Error al actualizar.');
        return;
    }
    wp_send_json_success('Enlace actualizado.');
}
add_action('wp_ajax_cambiar_enlace', 'cambiarEnlace');
