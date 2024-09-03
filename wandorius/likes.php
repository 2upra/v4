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

    if (!wp_verify_nonce($nonce, 'ajax-nonce')) {
        echo 'error';
        wp_die();
    }

    if (empty($post_id)) {
        echo 'error';
        wp_die();
    }

    $action = $like_state ? 'like' : 'unlike';

    handle_like_action($post_id, $user_id, $action);
    echo get_like_count($post_id);
    wp_die();
}

function handle_like_action($post_id, $user_id, $action)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    if ($action === 'like') {
        if (check_user_liked_post($post_id, $user_id)) {
            $action = 'unlike';
        } else {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'post_id' => $post_id,
                )
            );

            if ($result) {
                $autor_id = get_post_field('post_author', $post_id);
                $usuario = get_userdata($user_id);
                $nombre_usuario = $usuario->display_name;
                $texto = "$nombre_usuario le gustó tu publicación.";
                $enlace = get_permalink($post_id);
                insertar_notificacion($autor_id, $texto, $enlace, $user_id);
            } else {
                error_log("Error al insertar 'me gusta': " . $wpdb->last_error);
            }
        }
    }

    if ($action === 'unlike') {
        $result = $wpdb->delete(
            $table_name,
            array(
                'user_id' => $user_id,
                'post_id' => $post_id,
            )
        );

        if (!$result) {
            error_log("Error al eliminar 'me gusta': " . $wpdb->last_error);
        }
    }
}

function get_user_liked_post_ids($user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $liked_posts = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    if (empty($liked_posts)) {
        return array();
    }

    return $liked_posts;
}


function check_user_liked_post($post_id, $user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $results = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(1) FROM $table_name WHERE post_id = %d AND user_id = %d",
        $post_id,
        $user_id
    ));

    return $results > 0;
}

function get_like_count($post_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $like_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
        $post_id
    ));

    return $like_count ? $like_count : 0;
}

function like($post_id)
{
    $user_id = get_current_user_id();
    $like_count = get_like_count($post_id);
    $user_has_liked = check_user_liked_post($post_id, $user_id);
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    ob_start();
?>
    <div class="TJKQGJ">
        <button class="post-like-button <?= esc_attr($liked_class) ?>" data-post_id="<?= esc_attr($post_id) ?>" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            <?php echo $GLOBALS['iconoCorazon']; ?>
        </button>
        <span class="like-count"><?= esc_html($like_count) ?></span>
    </div>
<?php
    $output = ob_get_clean();
    return $output;
}


add_action('wp_ajax_nopriv_handle_post_like', 'handle_post_like');
add_action('wp_ajax_handle_post_like', 'handle_post_like');

function enqueue_likes_script()
{
    enqueue_and_localize_scripts('likes', '/js/likes.js', ['jquery'], '2.1', true, 'ajax_var_likes', 'ajax-nonce');
}
