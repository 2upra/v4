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

// Refactor(Org): Moved pinky-related functions and hooks to EconomyService.php

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

// Refactor(Org): Funcion ajustarZonaHoraria() y hooks AJAX movidos desde app/Utils/DateUtils.php
function ajustarZonaHoraria()
{
    $zona_horaria = isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC';
    // Asegurarse de que la zona horaria es válida antes de usarla
    try {
        new DateTimeZone($zona_horaria);
    } catch (Exception $e) {
        $zona_horaria = 'UTC'; // Volver a UTC si no es válida
    }
    setcookie('usuario_zona_horaria', $zona_horaria, time() + 86400, '/');
    wp_die();
}
add_action('wp_ajax_ajustar_zona_horaria', 'ajustarZonaHoraria');
add_action('wp_ajax_nopriv_ajustar_zona_horaria', 'ajustarZonaHoraria');

// Refactor(Org): Función cambiar_imagen_perfil() y hook AJAX movidos desde app/Perfiles/configuracion.php
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

// Refactor(Org): Función handle_info_usuario() movida desde app/Sync/api.php
function handle_info_usuario(WP_REST_Request $request)
{
    $receptor = intval($request->get_param('receptor'));

    if ($receptor <= 0) {
        return new WP_Error('invalid_receptor', 'ID del receptor inválido.', array('status' => 400));
    }

    // Dependencias: imagenPerfil() y obtenerNombreUsuario() deben estar disponibles (ej: en app/Helpers/UserHelper.php)
    $imagenPerfil = imagenPerfil($receptor) ?: 'ruta_por_defecto.jpg';
    $nombreUsuario = obtenerNombreUsuario($receptor) ?: 'Usuario Desconocido';

    return array(
        'imagenPerfil' => $imagenPerfil,
        'nombreUsuario' => $nombreUsuario,
    );
}

// Refactor(Org): Ruta REST /infoUsuario movida desde app/Sync/api.php
add_action('rest_api_init', function () {
    register_rest_route('1/v1',  '/infoUsuario', array(
        'methods' => 'POST',
        'callback' => 'handle_info_usuario',
        // Dependencia: chequearElectron() debe estar disponible globalmente (ej: en app/Sync/api.php)
        'permission_callback' => 'chequearElectron',
    ));
});

// Refactor(Org): Moved user blocking/restriction functions from app/Misc/Emergencias.php
function bloquear_y_eliminar_usuarios($usuarios) {
    foreach ($usuarios as $usuario) {
        $user = null;
        
        // Determinar el tipo de identificación del usuario
        if (is_numeric($usuario)) {
            $user = get_user_by('id', $usuario);
        } elseif (filter_var($usuario, FILTER_VALIDATE_IP)) {
            // Note: get_user_by('ip') is not a standard WordPress function.
            // This logic might need adjustment based on how IP is associated with users.
            // Assuming IP might be stored in user meta or logs, or this is intended for IP blocking only.
            // For now, we'll proceed assuming it's primarily for IP blocking via bloquear_ip().
        } elseif (is_email($usuario)) {
            $user = get_user_by('email', $usuario);
        } else {
            $user = get_user_by('login', $usuario);
        }

        if ($user) {
            // Eliminar comentarios del usuario
            $comments = get_comments(array('user_id' => $user->ID));
            foreach ($comments as $comment) {
                wp_delete_comment($comment->comment_ID, true);
            }

            // Eliminar posts del usuario
            $posts = get_posts(array(
                'author' => $user->ID,
                'post_type' => 'any',
                'numberposts' => -1
            ));
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }

            // Bloquear usuario (Set role to something non-functional or use a specific 'blocked' role if defined)
            // Using 'restringido' role as per agregar_rol_restringido() in Emergencias.php
            wp_update_user(array('ID' => $user->ID, 'role' => 'restringido')); 
            // wp_update_user(array('ID' => $user->ID, 'user_status' => 1)); // user_status is deprecated

            // Block user's IP if it was provided and is the identifier
            if (filter_var($usuario, FILTER_VALIDATE_IP) && $usuario === $_SERVER['REMOTE_ADDR']) { // Check if the identifier is the current IP
                 // Or retrieve the user's last known IP if stored elsewhere
                 $user_ip = get_user_meta($user->ID, 'last_login_ip', true); // Example meta key
                 if ($user_ip) {
                     bloquear_ip($user_ip);
                 }
            }
        } else {
            // Si no se encuentra el usuario pero es una IP válida, bloquearla
            if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                bloquear_ip($usuario);
            } else {
                error_log("No se pudo encontrar o bloquear al usuario: $usuario");
            }
        }
    }
}

// Refactor(Org): Moved banearUsuario() function and hook from app/Misc/Emergencias.php
function banearUsuario() {

    if (!current_user_can('administrator')) {
        wp_send_json_error('No tienes permisos para realizar esta acción.');
        wp_die();
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'restringir_autor_nonce')) {
        wp_send_json_error('Nonce no válido.');
        wp_die();
    }

    if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
        wp_send_json_error('No se proporcionó un post_id.');
        wp_die();
    }

    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error('El post no existe.');
        wp_die();
    }
    $autor_id = $post->post_author;
    // Call restringir_usuario with the author ID
    restringir_usuario([$autor_id]); // Pass as an array
    wp_send_json_success('El autor del post ha sido restringido correctamente.');
    wp_die(); 
}
add_action('wp_ajax_banearUsuario', 'banearUsuario');

// Refactor(Org): Moved restringir_usuario() function from app/Misc/Emergencias.php
function restringir_usuario($usuarios) { // Expects an array of user identifiers
    foreach ($usuarios as $usuario) {
        $user = null;
        if (is_numeric($usuario)) {
            $user = get_user_by('id', $usuario);
        } elseif (filter_var($usuario, FILTER_VALIDATE_IP)) {
            // Cannot reliably get user by IP unless stored
        } elseif (is_email($usuario)) {
            $user = get_user_by('email', $usuario);
        } else {
            $user = get_user_by('login', $usuario);
        }

        if ($user) {
            if (in_array('administrator', $user->roles) || $user->ID == 1) {
                error_log("No se puede restringir al administrador o al usuario con ID 1: {$user->user_login}");
                continue;
            }
            // Use 'restringido' role as defined in Emergencias.php
            wp_update_user(array('ID' => $user->ID, 'role' => 'restringido'));
            // wp_update_user(array('ID' => $user->ID, 'user_status' => 1)); // Deprecated
            
            // Block IP if identifier was IP (less common for restriction)
            if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                 // Maybe block last known IP?
                 $user_ip = get_user_meta($user->ID, 'last_login_ip', true); // Example
                 if ($user_ip) {
                     bloquear_ip($user_ip);
                 }
            }
        } else {
            if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                bloquear_ip($usuario); 
            } else {
                error_log("No se pudo encontrar o restringir al usuario: $usuario");
            }
        }
    }
}

// Refactor(Org): Moved bloquear_ip() function from app/Misc/Emergencias.php
function bloquear_ip($ip) {
    // Basic validation
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        error_log("Intento de bloquear una IP inválida: $ip");
        return;
    }

    $htaccess = ABSPATH . '.htaccess'; // Use ABSPATH constant
    $deny = "\n# Bloqueo de IP - " . date('Y-m-d H:i:s') . "\nDeny from $ip\n";
    
    // Check if .htaccess exists and is writable
    if (file_exists($htaccess) && is_writable($htaccess)) {
        // Check if IP is already blocked to avoid duplicates
        $content = file_get_contents($htaccess);
        if (strpos($content, "Deny from $ip") === false) {
            file_put_contents($htaccess, $deny, FILE_APPEND | LOCK_EX); // Add lock
        } else {
            error_log("IP ya bloqueada en .htaccess: $ip");
        }
    } else {
        error_log("No se pudo bloquear la IP $ip. El archivo .htaccess no existe o no tiene permisos de escritura.");
        // Fallback: Consider logging or alternative blocking methods if needed
    }
}

// Refactor(Org): Moved function restringir_acceso_admin() and hook from app/Misc/Emergencias.php
function restringir_acceso_admin() {
    $user = wp_get_current_user();
    // IMPORTANT: Define your actual allowed IP(s) or logic here
    $allowed_ips = ['YOUR_PRIMARY_ADMIN_IP', 'ANOTHER_ALLOWED_IP']; // Example: Use an array for multiple IPs
    $allowed_user_id = 1; // Typically the first admin user

    $current_ip = $_SERVER['REMOTE_ADDR'];
    $is_allowed_user = ($user && $user->ID === $allowed_user_id);
    $is_allowed_ip = in_array($current_ip, $allowed_ips);

    // Deny access if the user is NOT the specific allowed user AND the IP is NOT in the allowed list
    if (!$is_allowed_user && !$is_allowed_ip) {
        // Optional: More granular checks like capability
        // if ($user && !current_user_can('manage_options')) { 
        //     wp_die('Acceso denegado. Permisos insuficientes.', 'Acceso Denegado', ['response' => 403]);
        // }
        wp_die('Acceso denegado. Contacte al administrador.', 'Acceso Denegado', ['response' => 403]);
    }
}
add_action('admin_init', 'restringir_acceso_admin');

?>
