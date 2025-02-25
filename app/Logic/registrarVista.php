<?php

function guardarVista()
{
    if (isset($_POST['id_post'])) {
        $idPost = intval($_POST['id_post']);
        $userId = get_current_user_id();

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

            update_user_meta($userId, 'vistas_posts', $vistasUsuario);
            update_user_meta($userId, 'vistas_totales_usuario', $vistasTotalesUsuario);

            if ($vistasTotalesUsuario % 6 === 0) {
                reiniciarFeed($userId);
            }
        }

        $vistaTotales = get_post_meta($idPost, 'vistas_totales', true);

        if (!$vistaTotales) {
            $vistaTotales = 0;
        }

        $vistaTotales++;

        update_post_meta($idPost, 'vistas_totales', $vistaTotales);

        wp_send_json_success(array(
            'vistas_usuario' => $vistasUsuario[$idPost]['count'],
            'vistas_totales' => $vistaTotales
        ));
    }

    wp_die();
}

function obtenerVistasPosts($userId)
{
    $vistas_posts = get_user_meta($userId, 'vistas_posts', true);

    if (empty($vistas_posts)) {
        return [];
    }

    return $vistas_posts;
}

add_action('wp_ajax_guardar_vistas', 'guardarVista');
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista');

function limpiarVistasAntiguas($vistas, $dias)
{
    $fechaLimite = time() - (86400 * $dias);

    foreach ($vistas as $postId => $infoVista) {
        if ($infoVista['last_view'] < $fechaLimite) {
            unset($vistas[$postId]);
        }
    }

    return $vistas;
}
