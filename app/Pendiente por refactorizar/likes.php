<?php

function handle_post_like() {
    if (!is_user_logged_in() || !wp_verify_nonce($_POST['nonce'] ?? '', 'ajax-nonce') || empty($_POST['post_id'])) {
        wp_send_json_error('Invalid request');
    }

    $user_id = get_current_user_id();
    $post_id = $_POST['post_id'];
    $action = ($_POST['like_state'] ?? false) ? 'like' : 'unlike';

    handle_like_action($post_id, $user_id, $action);
    wp_send_json_success(get_like_count($post_id));
}

function handle_like_action($post_id, $user_id, $action) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    if ($action === 'like' && !check_user_liked_post($post_id, $user_id)) {
        $result = $wpdb->insert($table_name, ['user_id' => $user_id, 'post_id' => $post_id]);
        if ($result) {
            notify_author($post_id, $user_id);
        }
    } elseif ($action === 'unlike') {
        $wpdb->delete($table_name, ['user_id' => $user_id, 'post_id' => $post_id]);
    }
}

function notify_author($post_id, $liker_id) {
    $author_id = get_post_field('post_author', $post_id);
    $liker_name = get_userdata($liker_id)->display_name;
    $message = "$liker_name le gustó tu publicación.";
    $link = get_permalink($post_id);
    insertar_notificacion($author_id, $message, $link, $liker_id);
}

function check_user_liked_post($post_id, $user_id) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(1) FROM {$wpdb->prefix}post_likes WHERE post_id = %d AND user_id = %d",
        $post_id, $user_id
    )) > 0;
}

function get_like_count($post_id) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}post_likes WHERE post_id = %d",
        $post_id
    )) ?: 0;
}

function like($post_id) {
    $user_id = get_current_user_id();
    $liked_class = check_user_liked_post($post_id, $user_id) ? 'liked' : 'not-liked';
    
    return sprintf(
        '<div class="TJKQGJ">
            <button class="post-like-button %s" data-post_id="%s" data-nonce="%s">
                %s
            </button>
            <span class="like-count">%s</span>
        </div>',
        esc_attr($liked_class),
        esc_attr($post_id),
        wp_create_nonce('like_post_nonce'),
        $GLOBALS['iconoCorazon'],
        esc_html(get_like_count($post_id))
    );
}

add_action('wp_ajax_handle_post_like', 'handle_post_like');
add_action('wp_ajax_nopriv_handle_post_like', 'handle_post_like');

function enqueue_likes_script() {
    enqueue_and_localize_scripts('likes', '/js/likes.js', ['jquery'], '2.1', true, 'ajax_var_likes', 'ajax-nonce');
}