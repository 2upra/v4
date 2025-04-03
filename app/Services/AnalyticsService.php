<?php

# Guarda la vista de un post y actualiza las vistas totales.
function guardarVista() {

    if (!isset($_POST['id_post']) || !is_numeric($_POST['id_post'])) {
        wp_send_json_error('ID de post inválido.');
        wp_die();
    }

    $idPost = intval($_POST['id_post']);
    $idUsuario = get_current_user_id();

    if ($idUsuario) {
        $vistasUsuario = get_user_meta($idUsuario, 'vistas_posts', true) ?: [];
        $vistasTotalesUsuario = get_user_meta($idUsuario, 'vistas_totales_usuario', true) ?: 0;
        $fechaActual = time();

        if (isset($vistasUsuario[$idPost])) {
            $vistasUsuario[$idPost]['count']++;
            $vistasUsuario[$idPost]['last_view'] = $fechaActual;
        } else {
            $vistasUsuario[$idPost] = [
                'count' => 1,
                'last_view' => $fechaActual,
            ];
        }

        $vistasTotalesUsuario++;
        update_user_meta($idUsuario, 'vistas_posts', $vistasUsuario);
        update_user_meta($idUsuario, 'vistas_totales_usuario', $vistasTotalesUsuario);


        if (function_exists('reiniciarFeed') && $vistasTotalesUsuario % 6 === 0) {
            reiniciarFeed($idUsuario);
        }
    }

    $vistaTotales = get_post_meta($idPost, 'vistas_totales', true) ?: 0;
    $vistaTotales++;
    update_post_meta($idPost, 'vistas_totales', $vistaTotales);
    $respuesta = ['vistas_totales' => $vistaTotales];

    if ($idUsuario && isset($vistasUsuario[$idPost])) {
        $respuesta['vistas_usuario'] = $vistasUsuario[$idPost]['count'];
    }

    wp_send_json_success($respuesta);
    wp_die();
}
