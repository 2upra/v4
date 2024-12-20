<?



function manejarLike()
{
    if (!is_user_logged_in()) {
        echo 'not_logged_in';
        wp_die();
    }

    $userId = get_current_user_id();
    $postId = $_POST['post_id'] ?? '';
    $likeEstado = $_POST['like_state'] ?? false;
    $likeType = $_POST['like_type'] ?? 'like'; // Obtener el tipo de like, por defecto 'like'

    // Validación del tipo de like (importante para seguridad)
    $allowedLikeTypes = ['like', 'favorito', 'no_me_gusta'];
    if (!in_array($likeType, $allowedLikeTypes)) {
        echo 'error_like_type';
        wp_die();
    }

    if (empty($postId)) {
        echo 'error';
        wp_die();
    }

    // La acción ahora depende del estado y el tipo de like
    $accion = $likeEstado ? $likeType : 'unlike';

    likeAccion($postId, $userId, $accion, $likeType); // Pasar el likeType
    $contadorLike = contarLike($postId, 'like'); // Contar solo los likes "tradicionales"
    echo $contadorLike;
    wp_die();
}

add_action('wp_ajax_like', 'manejarLike');



function likeAccion($postId, $userId, $accion, $likeType = 'like')
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $like_count = (int) get_user_meta($userId, 'like_count', true);

    if ($accion === $likeType && $accion !== 'unlike') { // Si la acción es un tipo de like (like, favorito, no_me_gusta)
        if (chequearLike($postId, $userId, $likeType)) { // Verificar si ya existe ese tipo de like
            $accion = 'unlike'; // Si ya existe, se convierte en 'unlike'
        } else {
            $insert_result = $wpdb->insert($table_name, ['user_id' => $userId, 'post_id' => $postId, 'like_type' => $likeType]);
            if ($insert_result !== false) {
                if ($likeType === 'like') { // Solo actualizar el contador 'like_count' para likes tradicionales
                    $like_count++;
                    update_user_meta($userId, 'like_count', $like_count);
                    if ($like_count % 2 === 0) {
                        reiniciarFeed($userId);
                    }
                    $autorId = get_post_field('post_author', $postId);
                    if ($autorId != $userId) {
                        $usuario = get_userdata($userId);
                        crearNotificacion($autorId, $usuario->user_login . ' le ha dado me gusta a tu publicación.', false, $postId);
                    }
                } // Podrías agregar lógica similar para otros likeTypes si es necesario
            }
        }
    }

    if ($accion === 'unlike') {
        $delete_result = $wpdb->delete($table_name, ['user_id' => $userId, 'post_id' => $postId, 'like_type' => $likeType]);
        if ($delete_result !== false) {
            if ($likeType === 'like') { // Solo actualizar el contador 'like_count' para likes tradicionales
                $like_count = max(0, $like_count - 1);
                update_user_meta($userId, 'like_count', $like_count);
                if ($like_count % 2 === 0 && $like_count > 0) {
                    reiniciarFeed($userId);
                }
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

function contarLike($postId, $likeType = 'like')
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $contadorLike = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND like_type = %s",
        $postId,
        $likeType
    ));

    return $contadorLike ? $contadorLike : 0;
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

    $contadorLike = contarLike($postId, 'like');
    $user_has_liked = chequearLike($postId, $userId, 'like');
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    $contadorFavorito = contarLike($postId, 'favorito');
    $user_has_favorited = chequearLike($postId, $userId, 'favorito');
    $favorited_class = $user_has_favorited ? 'favorited' : 'not-favorited';

    $contadorNoMeGusta = contarLike($postId, 'no_me_gusta');
    $user_has_disliked = chequearLike($postId, $userId, 'no_me_gusta');
    $disliked_class = $user_has_disliked ? 'disliked' : 'not-disliked';

    ob_start();
?>
    <div class="TJKQGJ botonlike-container">
        <button class="post-like-button <?= esc_attr($liked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="like" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            <? echo $GLOBALS['iconoCorazon']; ?> <span class="like-count"><?= esc_html($contadorLike) ?></span>
        </button>
        <button class="post-favorite-button <?= esc_attr($favorited_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="favorito" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            t1<? // Icono de favorito ?> <span class="favorite-count"><?= esc_html($contadorFavorito) ?></span>
        </button>
        <button class="post-dislike-button <?= esc_attr($disliked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="no_me_gusta" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            t2<? // Icono de no me gusta ?> <span class="dislike-count"><?= esc_html($contadorNoMeGusta) ?></span>
        </button>
    </div>
<?
    $output = ob_get_clean();
    return $output;
}