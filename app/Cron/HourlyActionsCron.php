<?php
/**
 * app/Cron/HourlyActionsCron.php
 *
 * Contiene el registro de hooks cron horarios y sus funciones callback.
 * Inicialmente, manejará el registro del historial de acciones.
 *
 * @package App\Cron
 */

// Refactor(Org): Archivo creado para lógica de cron horaria

// Aquí se registrarán los hooks y callbacks para tareas cron horarias.
// Ejemplo: add_action('hourly_event_hook', 'callback_function');

// Las funciones callback se moverán aquí desde otros archivos (ej: app/Finanza/Calculos.php).

// Refactor(Org): Moved from app/Finanza/Calculos.php
function registrarHistorialAcciones()
{
    global $wpdb;
    $tablaHistorial = $wpdb->prefix . 'historial_acciones';
    $usuarios = get_users();

    // Ensure calc_ing is available (defined in app/Services/EconomyCalculationService.php)
    if (!function_exists('calc_ing')) {
        // Log error or handle missing function appropriately
        error_log('Error: Function calc_ing() not found in HourlyActionsCron.php');
        return; 
    }
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

// Refactor(Org): Moved from app/Finanza/Calculos.php
function registrar_evento_cron_historial_acciones()
{
    if (!wp_next_scheduled('evento_cron_historial_acciones')) {
        wp_schedule_event(time(), 'hourly', 'evento_cron_historial_acciones');
    }
}

// Refactor(Org): Moved hook from app/Finanza/Calculos.php
add_action('wp', 'registrar_evento_cron_historial_acciones');

// Refactor(Org): Added hook associated with registrarHistorialAcciones as per instruction
add_action('evento_cron_historial_acciones', 'registrarHistorialAcciones');

?>
