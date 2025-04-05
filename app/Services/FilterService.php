<?php
// Refactor(Org): Mover función guardarFiltro() y su hook AJAX aquí desde filtroLogic.php

function guardarFiltro()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Usuario no autenticado']);
        return;
    }

    if (!isset($_POST['filtroTiempo'])) {
        wp_send_json_error(['message' => 'Valor de filtroTiempo no especificado']);
        return;
    }

    $user_id = get_current_user_id();
    $filtro_tiempo = intval($_POST['filtroTiempo']);

    if (update_user_meta($user_id, 'filtroTiempo', $filtro_tiempo)) {
        wp_send_json_success([
            'message' => 'Filtro guardado correctamente',
            'filtroTiempo' => $filtro_tiempo,
            'userId' => $user_id
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al guardar el filtro']);
    }
}
add_action('wp_ajax_guardarFiltro', 'guardarFiltro');

// Refactor(Org): Mover función obtenerFiltroActual() y su hook AJAX aquí desde filtroLogic.php
function obtenerFiltroActual()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Usuario no autenticado']);
        return;
    }

    $user_id = get_current_user_id();
    $filtro_tiempo = intval(get_user_meta($user_id, 'filtroTiempo', true) ?: 0);
    $nombres_filtros = ['Feed', 'Reciente', 'Semanal', 'Mensual'];
    $nombre_filtro = $nombres_filtros[$filtro_tiempo] ?? 'Feed';

    wp_send_json_success([
        'filtroTiempo' => $filtro_tiempo,
        'nombreFiltro' => $nombre_filtro
    ]);
}
add_action('wp_ajax_obtenerFiltroActual', 'obtenerFiltroActual');
