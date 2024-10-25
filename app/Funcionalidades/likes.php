<?

function manejarLike() {
    if (!is_user_logged_in()) {
        //guardarLog("Usuario no está logueado");
        echo 'not_logged_in';
        wp_die();
    }

    $userId = get_current_user_id();
    $postId = $_POST['post_id'] ?? '';
    $likeEstado = $_POST['like_state'] ?? false;

    //guardarLog("Datos recibidos: user_id = $userId, post_id = $postId, like_state = $likeEstado");

    if (empty($postId)) {
        //guardarLog("post_id está vacío");
        echo 'error';
        wp_die();
    }

    $accion = $likeEstado ? 'like' : 'unlike';
    //guardarLog("Acción determinada: $accion");
    likeAccion($postId, $userId, $accion);
    $contadorLike = contarLike($postId);
    //guardarLog("Cantidad de likes después de la acción: $contadorLike")
    echo $contadorLike;
    wp_die();
}

add_action('wp_ajax_like', 'manejarLike');


//aqui esto no debería enviar la notificacion al usuario cuando esta dando like a su propia publicación, pero lo hace
function likeAccion($postId, $userId, $accion) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    if ($accion === 'like') {
        if (chequearLike($postId, $userId)) {
            //guardarLog("El usuario $userId ya ha dado like al post $postId.");
            $accion = 'unlike';  // Si ya le gustó, se cambia a "unlike"
        } else {
            $insert_result = $wpdb->insert($table_name, ['user_id' => $userId, 'post_id' => $postId]);
            if ($insert_result === false) {
                //guardarLog("Error al insertar el like en la base de datos para el post $postId y el usuario $userId.");
            } else {
                //guardarLog("Like insertado correctamente en la base de datos.");
                
                // Obtener el ID del autor del post
                $autorId = get_post_field('post_author', $postId);
                
                // Solo enviar notificación si el autor es diferente del usuario que da like
                if ($autorId != $userId) {
                    $usuario = get_userdata($userId);
                    agregarNoti($autorId, "{$usuario->display_name} le gustó tu publicación.", get_permalink($postId), $userId);
                }
            }
        }
    }

    if ($accion === 'unlike') {
        $delete_result = $wpdb->delete($table_name, ['user_id' => $userId, 'post_id' => $postId]);
        if ($delete_result === false) {
            //guardarLog("Error al eliminar el like en la base de datos para el post $postId y el usuario $userId.");
        } else {
            //guardarLog("Like eliminado correctamente.");
        }
    }
}


function obtenerLikesDelUsuario($userId, $limit = 500)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    
    // Prepara la consulta SQL con un límite opcional
    $query = $wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d ORDER BY like_date DESC LIMIT %d",
        $userId, $limit
    );
    
    // Obtener los IDs de los posts con like
    $liked_posts = $wpdb->get_col($query);
    
    // Si no hay resultados, devuelve un array vacío
    if (empty($liked_posts)) {
        return [];
    }
    
    return $liked_posts;
}

function contarLike($postId) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    
    // Contar el número de likes para el post
    $contadorLike = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
        $postId
    ));

    return $contadorLike ? $contadorLike : 0;
}

function chequearLike($postId, $userId) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    // Verificar si el usuario ya ha dado like al post
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