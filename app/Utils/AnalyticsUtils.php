<?php

/**
 * Guarda la vista de un post para un usuario y actualiza las vistas totales del post.
 * Se activa mediante AJAX.
 */
function guardarVista()
{
    if (isset($_POST['id_post'])) {
        $idPost = intval($_POST['id_post']);
        $userId = get_current_user_id();

        // Lógica para usuarios logueados
        if ($userId) {
            $vistasUsuario = get_user_meta($userId, 'vistas_posts', true);
            $vistasTotalesUsuario = get_user_meta($userId, 'vistas_totales_usuario', true);

            if (!$vistasUsuario) {
                $vistasUsuario = array();
            }

            if (!$vistasTotalesUsuario) {
                $vistasTotalesUsuario = 0;
            }

            $fechaActual = time();

            // Actualiza o crea la entrada de vista para este post
            if (isset($vistasUsuario[$idPost])) {
                $vistasUsuario[$idPost]['count']++;
                $vistasUsuario[$idPost]['last_view'] = $fechaActual;
            } else {
                $vistasUsuario[$idPost] = array(
                    'count' => 1,
                    'last_view' => $fechaActual,
                );
            }

            $vistasTotalesUsuario++;

            // Guarda los metadatos del usuario
            update_user_meta($userId, 'vistas_posts', $vistasUsuario);
            update_user_meta($userId, 'vistas_totales_usuario', $vistasTotalesUsuario);

            // Reinicia el feed si se alcanza un múltiplo de 6 vistas totales del usuario
            // Nota: La función reiniciarFeed() debe estar definida globalmente o incluida.
            if (function_exists('reiniciarFeed') && $vistasTotalesUsuario % 6 === 0) {
                reiniciarFeed($userId);
            }
        }

        // Lógica para vistas totales del post (independiente del usuario)
        $vistaTotales = get_post_meta($idPost, 'vistas_totales', true);

        if (!$vistaTotales) {
            $vistaTotales = 0;
        }

        $vistaTotales++;

        // Guarda las vistas totales del post
        update_post_meta($idPost, 'vistas_totales', $vistaTotales);

        // Prepara la respuesta JSON
        $responseData = array(
            'vistas_totales' => $vistaTotales
        );
        if ($userId && isset($vistasUsuario[$idPost])) {
             $responseData['vistas_usuario'] = $vistasUsuario[$idPost]['count'];
        }


        wp_send_json_success($responseData);
    }

    // Termina la ejecución de WordPress para peticiones AJAX
    wp_die();
}

/**
 * Obtiene el historial de vistas de posts para un usuario específico.
 *
 * @param int $userId ID del usuario.
 * @return array Array asociativo con ID de post como clave y datos de vista como valor, o array vacío si no hay vistas.
 */
function obtenerVistasPosts($userId)
{
    $vistas_posts = get_user_meta($userId, 'vistas_posts', true);

    if (empty($vistas_posts) || !is_array($vistas_posts)) {
        return [];
    }

    return $vistas_posts;
}

/**
 * Filtra un array de vistas, eliminando aquellas cuya última vista ('last_view')
 * es más antigua que un número específico de días.
 *
 * @param array $vistas Array asociativo de vistas (postId => ['count' => int, 'last_view' => timestamp]).
 * @param int $dias Número de días para el límite de antigüedad.
 * @return array El array de vistas filtrado.
 */
function limpiarVistasAntiguas($vistas, $dias)
{
    if (empty($vistas) || !is_array($vistas)) {
        return [];
    }

    $fechaLimite = time() - (absint($dias) * 86400); // 86400 segundos en un día

    foreach ($vistas as $postId => $infoVista) {
        // Asegurarse de que 'last_view' existe y es numérico
        if (!isset($infoVista['last_view']) || !is_numeric($infoVista['last_view'])) {
             // Opcional: manejar o registrar posts con datos de vista inválidos
             unset($vistas[$postId]);
             continue;
        }

        if ($infoVista['last_view'] < $fechaLimite) {
            unset($vistas[$postId]);
        }
    }

    return $vistas;
}

// Hooks para manejar la petición AJAX de guardar vistas
add_action('wp_ajax_guardar_vistas', 'guardarVista');        // Para usuarios logueados
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista'); // Para usuarios no logueados

