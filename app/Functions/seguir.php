<?

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

add_shortcode('mostrar_contadores', function() {
    $user_id = get_current_user_id();
    $seguidores = count((array) get_user_meta($user_id, 'seguidores', true));
    $siguiendo = count((array) get_user_meta($user_id, 'siguiendo', true));
    $posts_count = (new WP_Query(['author' => $user_id, 'post_type' => 'social_post']))->found_posts;

    return "{$seguidores} seguidores {$siguiendo} seguidos {$posts_count} posts";
});

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

