<?php

// Funciones de seguimiento movidas desde app/Utils/UserUtils.php

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
            return false; // Ya lo sigue
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
    // Verificar nonce si es necesario para seguridad
    // check_ajax_referer('follow_nonce_action', 'security');

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
    // Verificar nonce si es necesario para seguridad
    // check_ajax_referer('follow_nonce_action', 'security');

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
    // El usuario se sigue a sí mismo al registrarse
    $siguiendo = (array) get_user_meta($user_id, 'siguiendo', true);
    if (!in_array($user_id, $siguiendo)) {
        $siguiendo[] = $user_id;
        update_user_meta($user_id, 'siguiendo', $siguiendo);
    }
    // También se añade a sí mismo como seguidor (opcional, depende de la lógica)
    $seguidores = (array) get_user_meta($user_id, 'seguidores', true);
    if (!in_array($user_id, $seguidores)) {
        $seguidores[] = $user_id;
        update_user_meta($user_id, 'seguidores', $seguidores);
    }
}
add_action('user_register', 'seguir_usuario_automaticamente');

function seguir_usuarios_automaticamente1() {
    // Función para hacer que todos los usuarios existentes se sigan a sí mismos
    // Se ejecuta manualmente o en una migración, no automáticamente en cada carga.
    foreach (get_users() as $usuario) {
        seguir_usuario_automaticamente($usuario->ID);
    }
}

// Nota: La función 'botonseguir' mencionada en la decisión original no existía.
