<?php
// Refactor(Org): Moved monthly cron functions and hooks from app/Finanza/SumaMensual.php

// Registrar evento cron mensual
function registrar_evento_mensual() {
    if (!wp_next_scheduled('accion_mensual_user_pro')) {
        wp_schedule_event(time(), 'monthly', 'accion_mensual_user_pro');
    }
}
add_action('wp', 'registrar_evento_mensual');

// Ejecutar cálculo mensual de acciones para usuarios Pro
add_action('accion_mensual_user_pro', 'calcularAccionMensualUsuariosPro');
function calcularAccionMensualUsuariosPro() {
    // Note: sumaAcciones was moved to EconomyService.php
    // This function relies on sumaAcciones being available globally or included.
    // Ensure EconomyService.php is loaded before this hook runs.
    if (function_exists('sumaAcciones')) {
        sumaAcciones(true);
    } else {
        error_log('Error: La función sumaAcciones no está disponible en MonthlyActionsCron.php');
    }
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

// Refactor(Org): Moved deactivation hook eliminar_evento_mensual() from app/Finanza/SumaMensual.php
// Eliminar evento cron mensual al desactivar
// Note: register_deactivation_hook using __FILE__ here might need adjustment
// if this file isn't the main plugin file. Moved as per instruction.
function eliminar_evento_mensual() {
    wp_clear_scheduled_hook('accion_mensual_user_pro');
}
register_deactivation_hook(__FILE__, 'eliminar_evento_mensual');

?>
