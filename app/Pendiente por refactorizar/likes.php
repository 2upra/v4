<?php

function handle_post_like()
{
    if (!is_user_logged_in()) {
        echo 'not_logged_in';
        wp_die();
    }
    $user_id = get_current_user_id();
    $post_id = $_POST['post_id'] ?? '';
    $nonce = $_POST['nonce'] ?? '';
    $like_state = $_POST['like_state'] ?? false;
    if (!wp_verify_nonce($nonce, 'ajax-nonce') || empty($post_id)) {
        echo 'error';
        wp_die();
    }
    handle_like_action($post_id, $user_id, $like_state ? 'like' : 'unlike');
    echo get_like_count($post_id);
    wp_die();
}

function handle_like_action($post_id, $user_id, $action)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    if ($action === 'like' && !check_user_liked_post($post_id, $user_id)) {
        $result = $wpdb->insert($table_name, ['user_id' => $user_id, 'post_id' => $post_id]);
        if ($result) {
            $autor_id = get_post_field('post_author', $post_id);
            $nombre_usuario = get_userdata($user_id)->display_name;
            insertar_notificacion($autor_id, "$nombre_usuario le gustó tu publicación.", get_permalink($post_id), $user_id);
        } else {
            error_log("Error al insertar 'me gusta': " . $wpdb->last_error);
        }
    } elseif ($action === 'unlike') {
        $result = $wpdb->delete($table_name, ['user_id' => $user_id, 'post_id' => $post_id]);
        if (!$result) {
            error_log("Error al eliminar 'me gusta': " . $wpdb->last_error);
        }
    }
}

function get_user_liked_post_ids($user_id)
{
    global $wpdb;
    return $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}post_likes WHERE user_id = %d", $user_id)) ?: [];
}

function check_user_liked_post($post_id, $user_id)
{
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM {$wpdb->prefix}post_likes WHERE post_id = %d AND user_id = %d", $post_id, $user_id)) > 0;
}

function get_like_count($post_id)
{
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}post_likes WHERE post_id = %d", $post_id)) ?: 0;
}

function like($post_id)
{
    $user_id = get_current_user_id();
    $liked_class = check_user_liked_post($post_id, $user_id) ? 'liked' : 'not-liked';
    ob_start();
?>
    <div class="TJKQGJ">
        <button class="post-like-button <?= esc_attr($liked_class) ?>" data-post_id="<?= esc_attr($post_id) ?>" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            <?php echo $GLOBALS['iconoCorazon']; ?>
        </button>
        <span class="like-count"><?= esc_html(get_like_count($post_id)) ?></span>
    </div>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_nopriv_handle_post_like', 'handle_post_like');
add_action('wp_ajax_handle_post_like', 'handle_post_like');

function enqueue_likes_script()
{
    enqueue_and_localize_scripts('likes', '/js/likes.js', ['jquery'], '2.1', true, 'ajax_var_likes', 'ajax-nonce');
}
