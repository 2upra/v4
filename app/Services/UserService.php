<?php
// File created to consolidate user profile update AJAX handlers

// Moved from app/Perfiles/configuracion.php
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

// Moved from app/Perfiles/configuracion.php
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

// Moved from app/Perfiles/configuracion.php
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

// Moved from app/Perfiles/perfiles.php
function save_profile_description_ajax() {
    $idUsuario = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $desc = isset($_POST['profile_description']) ? sanitize_textarea_field($_POST['profile_description']) : ''; // Usar sanitize_textarea_field

    // Verificar nonce aquí sería una buena práctica de seguridad
    // check_ajax_referer('tu_nonce_action', 'security');

    if ($idUsuario && current_user_can('edit_user', $idUsuario)) {
        if (update_user_meta($idUsuario, 'profile_description', $desc)) {
             wp_send_json_success('Descripción actualizada.'); // Mejor usar wp_send_json_*
        } else {
             wp_send_json_error('Error al actualizar o valor sin cambios.');
        }
    } else {
        wp_send_json_error('Permiso denegado.');
    }
    // wp_die() es llamado automáticamente por wp_send_json_*
}
add_action('wp_ajax_save_profile_description', 'save_profile_description_ajax');
// Si necesitas que funcione para usuarios no logueados (poco probable aquí):
// add_action('wp_ajax_nopriv_save_profile_description', 'save_profile_description_ajax');

?>