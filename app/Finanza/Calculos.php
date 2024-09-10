<?php

function definir_acciones_usuario($usuarios_acciones, $actualizar_si_existe = false)
{
    foreach ($usuarios_acciones as $user_id => $cantidad_acciones) {
        if (get_user_meta($user_id, 'acciones', true) && $actualizar_si_existe) {
            update_user_meta($user_id, 'acciones', $cantidad_acciones);
        } else {
            add_user_meta($user_id, 'acciones', $cantidad_acciones, true);
        }
    }
}
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

function registrarHistorialAcciones()
{
    global $wpdb;
    $tablaHistorial = $wpdb->prefix . 'historial_acciones';
    $usuarios = get_users();

    $valAcc = calc_ing(48, false)['valAcc'];
    $fecha = date('Y-m-d');

    foreach ($usuarios as $user) {
        $acciones = get_user_meta($user->ID, 'acciones', true);

        if ($acciones) {
            $wpdb->delete(
                $tablaHistorial,
                [
                    'user_id' => $user->ID,
                    'fecha' => $fecha
                ],
                [
                    '%d',
                    '%s'
                ]
            );
            // Insertar el nuevo registro
            $wpdb->insert(
                $tablaHistorial,
                [
                    'user_id' => $user->ID,
                    'fecha' => $fecha,
                    'acciones' => $acciones,
                    'valor' => $acciones * $valAcc
                ],
                [
                    '%d',
                    '%s',
                    '%d',
                    '%f'
                ]
            );
        }
    }
}

function registrar_evento_cron_historial_acciones()
{
    if (!wp_next_scheduled('evento_cron_historial_acciones')) {
        wp_schedule_event(time(), 'hourly', 'evento_cron_historial_acciones');
    }
}
add_action('wp', 'registrar_evento_cron_historial_acciones');