<?php

function handle_post_like() {
    if (!is_user_logged_in()) {
        wp_send_json_error('not_logged_in');
    }

    $user_id = get_current_user_id();
    $post_id = sanitize_text_field($_POST['post_id'] ?? '');
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    $like_state = filter_var($_POST['like_state'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!wp_verify_nonce($nonce, 'ajax-nonce') || empty($post_id)) {
        wp_send_json_error('error');
    }

    $action = $like_state ? 'like' : 'unlike';

    if (handle_like_action($post_id, $user_id, $action)) {
        wp_send_json_success(get_like_count($post_id));
    } else {
        wp_send_json_error('error');
    }
}

function handle_like_action($post_id, $user_id, $action) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    if ($action === 'like' && !check_user_liked_post($post_id, $user_id)) {
        $result = $wpdb->insert($table_name, ['user_id' => $user_id, 'post_id' => $post_id]);

        if ($result) {
            send_like_notification($post_id, $user_id);
            return true;
        }

        error_log("Error al insertar 'me gusta': " . $wpdb->last_error);
        return false;
    }

    if ($action === 'unlike') {
        $result = $wpdb->delete($table_name, ['user_id' => $user_id, 'post_id' => $post_id]);

        if ($result !== false) {
            return true;
        }

        error_log("Error al eliminar 'me gusta': " . $wpdb->last_error);
    }

    return false;
}

function send_like_notification($post_id, $user_id) {
    $autor_id = get_post_field('post_author', $post_id);
    $usuario = get_userdata($user_id);
    $texto = sprintf('%s le gustó tu publicación.', $usuario->display_name);
    $enlace = get_permalink($post_id);
    insertar_notificacion($autor_id, $texto, $enlace, $user_id);
}

function check_user_liked_post($post_id, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $results = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(1) FROM $table_name WHERE post_id = %d AND user_id = %d",
        $post_id,
        $user_id
    ));

    return $results > 0;
}

function get_like_count($post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
        $post_id
    ));
}

function like($post_id) {
    $user_id = get_current_user_id();
    $like_count = get_like_count($post_id);
    $user_has_liked = check_user_liked_post($post_id, $user_id);
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    ob_start();
    ?>
    <div class="TJKQGJ">
        <button class="post-like-button <?= esc_attr($liked_class) ?>" data-post_id="<?= esc_attr($post_id) ?>" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            <?= $GLOBALS['iconoCorazon']; ?>
        </button>
        <span class="like-count"><?= esc_html($like_count) ?></span>
    </div>
    <?php
    return ob_get_clean();
}

add_action('wp_ajax_nopriv_handle_post_like', 'handle_post_like');
add_action('wp_ajax_handle_post_like', 'handle_post_like');

