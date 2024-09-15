<?php

function handle_post_like() {
    guardarLog("handle_post_like() llamada");

    if (!is_user_logged_in()) {
        guardarLog("Usuario no está logueado");
        echo 'not_logged_in';
        wp_die();
    }

    $user_id = get_current_user_id();
    $post_id = $_POST['post_id'] ?? '';
    $like_state = $_POST['like_state'] ?? false;

    guardarLog("Datos recibidos: user_id = $user_id, post_id = $post_id, nonce = $nonce, like_state = $like_state");

    if (empty($post_id)) {
        guardarLog("post_id está vacío");
        echo 'error';
        wp_die();
    }

    $action = $like_state ? 'like' : 'unlike';
    guardarLog("Acción determinada: $action");

    likeAccion($post_id, $user_id, $action);

    $like_count = contarLike($post_id);
    guardarLog("Cantidad de likes después de la acción: $like_count");

    echo $like_count;
    wp_die();
}

function likeAccion($post_id, $user_id, $action) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    if ($action === 'like') {
        if (chequearLike($post_id, $user_id)) {
            $action = 'unlike';
        } else {
            if ($wpdb->insert($table_name, ['user_id' => $user_id, 'post_id' => $post_id])) {
                $autor_id = get_post_field('post_author', $post_id);
                $usuario = get_userdata($user_id);
                insertar_notificacion($autor_id, "{$usuario->display_name} le gustó tu publicación.", get_permalink($post_id), $user_id);
            }
        }
    }

    if ($action === 'unlike') {
        $wpdb->delete($table_name, ['user_id' => $user_id, 'post_id' => $post_id]);
    }
}

function obtenerLikesDelUsuario($user_id)
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

function contarLike($post_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $like_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
        $post_id
    ));

    return $like_count ? $like_count : 0;
}


function chequearLike($post_id, $user_id)
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



function like($post_id)
{
    $user_id = get_current_user_id();
    $like_count = contarLike($post_id);
    $user_has_liked = chequearLike($post_id, $user_id);
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
