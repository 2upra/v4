<?php
// Funciones lógicas de likes movidas desde app/Functions/likes.php

/**
 * Maneja la solicitud AJAX para dar/quitar like/favorito/no_me_gusta a una publicación.
 */
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

// Hook para manejar la acción AJAX
add_action('wp_ajax_like', 'manejarLike');

/**
 * Realiza la acción de like/unlike en la base de datos.
 *
 * @param int $postId ID del post.
 * @param int $userId ID del usuario.
 * @param string $accion Acción a realizar ('like', 'favorito', 'no_me_gusta', 'unlike').
 * @param string $likeType Tipo de like ('like', 'favorito', 'no_me_gusta').
 */
function likeAccion($postId, $userId, $accion, $likeType = 'like')
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $like_count = (int) get_user_meta($userId, 'like_count', true);

    // Log detallado en likeAccion: Información sobre la acción realizada en la base de datos
    error_log('[likeAccion] Ejecutando acción: ' . $accion . ' de tipo: ' . $likeType . ' para el post ID: ' . $postId . ' por el usuario ID: ' . $userId);

    if ($accion === $likeType && $accion !== 'unlike') {
        if (chequearLike($postId, $userId, $likeType)) {
            // Si ya existe un like del mismo tipo, lo eliminamos (comportamiento de toggle)
            $accion = 'unlike';
        } else {
            // Insertar el nuevo like
            $wpdb->insert(
                $table_name,
                ['user_id' => $userId, 'post_id' => $postId, 'like_type' => $likeType],
                ['%d', '%d', '%s']
            );
            if ($likeType === 'like') {
                $like_count++;
                update_user_meta($userId, 'like_count', $like_count);
                if ($like_count % 2 === 0) {
                    reiniciarFeed($userId); // Asume que esta función existe globalmente o es inyectada
                }
                $autorId = get_post_field('post_author', $postId);
                if ($autorId != $userId) {
                    $usuario = get_userdata($userId);
                    if ($usuario) {
                        crearNotificacion($autorId, $usuario->user_login . ' le ha dado me gusta a tu publicación.', false, $postId); // Asume que esta función existe
                    }
                }
            } elseif ($likeType === 'favorito') {
                $autorId = get_post_field('post_author', $postId);
                if ($autorId != $userId) {
                    $usuario = get_userdata($userId);
                    if ($usuario) {
                        crearNotificacion($autorId, $usuario->user_login . ' le ha encantado tu publicación.', false, $postId); // Asume que esta función existe
                    }
                }
            }
            // No hay acción específica para 'no_me_gusta' aquí más allá de registrarlo
        }
    }

    // Si la acción es 'unlike' (ya sea directamente o por toggle)
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
                reiniciarFeed($userId); // Asume que esta función existe
            }
        }
        // No hay decremento de contador específico para 'favorito' o 'no_me_gusta' en user_meta
    }
}

/**
 * Cuenta los likes de un tipo específico para una publicación.
 *
 * @param int $postId ID del post.
 * @param string|null $likeType Tipo de like ('like', 'favorito', 'no_me_gusta'). Si es null, cuenta 'like' y 'favorito'.
 * @return int Número de likes.
 */
function contarLike($postId, $likeType = null)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    if ($likeType === null) {
        // Cuenta combinada de 'like' y 'favorito' por defecto para el contador principal
        $contadorLike = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND like_type IN ('like', 'favorito')",
            $postId
        ));
    } else {
        // Cuenta específica por tipo
        $contadorLike = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND like_type = %s",
            $postId,
            $likeType
        ));
    }

    return (int) $contadorLike ? $contadorLike : 0;
}

/**
 * Verifica si un usuario ha dado un tipo específico de like a una publicación.
 *
 * @param int $postId ID del post.
 * @param int $userId ID del usuario.
 * @param string $likeType Tipo de like ('like', 'favorito', 'no_me_gusta').
 * @return bool True si el usuario ha dado ese like, false en caso contrario.
 */
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

// Refactor(Org): Función obtenerLikesPorPost() movida desde app/Content/Logic/datosParaCalculo.php
function obtenerLikesPorPost($postsIds) {
    global $wpdb;
    $tiempoInicio = microtime(true);
    $tablaLikes = "{$wpdb->prefix}post_likes";

    // Asegurarse de que $postsIds no esté vacío para evitar errores SQL
    if (empty($postsIds)) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));

    $sqlLikes = "
        SELECT post_id, like_type, COUNT(*) as cantidad
        FROM $tablaLikes
        WHERE post_id IN ($placeholders)
        GROUP BY post_id, like_type
    ";

    // Usar array_merge correctamente para pasar los argumentos a prepare
    $args = array_merge([$sqlLikes], $postsIds);
    $likesResultados = $wpdb->get_results(call_user_func_array([$wpdb, 'prepare'], $args));

    if ($wpdb->last_error) {
        //guardarLog("[obtenerLikesPorPost] Error: Fallo al obtener likes: " . $wpdb->last_error);
    }
    //rendimientolog("[obtenerLikesPorPost] Tiempo para obtener \$likesResultados: " . (microtime(true) - $tiempoInicio) . " segundos");

    $likesPorPost = [];
    foreach ($likesResultados as $like) {
        if (!isset($likesPorPost[$like->post_id])) {
            $likesPorPost[$like->post_id] = [
                'like' => 0,
                'favorito' => 0,
                'no_me_gusta' => 0
            ];
        }
        $likesPorPost[$like->post_id][$like->like_type] = (int)$like->cantidad;
    }
    //rendimientolog("[obtenerLikesPorPost] Tiempo para procesar \$likesPorPost: " . (microtime(true) - $tiempoInicio) . " segundos");

    return $likesPorPost;
}

// Refactor(Org): Funcion saberSi() movida desde app/Utils/UserUtils.php
function saberSi($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $last_run = get_user_meta($user_id, 'ultima_ejecucion_saber', true);
    $current_time = current_time('timestamp');

    if ($last_run && ($current_time - $last_run < 1)) {
        return; 
    }
    update_user_meta($user_id, 'ultima_ejecucion_saber', $current_time);

    //Saber si le gusta una rola
    $liked_posts = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    if (empty($liked_posts)) {
        update_user_meta($user_id, 'leGustaAlMenosUnaRola', false);
        return;
    }

    $rola_posts = get_posts(array(
        'post__in' => $liked_posts,
        'meta_query' => array(
            array(
                'key' => 'rola',
                'value' => 'true',
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    $le_gusta_rola = !empty($rola_posts);
    update_user_meta($user_id, 'leGustaAlMenosUnaRola', $le_gusta_rola);
}

?>
