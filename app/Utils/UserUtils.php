<?php

function usuarioEsAdminOPro($user_id)
{
    // Verificar que el ID de usuario sea válido
    if (empty($user_id) || !is_numeric($user_id)) {
        ////guardarLog("usuarioEsAdminOPro: Error - ID de usuario inválido.");
        return false;
    }

    // Obtener el objeto usuario
    $user = get_user_by('id', $user_id);

    // Verificar si el usuario existe
    if (!$user) {
        ////guardarLog("usuarioEsAdminOPro: Error - Usuario no encontrado para el ID: " . $user_id);
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($user->roles)) {
        ////guardarLog("usuarioEsAdminOPro: Error - Usuario sin roles asignados. ID: " . $user_id);
        ////guardarLog("usuarioEsAdminOPro: Información del usuario - " . print_r($user, true));
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $user->roles)) {
        ////guardarLog("usuarioEsAdminOPro: Usuario es administrador. ID: " . $user_id);
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($user_id, 'pro', true);
    if (!empty($is_pro)) {
        ////guardarLog("usuarioEsAdminOPro: Usuario tiene la meta 'pro'. ID: " . $user_id);
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    ////guardarLog("usuarioEsAdminOPro: Usuario no es administrador ni tiene la meta 'pro'. ID: " . $user_id);
    return false;
}

function saberSi($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $last_run = get_user_meta($user_id, 'ultima_ejecucion_saber', true);
    $current_time = current_time('timestamp');

    if ($last_run && ($current_time - $last_run < 1)) {
        return; 
    }
    update_user_meta($user_id, 'ultima_ejecucion_saber', $current_time);

    //Saber si le gusta una rola
    $liked_posts = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    if (empty($liked_posts)) {
        update_user_meta($user_id, 'leGustaAlMenosUnaRola', false);
        return;
    }

    $rola_posts = get_posts(array(
        'post__in' => $liked_posts,
        'meta_query' => array(
            array(
                'key' => 'rola',
                'value' => 'true',
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    $le_gusta_rola = !empty($rola_posts);
    update_user_meta($user_id, 'leGustaAlMenosUnaRola', $le_gusta_rola);
}

function obtenerNombreUsuario($usuarioId)
{
    $usuario = get_userdata($usuarioId);

    if ($usuario) {
        return !empty($usuario->display_name) ? $usuario->display_name : $usuario->user_login;
    }

    return 'Usuario desconocido';
}

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

// Funciones de seguimiento movidas desde app/Functions/seguir.php

function get_user_id_from_post($key) {
    return isset($_POST[$key]) ? (int) $_POST[$key] : 0;
}

function update_follow_relationship($follower_id, $followed_id, $action) {
    if (!is_numeric($follower_id) || !is_numeric($followed_id)) {
        return false;
    }

    $following = (array) get_user_meta($follower_id, 'siguiendo', true);
    $followers = (array) get_user_meta($followed_id, 'seguidores', true);

    if ($action === 'follow') {
        if (!in_array($followed_id, $following)) {
            $following[] = $followed_id;
            $followers[] = $follower_id;
        } else {
            return false;
        }
    } elseif ($action === 'unfollow') {
        $following = array_diff($following, [$followed_id]);
        $followers = array_diff($followers, [$follower_id]);
    }

    $update_following = update_user_meta($follower_id, 'siguiendo', array_values($following));
    $update_followers = update_user_meta($followed_id, 'seguidores', array_values($followers));

    return $update_following && $update_followers;
}

function seguir_usuario() {
    $result = update_follow_relationship(
        get_user_id_from_post('seguidor_id'),
        get_user_id_from_post('seguido_id'),
        'follow'
    );

    wp_send_json([
        'success' => $result,
        'message' => $result ? 'Usuario seguido exitosamente' : 'Error al seguir usuario'
    ]);
}
add_action('wp_ajax_seguir_usuario', 'seguir_usuario');

function dejar_de_seguir_usuario() {
    $result = update_follow_relationship(
        get_user_id_from_post('seguidor_id'),
        get_user_id_from_post('seguido_id'),
        'unfollow'
    );

    wp_send_json([
        'success' => $result,
        'message' => $result ? 'Usuario dejado de seguir exitosamente' : 'Error al dejar de seguir usuario'
    ]);
}
add_action('wp_ajax_dejar_de_seguir_usuario', 'dejar_de_seguir_usuario');

function seguir_usuario_automaticamente($user_id) {
    $siguiendo = (array) get_user_meta($user_id, 'siguiendo', true);
    if (!in_array($user_id, $siguiendo)) {
        $siguiendo[] = $user_id;
        update_user_meta($user_id, 'siguiendo', $siguiendo);
    }
}
add_action('user_register', 'seguir_usuario_automaticamente');

function seguir_usuarios_automaticamente1() {
    foreach (get_users() as $usuario) {
        seguir_usuario_automaticamente($usuario->ID);
    }
}

// Nota: La función 'botonseguir' mencionada en la decisión no existía en el archivo original.

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
