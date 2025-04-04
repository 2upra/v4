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

// Refactor(Org): Moved guardarBloqueo function and AJAX hook from UserUtils.php
function guardarBloqueo() {
    global $wpdb;
    $tabla_bloqueo = $wpdb->prefix . 'bloqueo';
    $usuario_actual = get_current_user_id();
    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    
    if (!$post) {
        wp_send_json_error('Post no encontrado.');
        return;
    }
    
    $autor_id = $post->post_author;

    // Verificar si el autor es un administrador
    if (user_can($autor_id, 'administrator')) {
        wp_send_json_error('Pero que haces boludo?');
        return;
    }
    
    $bloqueo_existente = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $tabla_bloqueo WHERE idUser = %d AND idBloqueado = %d",
        $usuario_actual,
        $autor_id
    ));
    
    if ($bloqueo_existente) {
        $wpdb->delete($tabla_bloqueo, array(
            'idUser' => $usuario_actual,
            'idBloqueado' => $autor_id
        ));
        wp_send_json_success('Usuario desbloqueado.');
    } else {
        $wpdb->insert($tabla_bloqueo, array(
            'idUser' => $usuario_actual,
            'idBloqueado' => $autor_id
        ));
        wp_send_json_success('Usuario bloqueado.');
    }
}
add_action('wp_ajax_guardarBloqueo', 'guardarBloqueo');

// Refactor(Org): Moved Pinkys and User Type logic from UserUtils.php

// Funciones de manejo de 'pinkys' movidas desde app/Functions/pinkys.php

function agregarPinkys($userID, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
    $nuevas_monedas = $monedas_actuales + $cantidad;
    update_user_meta($userID, 'pinky', $nuevas_monedas);
}

function restarPinkys($userID, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
    $nuevas_monedas = $monedas_actuales - $cantidad;
    update_user_meta($userID, 'pinky', $nuevas_monedas);
}

function restarPinkysEliminacion($postID)
{
    $post = get_post($postID);
    $userID = $post->post_author;

    if ($userID) {
        restarPinkys($userID, 1);
    }
}

function pinkysRegistro($user_id)
{
    $pinkys_iniciales = 10;
    update_user_meta($user_id, 'pinky', $pinkys_iniciales);
}
add_action('user_register', 'pinkysRegistro');

function restablecerPinkys()
{
    $usuarios_query = new WP_User_Query(array(
        'fields' => 'ID',
    ));

    if (!empty($usuarios_query->results)) {
        foreach ($usuarios_query->results as $userID) {
            $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
            if ($monedas_actuales < 10) {
                update_user_meta($userID, 'pinky', 10);
            }
        }
    }
}
add_action('restablecer_pinkys_semanal', 'restablecerPinkys');


if (!wp_next_scheduled('restablecer_pinkys_semanal')) {
    wp_schedule_event(time(), 'weekly', 'restablecer_pinkys_semanal');
}

// Funciones movidas desde app/Functions/cambiarTipoUser.php

function cambiar_tipo_usuario_callback()
{
    $user_id = get_current_user_id();
    $tipo = $_POST['tipo'];

    if ($tipo === 'fan') {
        $estado_actual = get_user_meta($user_id, 'fan', true);
        update_user_meta($user_id, 'fan', !$estado_actual);
    }

    echo !$estado_actual;
    wp_die();
}

add_action('wp_ajax_cambiar_tipo_usuario', 'cambiar_tipo_usuario_callback');
add_action('wp_ajax_nopriv_cambiar_tipo_usuario', 'cambiar_tipo_usuario_callback');

// Función movida desde app/View/InicialModal.php
function guardarTipoUsuario()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión para realizar esta acción.');
    }
    $tipoUsuario = isset($_POST['tipoUsuario']) ? sanitize_text_field($_POST['tipoUsuario']) : '';
    if (empty($tipoUsuario)) {
        wp_send_json_error('No se recibió el tipo de usuario.');
    }
    $userId = get_current_user_id();
    reiniciarFeed($userId); // Asegúrate de que esta función esté disponible globalmente o incluida.
    update_user_meta($userId, 'tipoUsuario', $tipoUsuario);
    wp_send_json_success('El tipo de usuario ha sido guardado.');
}
add_action('wp_ajax_guardarTipoUsuario', 'guardarTipoUsuario');

// Refactor(Org): Moved function obtenerInteresesUsuario from app/Content/Logic/datosParaCalculo.php
function obtenerInteresesUsuario($userId) {
    global $wpdb;
    $tiempoInicio = microtime(true);
    $tablaIntereses = INTERES_TABLE;
    $intereses = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $tablaIntereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);
    if ($wpdb->last_error) {
        //guardarLog("[obtenerInteresesUsuario] Error: Fallo al obtener intereses del usuario: " . $wpdb->last_error);
    }
    //rendimientolog("[obtenerInteresesUsuario] Tiempo para obtener 'intereses': " . (microtime(true) - $tiempoInicio) . " segundos");
    return $intereses;
}

// Refactor(Org): Funcion guardarGenerosUsuario() y hook AJAX movidos desde app/View/InicialModal.php
function guardarGenerosUsuario()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión para realizar esta acción.');
    }
    $generos = isset($_POST['generos']) ? explode(',', $_POST['generos']) : array();

    if (empty($generos) || !is_array($generos)) {
        wp_send_json_error('No se recibieron géneros seleccionados.');
    }

    $generos_sanitizados = array_map('sanitize_text_field', $generos);
    $userId = get_current_user_id();
    update_user_meta($userId, 'usuarioPreferencias', $generos_sanitizados);
    wp_send_json_success('Los géneros han sido guardados.');
}
add_action('wp_ajax_guardarGenerosUsuario', 'guardarGenerosUsuario');

?>
