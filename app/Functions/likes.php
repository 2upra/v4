<?


function manejarLike() {
    error_log('[manejarLike] Iniciando función.');

    $response = array('success' => false); // Initialize a default response

    if (!is_user_logged_in()) {
        error_log('[manejarLike] Usuario no autenticado.');
        $response['error'] = 'not_logged_in';
        echo json_encode($response);
        wp_die();
    }

    if (!check_ajax_referer('like_post_nonce', 'nonce', false)) {
        error_log('[manejarLike] Nonce inválido.');
        $response['error'] = 'invalid_nonce';
        echo json_encode($response);
        wp_die();
    }

    $userId = get_current_user_id();
    error_log('[manejarLike] User ID: ' . $userId);

    $postId = $_POST['post_id'] ?? '';
    error_log('[manejarLike] Post ID recibido: ' . $postId);

    $likeEstado = isset($_POST['like_state']) ? filter_var($_POST['like_state'], FILTER_VALIDATE_BOOLEAN) : false;
    error_log('[manejarLike] Estado del Like recibido: ' . ($likeEstado ? 'true' : 'false'));

    $likeType = $_POST['like_type'] ?? 'like'; // Obtener el tipo de like, por defecto 'like'
    error_log('[manejarLike] Tipo de Like recibido: ' . $likeType);

    // Validación del tipo de like (importante para seguridad)
    $allowedLikeTypes = ['like', 'favorito', 'no_me_gusta'];
    if (!in_array($likeType, $allowedLikeTypes)) {
        error_log('[manejarLike] Error: Tipo de like no permitido: ' . $likeType);
        $response['error'] = 'error_like_type';
        echo json_encode($response);
        wp_die();
    }

    if (empty($postId)) {
        error_log('[manejarLike] Error: Post ID vacío.');
        $response['error'] = 'missing_post_id';
        echo json_encode($response);
        wp_die();
    }

    // La acción ahora depende del estado y el tipo de like
    $accion = $likeEstado ? $likeType : 'unlike';
    error_log('[manejarLike] Acción a realizar: ' . $accion);

    likeAccion($postId, $userId, $accion, $likeType); // Pasar el likeType
    $contadorLike = contarLike($postId, 'like'); // Contar los likes
    $contadorFavorito = contarLike($postId, 'favorito'); // Contar los favoritos
    $contadorNoMeGusta = contarLike($postId, 'no_me_gusta'); // Contar los no me gusta
    error_log('[manejarLike] Contador de likes para el post ' . $postId . ': ' . $contadorLike);
    error_log('[manejarLike] Contador de favoritos para el post ' . $postId . ': ' . $contadorFavorito);
    error_log('[manejarLike] Contador de no me gusta para el post ' . $postId . ': ' . $contadorNoMeGusta);

    // Enviar todos los contadores como JSON
    $response['success'] = true; // Mark as successful
    $response['counts'] = array(
        'like' => $contadorLike,
        'favorito' => $contadorFavorito,
        'no_me_gusta' => $contadorNoMeGusta,
    );
    echo json_encode($response);
    error_log('[manejarLike] Finalizando función.');
    wp_die();
}

add_action('wp_ajax_like', 'manejarLike');


function likeAccion($postId, $userId, $accion, $likeType = 'like')
{
    error_log('[likeAccion] Iniciando función. Post ID: ' . $postId . ', User ID: ' . $userId . ', Acción: ' . $accion . ', Tipo de Like: ' . $likeType);

    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $like_count = (int) get_user_meta($userId, 'like_count', true);
    error_log('[likeAccion] Contador de likes actual del usuario ' . $userId . ': ' . $like_count);

    if ($accion === $likeType && $accion !== 'unlike') { // Si la acción es un tipo de like (like, favorito, no_me_gusta)
        error_log('[likeAccion] La acción es de tipo like (' . $likeType . ').');
        if (chequearLike($postId, $userId, $likeType)) { // Verificar si ya existe ese tipo de like
            error_log('[likeAccion] El usuario ' . $userId . ' ya ha dado ' . $likeType . ' al post ' . $postId . '. Cambiando a unlike.');
            $accion = 'unlike'; // Si ya existe, se convierte en 'unlike'
        } else {
            error_log('[likeAccion] Insertando ' . $likeType . ' para el usuario ' . $userId . ' en el post ' . $postId . '.');
            $insert_result = $wpdb->insert(
                $table_name,
                ['user_id' => $userId, 'post_id' => $postId, 'like_type' => $likeType],
                ['%d', '%d', '%s']
            );
            if ($insert_result !== false) {
                error_log('[likeAccion] Inserción exitosa en la base de datos.');
                if ($likeType === 'like') { // Solo actualizar el contador 'like_count' para likes tradicionales
                    $like_count++;
                    update_user_meta($userId, 'like_count', $like_count);
                    error_log('[likeAccion] Contador de likes del usuario ' . $userId . ' actualizado a: ' . $like_count);
                    if ($like_count % 2 === 0) {
                        error_log('[likeAccion] El contador de likes de ' . $userId . ' es par. Llamando a reiniciarFeed().');
                        reiniciarFeed($userId);
                    }
                    $autorId = get_post_field('post_author', $postId);
                    if ($autorId != $userId) {
                        $usuario = get_userdata($userId);
                        if ($usuario) {
                            error_log('[likeAccion] Creando notificación para el autor ' . $autorId . ' del post ' . $postId . '.');
                            crearNotificacion($autorId, $usuario->user_login . ' le ha dado me gusta a tu publicación.', false, $postId);
                        } else {
                            error_log('[likeAccion] Error: No se pudo obtener la información del usuario ' . $userId . ' para la notificación.');
                        }
                    } else {
                        error_log('[likeAccion] El usuario ' . $userId . ' es el autor del post ' . $postId . '. No se crea notificación.');
                    }
                } else {
                    error_log('[likeAccion] No se actualiza el contador de likes del usuario porque el tipo de like es: ' . $likeType);
                }
            } else {
                error_log('[likeAccion] Error al insertar en la base de datos. Error: ' . $wpdb->last_error);
            }
        }
    }

    if ($accion === 'unlike') {
        error_log('[likeAccion] La acción es unlike.');
        $delete_result = $wpdb->delete(
            $table_name,
            ['user_id' => $userId, 'post_id' => $postId, 'like_type' => $likeType],
            ['%d', '%d', '%s']
        );
        if ($delete_result !== false) {
            error_log('[likeAccion] Eliminación exitosa de la base de datos.');
            if ($likeType === 'like') { // Solo actualizar el contador 'like_count' para likes tradicionales
                $like_count = max(0, $like_count - 1);
                update_user_meta($userId, 'like_count', $like_count);
                error_log('[likeAccion] Contador de likes del usuario ' . $userId . ' actualizado a: ' . $like_count);
                if ($like_count % 2 === 0 && $like_count > 0) {
                    error_log('[likeAccion] El contador de likes de ' . $userId . ' es par y mayor que 0. Llamando a reiniciarFeed().');
                    reiniciarFeed($userId);
                }
            } else {
                error_log('[likeAccion] No se actualiza el contador de likes del usuario porque el tipo de like es: ' . $likeType);
            }
        } else {
            error_log('[likeAccion] Error al eliminar de la base de datos. Error: ' . $wpdb->last_error);
        }
    }
    error_log('[likeAccion] Finalizando función.');
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