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

// Funcion obtenerVistasPosts() movida desde app/Utils/AnalyticsUtils.php
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

// Funcion limpiarVistasAntiguas() movida desde app/Utils/AnalyticsUtils.php
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

// Hooks AJAX para guardarVista() movidos desde app/Utils/AnalyticsUtils.php
add_action('wp_ajax_guardar_vistas', 'guardarVista');        // Para usuarios logueados
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista'); // Para usuarios no logueados

// Refactor(Exec): Moved function vistasDatos from app/Content/Logic/datosParaCalculo.php
function vistasDatos($userId) {
    $tiempoInicio = microtime(true);
    $vistas = get_user_meta($userId, 'vistas_posts', true);
    //rendimientolog("[vistasDatos] Tiempo para obtener 'vistas': " . (microtime(true) - $tiempoInicio) . " segundos");
    return $vistas;
}
