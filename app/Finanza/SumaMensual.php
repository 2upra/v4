<?

function sumaAcciones($mostrarTodos = false)
{
    error_log("La función sumaAcciones se está ejecutando.");
    global $wpdb;
    $totalAcciones = 810000; // Acciones totales
    $valAcc = calc_ing(48, false)['valAcc']; // Valor por acción
    $accionesExtra = 2.5 / $valAcc; // Acciones equivalentes a $2.5
    $fechaActual = new DateTime();

    // Rutas de los archivos de log
    $logAcciones = '/var/www/wordpress/wp-content/themes/SumaAcciones.log';
    $logRespaldo = '/var/www/wordpress/wp-content/themes/RespaldoValor.log';

    // Función para escribir en el log
    function escribirLog($mensaje, $archivoLog) {
        $fecha = (new DateTime())->format('Y-m-d H:i:s');
        $mensajeCompleto = "[$fecha] $mensaje\n";

        // Intentar escribir en el archivo de log
        $resultado = file_put_contents($archivoLog, $mensajeCompleto, FILE_APPEND);

        // Si falla, escribir en el log de errores de PHP
        if ($resultado === false) {
            error_log("ERROR: No se pudo escribir en el archivo de log: $archivoLog");
            error_log("Mensaje que se intentó escribir: $mensajeCompleto");

            // Intentar escribir en un archivo temporal en /tmp/
            file_put_contents('/tmp/SumaAcciones_temp.log', $mensajeCompleto, FILE_APPEND);
        }
    }

    // Función para sumar acciones si corresponde
    function actualizarAcciones($userID, $acciones, $accionesExtra, $fechaActual, $logAcciones) {
        $ultimaActualizacion = get_user_meta($userID, 'ultima_actualizacion_acciones', true);
        $actualizar = false;

        if ($ultimaActualizacion) {
            $fechaUltima = new DateTime($ultimaActualizacion);
            $intervalo = $fechaUltima->diff($fechaActual);

            // Solo sumamos si ha pasado al menos un mes
            if ($intervalo->m >= 1 || $intervalo->y > 0) {
                $actualizar = true;
            }
        } else {
            $actualizar = true;
        }

        if ($actualizar) {
            $acciones += $accionesExtra;
            update_user_meta($userID, 'acciones', $acciones);
            update_user_meta($userID, 'ultima_actualizacion_acciones', $fechaActual->format('Y-m-d'));
            escribirLog("PASS: Acciones actualizadas para el usuario ID $userID. Nuevas acciones: $acciones", $logAcciones);
        } else {
            escribirLog("PASS: No se requiere actualización de acciones para el usuario ID $userID.", $logAcciones);
        }

        return $acciones;
    }

    // Si se deben mostrar todos los usuarios con acciones
    if ($mostrarTodos) {
        escribirLog("INFO: Iniciando proceso para mostrar todos los usuarios con acciones.", $logAcciones);

        $usuarios = get_users();
        escribirLog("INFO: Se encontraron " . count($usuarios) . " usuarios en total.", $logAcciones);

        $usuariosConAcciones = array_filter($usuarios, function ($user) {
            return get_user_meta($user->ID, 'acciones', true);
        });

        escribirLog("INFO: Se encontraron " . count($usuariosConAcciones) . " usuarios con al menos una acción.", $logAcciones);

        usort($usuariosConAcciones, function ($a, $b) {
            return get_user_meta($b->ID, 'acciones', true) - get_user_meta($a->ID, 'acciones', true);
        });

        $usuariosProConAcciones = 0;
        $resultados = [];
        foreach ($usuariosConAcciones as $user) {
            $acciones = get_user_meta($user->ID, 'acciones', true);
            if (get_user_meta($user->ID, 'user_pro', true)) {
                $acciones = actualizarAcciones($user->ID, $acciones, $accionesExtra, $fechaActual, $logAcciones);
                $usuariosProConAcciones++;
            }
            if ($acciones) {
                $resultado = [
                    'usuario' => $user->user_login,
                    'acciones' => $acciones,
                    'valor' => $acciones * $valAcc,
                    'participacion' => ($acciones / $totalAcciones) * 100
                ];
                $resultados[] = $resultado;

                // Guardar en el log de respaldo
                $mensajeRespaldo = "Usuario: {$resultado['usuario']}, Acciones: {$resultado['acciones']}, Valor: {$resultado['valor']}, Participación: {$resultado['participacion']}%";
                escribirLog($mensajeRespaldo, $logRespaldo);
            }
        }

        escribirLog("INFO: Se encontraron " . $usuariosProConAcciones . " usuarios 'Pro' con al menos una acción.", $logAcciones);

        return $resultados;
    }

    // Para el usuario actual
    $usuarioActual = wp_get_current_user();
    escribirLog("INFO: Procesando usuario actual: {$usuarioActual->user_login} (ID: {$usuarioActual->ID})", $logAcciones);

    $acciones = get_user_meta($usuarioActual->ID, 'acciones', true);

    if (!$acciones) {
        escribirLog("ERROR: El usuario {$usuarioActual->user_login} no tiene acciones.", $logAcciones);
        return 'No tienes acciones.';
    }

    if (get_user_meta($usuarioActual->ID, 'user_pro', true)) {
        $acciones = actualizarAcciones($usuarioActual->ID, $acciones, $accionesExtra, $fechaActual, $logAcciones);
    }

    $resultado = [
        'usuario' => $usuarioActual->user_login,
        'acciones' => $acciones,
        'valor' => $acciones * $valAcc,
        'participacion' => ($acciones / $totalAcciones) * 100
    ];

    // Guardar en el log de respaldo
    $mensajeRespaldo = "Usuario: {$resultado['usuario']}, Acciones: {$resultado['acciones']}, Valor: {$resultado['valor']}, Participación: {$resultado['participacion']}%";
    escribirLog($mensajeRespaldo, $logRespaldo);

    return $resultado;
}


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



