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
