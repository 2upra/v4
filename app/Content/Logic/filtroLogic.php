<?
//saber el filtro tiempo

// Refactor(Org): Función obtenerFiltroActual() y su hook AJAX movidos a app/Services/FilterService.php

// ASI FUNCIONA CORRECTAMENTE a:1:{i:0;s:15:"mostrarMeGustan";} PERO CUANDO SE RESTABLECE ALGO SE GUARDA ASI s:33:"a:1:{i:1;s:15:"mostrarMeGustan";}";, cosa que hace que evidenmente falla, arreglalo

// Refactor(Org): Función restablecerFiltros() y su hook AJAX movidos a app/Services/FilterService.php


function obtenerFiltrosTotal()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }

    $user_id = get_current_user_id();
    $filtro_post = get_user_meta($user_id, 'filtroPost', true) ?: '{}'; 
    $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true) ?: 0;

    wp_send_json_success([
        'filtroPost' => $filtro_post,
        'filtroTiempo' => $filtro_tiempo,
    ]);
}
add_action('wp_ajax_obtenerFiltrosTotal', 'obtenerFiltrosTotal');

function guardarFiltroPost()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }
    $filtros = json_decode(stripslashes($_POST['filtros']), true);
    $user_id = get_current_user_id();
    if (update_user_meta($user_id, 'filtroPost', $filtros)) {
        wp_send_json_success(['message' => 'Filtros guardados correctamente']);
    } else {
        wp_send_json_error('Error al guardar los filtros');
    }
}
add_action('wp_ajax_guardarFiltroPost', 'guardarFiltroPost');

function obtenerFiltros()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }

    $user_id = get_current_user_id();
    $filtros = get_user_meta($user_id, 'filtroPost', true) ?: [];

    wp_send_json_success(['filtros' => $filtros]);
}
add_action('wp_ajax_obtenerFiltros', 'obtenerFiltros');

// Refactor(Org): Función guardarFiltro() y su hook AJAX movidos a app/Services/FilterService.php
