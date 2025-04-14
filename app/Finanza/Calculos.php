<?

// Refactor(Org): Moved function definir_acciones_usuario to app/Services/EconomyService.php

$usuarios_acciones = [
    1 => 420000,
    40 => 4000,
    41 => 12000,
    45 => 9000, //HORACIO
    49 => 5000,
    51 => 6500
];


function obtenerHistorialAccionesUsuario()
{
    global $wpdb;
    $tablaHistorial = $wpdb->prefix . 'historial_acciones';
    $user_id = get_current_user_id();
    $resultados = $wpdb->get_results($wpdb->prepare(
        "SELECT fecha, acciones FROM $tablaHistorial WHERE user_id = %d ORDER BY fecha ASC",
        $user_id
    ));

    return $resultados;
}

// Refactor(Org): Moved function registrarHistorialAcciones to app/Cron/HourlyActionsCron.php

// Refactor(Org): Moved function registrar_evento_cron_historial_acciones and its hook to app/Cron/HourlyActionsCron.php

// Refactor(Org): Moved function calcularAccionPorUsuario to app/Services/EconomyService.php
