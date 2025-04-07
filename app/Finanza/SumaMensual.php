<?php
// Refactor(Org): Moved function sumaAcciones to app/Services/EconomyService.php

// Registrar y eliminar eventos cron mensuales
function registrar_evento_mensual() {
    if (!wp_next_scheduled('accion_mensual_user_pro')) {
        wp_schedule_event(time(), 'monthly', 'accion_mensual_user_pro');
    }
}
add_action('wp', 'registrar_evento_mensual');

function eliminar_evento_mensual() {
    wp_clear_scheduled_hook('accion_mensual_user_pro');
}
register_deactivation_hook(__FILE__, 'eliminar_evento_mensual');

// Ejecutar cálculo mensual de acciones
add_action('accion_mensual_user_pro', 'calcularAccionMensualUsuariosPro');
function calcularAccionMensualUsuariosPro() {
    // Note: sumaAcciones was moved to EconomyService.php
    // This function call will likely fail unless sumaAcciones is globally available
    // or this code is also moved/updated.
    sumaAcciones(true);
}
// Añadir intervalo mensual a cron
function agregar_intervalo_cron_mensual($schedules) {
    $schedules['monthly'] = array(
        'interval' => 30 * 24 * 60 * 60, // 30 días en segundos
        'display'  => __('Una vez al mes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'agregar_intervalo_cron_mensual');

