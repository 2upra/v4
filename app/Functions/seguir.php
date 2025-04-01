<?php

add_shortcode('mostrar_contadores', function() {
    $user_id = get_current_user_id();
    $seguidores = count((array) get_user_meta($user_id, 'seguidores', true));
    $siguiendo = count((array) get_user_meta($user_id, 'siguiendo', true));
    $posts_count = (new WP_Query(['author' => $user_id, 'post_type' => 'social_post']))->found_posts;

    return "{$seguidores} seguidores {$siguiendo} seguidos {$posts_count} posts";
});



