<?

function manejarLike()
{
    // Log detallado 1: Inicio de la función con información del like (si está disponible)
    $likeTypeParaLog = $_POST['like_type'] ?? 'like';
    error_log('[manejarLike] Iniciando función para acción de like tipo: ' . $likeTypeParaLog);

    $response = array('success' => false);

    if (!is_user_logged_in()) {
        $response['error'] = 'not_logged_in';
        echo json_encode($response);
        wp_die();
    }

    if (!check_ajax_referer('like_post_nonce', 'nonce', false)) {
        $response['error'] = 'invalid_nonce';
        echo json_encode($response);
        wp_die();
    }

    $userId = get_current_user_id();
    $postId = $_POST['post_id'] ?? '';
    $likeEstado = isset($_POST['like_state']) ? filter_var($_POST['like_state'], FILTER_VALIDATE_BOOLEAN) : false;
    $likeType = $_POST['like_type'] ?? 'like';

    $allowedLikeTypes = ['like', 'favorito', 'no_me_gusta'];
    if (!in_array($likeType, $allowedLikeTypes)) {
        $response['error'] = 'error_like_type';
        echo json_encode($response);
        wp_die();
    }

    if (empty($postId)) {
        $response['error'] = 'missing_post_id';
        echo json_encode($response);
        wp_die();
    }

    $accion = $likeEstado ? $likeType : 'unlike';
    likeAccion($postId, $userId, $accion, $likeType);
    $contadorLike = contarLike($postId);
    $contadorFavorito = contarLike($postId, 'favorito');
    $contadorNoMeGusta = contarLike($postId, 'no_me_gusta');

    $response['success'] = true;
    $response['counts'] = array(
        'like' => $contadorLike,
        'favorito' => $contadorFavorito,
        'no_me_gusta' => $contadorNoMeGusta,
    );
    echo json_encode($response);

    // Log detallado 2: Finalización exitosa de la función
    error_log('[manejarLike] Función finalizada exitosamente. Tipo de like procesado: ' . $likeType);
    wp_die();
}

add_action('wp_ajax_like', 'manejarLike');

function likeAccion($postId, $userId, $accion, $likeType = 'like')
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $like_count = (int) get_user_meta($userId, 'like_count', true);

    // Log detallado en likeAccion: Información sobre la acción realizada en la base de datos
    error_log('[likeAccion] Ejecutando acción: ' . $accion . ' de tipo: ' . $likeType . ' para el post ID: ' . $postId . ' por el usuario ID: ' . $userId);

    if ($accion === $likeType && $accion !== 'unlike') {
        if (chequearLike($postId, $userId, $likeType)) {
            $accion = 'unlike';
        } else {
            $wpdb->insert(
                $table_name,
                ['user_id' => $userId, 'post_id' => $postId, 'like_type' => $likeType],
                ['%d', '%d', '%s']
            );
            if ($likeType === 'like') {
                $like_count++;
                update_user_meta($userId, 'like_count', $like_count);
                if ($like_count % 2 === 0) {
                    reiniciarFeed($userId);
                }
                $autorId = get_post_field('post_author', $postId);
                if ($autorId != $userId) {
                    $usuario = get_userdata($userId);
                    if ($usuario) {
                        crearNotificacion($autorId, $usuario->user_login . ' le ha dado me gusta a tu publicación.', false, $postId);
                    }
                }
            } elseif ($likeType === 'favorito') {
                $autorId = get_post_field('post_author', $postId);
                if ($autorId != $userId) {
                    $usuario = get_userdata($userId);
                    if ($usuario) {
                        crearNotificacion($autorId, $usuario->user_login . ' le ha encantado tu publicación.', false, $postId);
                    }
                }
            }
        }
    }

    if ($accion === 'unlike') {
        $wpdb->delete(
            $table_name,
            ['user_id' => $userId, 'post_id' => $postId, 'like_type' => $likeType],
            ['%d', '%d', '%s']
        );
        if ($likeType === 'like') {
            $like_count = max(0, $like_count - 1);
            update_user_meta($userId, 'like_count', $like_count);
            if ($like_count % 2 === 0 && $like_count > 0) {
                reiniciarFeed($userId);
            }
        }
    }
}

function obtenerLikesDelUsuario($userId, $limit = 500)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $query = $wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d ORDER BY like_date DESC LIMIT %d",
        $userId,
        $limit
    );
    $liked_posts = $wpdb->get_col($query);
    if (empty($liked_posts)) {
        return [];
    }
    return $liked_posts;
}


function contarLike($postId, $likeType = null)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    if ($likeType === null) {
        // Contar todos los likes (incluyendo 'like' y 'favorito') de forma eficiente
        $contadorLike = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND like_type IN ('like', 'favorito')",
            $postId
        ));
    } else {
        // Contar likes de un tipo específico
        $contadorLike = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND like_type = %s",
            $postId,
            $likeType
        ));
    }

    return (int)  $contadorLike ? $contadorLike : 0;
}



function chequearLike($postId, $userId, $likeType = 'like')
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $results = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(1) FROM $table_name WHERE post_id = %d AND user_id = %d AND like_type = %s",
        $postId,
        $userId,
        $likeType
    ));

    return $results > 0;
}


function like($postId)
{
    $userId = get_current_user_id();

    $contadorLike = contarLike($postId);
    $user_has_liked = chequearLike($postId, $userId, 'like');
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    $contadorFavorito = contarLike($postId, 'favorito');
    $user_has_favorited = chequearLike($postId, $userId, 'favorito');
    $favorited_class = $user_has_favorited ? 'liked' : 'not-liked';

    $contadorNoMeGusta = contarLike($postId, 'no_me_gusta');
    $user_has_disliked = chequearLike($postId, $userId, 'no_me_gusta');
    $disliked_class = $user_has_disliked ? 'liked' : 'not-liked';

    ob_start();
?>
    <div class="TJKQGJ botonlike-container">
        <button class="post-like-button <?= esc_attr($liked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="like" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            <? echo $GLOBALS['iconoCorazon']; ?> <span class="like-count"><?= esc_html($contadorLike) ?></span>
        </button>
        <div class="botones-extras">
            <button class="post-favorite-button <?= esc_attr($favorited_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="favorito" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
                <? echo $GLOBALS['estrella']; ?> <span class="favorite-count"><?= esc_html($contadorFavorito) ?></span>
            </button>
            <button class="post-dislike-button <?= esc_attr($disliked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="no_me_gusta" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
                <? echo $GLOBALS['dislike']; ?> <span class="dislike-count"><?= esc_html($contadorNoMeGusta) ?></span>
            </button>
        </div>
    </div>
<?
    $output = ob_get_clean();
    return $output;
}
?>



