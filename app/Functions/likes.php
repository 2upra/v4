<?

function manejarLike() {
    if (!is_user_logged_in()) {
        echo 'not_logged_in';
        wp_die();
    }

    $userId = get_current_user_id();
    $postId = $_POST['post_id'] ?? '';
    $likeEstado = $_POST['like_state'] ?? false;


    if (empty($postId)) {
        echo 'error';
        wp_die();
    }

    $accion = $likeEstado ? 'like' : 'unlike';
    likeAccion($postId, $userId, $accion);
    $contadorLike = contarLike($postId);
    echo $contadorLike;
    wp_die();
}

add_action('wp_ajax_like', 'manejarLike');


function likeAccion($postId, $userId, $accion) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    if ($accion === 'like') {
        if (chequearLike($postId, $userId)) {
            $accion = 'unlike';  
        } else {
            $insert_result = $wpdb->insert($table_name, ['user_id' => $userId, 'post_id' => $postId]);
            if ($insert_result === false) {
            } else {
                
                $autorId = get_post_field('post_author', $postId);
                
                if ($autorId != $userId) {
                    $usuario = get_userdata($userId);
                    
                }
            }
        }
    }

    if ($accion === 'unlike') {
        $delete_result = $wpdb->delete($table_name, ['user_id' => $userId, 'post_id' => $postId]);
        if ($delete_result === false) {
        } else {
        }
    }
}


function obtenerLikesDelUsuario($userId, $limit = 500)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $query = $wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d ORDER BY like_date DESC LIMIT %d",
        $userId, $limit
    );
    $liked_posts = $wpdb->get_col($query);
    if (empty($liked_posts)) {
        return [];
    }
    return $liked_posts;
}

function contarLike($postId) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $contadorLike = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
        $postId
    ));

    return $contadorLike ? $contadorLike : 0;
}

function chequearLike($postId, $userId) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $results = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(1) FROM $table_name WHERE post_id = %d AND user_id = %d",
        $postId,
        $userId
    ));

    return $results > 0;
}




function like($postId)
{
    $userId = get_current_user_id();
    $contadorLike = contarLike($postId);
    $user_has_liked = chequearLike($postId, $userId);
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    ob_start();
?>
    <div class="TJKQGJ botonlike">
        <button class="post-like-button <?= esc_attr($liked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            <? echo $GLOBALS['iconoCorazon']; ?>
        </button>
        <span class="like-count"><?= esc_html($contadorLike) ?></span>
    </div>
<?
    $output = ob_get_clean();
    return $output;
}