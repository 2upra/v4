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

?>