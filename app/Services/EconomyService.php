<?php
// Refactor(Org): Moved pinky-related functions and hooks from UserService.php

// Funciones de manejo de 'pinkys' movidas desde app/Functions/pinkys.php (originalmente) y luego UserService.php

function agregarPinkys($userID, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
    $nuevas_monedas = $monedas_actuales + $cantidad;
    update_user_meta($userID, 'pinky', $nuevas_monedas);
}

function restarPinkys($userID, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
    $nuevas_monedas = $monedas_actuales - $cantidad;
    update_user_meta($userID, 'pinky', $nuevas_monedas);
}

function restarPinkysEliminacion($postID)
{
    $post = get_post($postID);
    $userID = $post->post_author;

    if ($userID) {
        restarPinkys($userID, 1);
    }
}

function pinkysRegistro($user_id)
{
    $pinkys_iniciales = 10;
    update_user_meta($user_id, 'pinky', $pinkys_iniciales);
}
add_action('user_register', 'pinkysRegistro');

function restablecerPinkys()
{
    $usuarios_query = new WP_User_Query(array(
        'fields' => 'ID',
    ));

    if (!empty($usuarios_query->results)) {
        foreach ($usuarios_query->results as $userID) {
            $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
            if ($monedas_actuales < 10) {
                update_user_meta($userID, 'pinky', 10);
            }
        }
    }
}
add_action('restablecer_pinkys_semanal', 'restablecerPinkys');


if (!wp_next_scheduled('restablecer_pinkys_semanal')) {
    wp_schedule_event(time(), 'weekly', 'restablecer_pinkys_semanal');
}

// Refactor(Org): Moved financial calculation functions from app/Finanza/Algoritmo.php
function calc_ing($m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    global $wpdb;

    // Configuración inicial
    $accTot = 810000;    // Total de acciones
    $tDesc = 0.10;       // Tasa de descuento
    $cGan = 0.05;        // Crecimiento de ganancias
    $volatilidad = 0.01; // Volatilidad

    // Definir ingresos reales si no se proporcionan

    if (empty($ingresosReales)) {
        $ingresosReales = [25, 25, 25, 25, 25, 25, 25, 25, 25, 25, 25, 25, /*1 año */];
    }

    // Validación de entradas
    validarEntradas($m, $ingresosReales, $fechaInicio);

    // Obtención de las acciones de los usuarios
    $resultados = $wpdb->get_results(
        $wpdb->prepare("\n            SELECT user_id, meta_value AS acciones\n            FROM {$wpdb->usermeta}\n            WHERE meta_key = %s AND user_id != %d\n        ", 'acciones', 1)
    );
    
    // Calcular factor de escasez
    $totalAccionesUsuarios = sumarAcciones($resultados);
    $accionesDisponibles = $accTot - $totalAccionesUsuarios;
    $factorEscasez = calcularFactorEscasez($totalAccionesUsuarios, $accTot);

    // Generar ingresos estimados
    $ingM = generarIngresosEstimados($m);

    // Ajustar ingresos reales si se proporcionan
    if (!empty($ingresosReales)) {
        $ingM = ajustarIngresos($ingM, $ingresosReales, $fechaInicio);
    }

    // Aplicar volatilidad y factor de escasez
    $ingM = array_map(function ($ing) use ($factorEscasez, $volatilidad) {
        return aplicarVolatilidad($ing, $factorEscasez, $volatilidad);
    }, $ingM);

    // Limitar los ingresos a los meses especificados
    $ingM = array_slice($ingM, 0, $m);

    // Calcular métricas clave
    $pIng = calcularPromedioIngresos($ingM);
    $aumPM = calcularAumentoPromedioMensual($ingM);
    $tIngE = estimarIngresosTotales($pIng, $aumPM, $m, $cGan);
    $valEmp = calcularValorEmpresa($tIngE, $tDesc);
    $valAcc = calcularValorAccion($valEmp, $accTot);

    return [
        'valEmp' => $valEmp,
        'valAcc' => $valAcc,
        'pIng' => $pIng,
        'accionesDisponibles' => $accionesDisponibles
    ];
}

// Función para obtener ingresos reales desde la base de datos
function obtenerIngresosRealesDesdeDB($wpdb, $fechaInicio)
{
    $query = "\n        SELECT ingreso, fecha\n        FROM {$wpdb->prefix}ingresos\n        WHERE fecha >= %s\n        ORDER BY fecha ASC\n    ";
    $resultados = $wpdb->get_results($wpdb->prepare($query, $fechaInicio));

    return array_map(function ($row) {
        return (float) $row->ingreso;
    }, $resultados);
}


function validarEntradas($m, $ingresosReales, $fechaInicio)
{
    if (!is_int($m) || $m <= 0) {
        throw new InvalidArgumentException('El número de meses debe ser un entero positivo.');
    }

    if (!is_array($ingresosReales) || array_filter($ingresosReales, 'is_numeric') !== $ingresosReales) {
        throw new InvalidArgumentException('Ingresos reales debe ser un array de números.');
    }

    if (DateTime::createFromFormat('Y-m-d', $fechaInicio) === false) {
        throw new InvalidArgumentException('La fecha de inicio debe estar en formato YYYY-MM-DD.');
    }
}

function sumarAcciones($resultados)
{
    return array_sum(array_map(function ($row) {
        return (int) $row->acciones;
    }, $resultados));
}

function calcularFactorEscasez($accionesUsuarios, $totalAcciones)
{
    return 1 + ($accionesUsuarios / $totalAcciones);
}

function generarIngresosEstimados($m)
{
    return array_merge(
        array_fill(0, 12, 35.5),  // Meses 1-12
        array_fill(0, 12, 60),    // Meses 13-24
        array_fill(0, 12, 125),   // Meses 25-36
        array_fill(0, max(0, $m - 36), 250) // Meses restantes
    );
}

function ajustarIngresos($ingM, $ingresosReales, $fechaInicio)
{
    $fechaInicioObj = new DateTime($fechaInicio);
    $fechaActualObj = new DateTime();
    $mesActual = (($fechaActualObj->format('Y') - $fechaInicioObj->format('Y')) * 12) +
        ($fechaActualObj->format('n') - $fechaInicioObj->format('n')) + 1;
    $mesActual = min($mesActual, count($ingM));
    $numIngresosReales = min($mesActual, count($ingresosReales));

    // Sustituir ingresos reales
    for ($i = 0; $i < $numIngresosReales; $i++) {
        $ingM[$i] = $ingresosReales[$i];
    }

    // Calcular ajuste dinámico
    $ratios = [];
    for ($i = 0; $i < $numIngresosReales; $i++) {
        $denominador = $ingM[$i] * 1.5;
        if ($denominador != 0) {
            $ratios[] = $ingresosReales[$i] / $denominador;
        }
    }

    $ajusteDinamico = !empty($ratios) ? pow(array_product($ratios), 1 / count($ratios)) : 1;
    $ajusteDinamico = min(max($ajusteDinamico, 0.95), 1.05);

    // Aplicar ajuste dinámico
    for ($i = $numIngresosReales; $i < count($ingM); $i++) {
        $ingM[$i] *= $ajusteDinamico;
    }

    return $ingM;
}

function aplicarVolatilidad($ing, $factorEscasez, $volatilidad)
{
    $variacion = (mt_rand(-100, 100) / 100) * $volatilidad;
    return $ing * $factorEscasez * (1 + $variacion);
}

function calcularPromedioIngresos($ingM)
{
    return array_sum($ingM) / max(count($ingM), 1);
}

function calcularAumentoPromedioMensual($ingM)
{
    $numMeses = count($ingM);
    if ($numMeses > 1) {
        return ($ingM[$numMeses - 1] - $ingM[0]) / ($numMeses - 1);
    }
    return 0;
}

function estimarIngresosTotales($pIng, $aumPM, $m, $cGan)
{
    $tIngE = 0;
    for ($i = 1; $i <= $m; $i++) {
        $tIngE += ($pIng + $aumPM * $i) * (1 + $cGan);
    }
    return $tIngE;
}

function calcularValorEmpresa($tIngE, $tDesc)
{
    return $tIngE / (1 + $tDesc);
}

function calcularValorAccion($valEmp, $accTot)
{
    return $valEmp / $accTot;
}

function valores()
{
    $resultados = calc_ing();

    // Formateo de los valores monetarios con separación adecuada
    $pIng = "$" . number_format($resultados['pIng'], 2, ',', '.');
    $valEmp = "$" . number_format($resultados['valEmp'], 2, ',', '.');
    $valAcc = "$" . number_format($resultados['valAcc'], 2, ',', '.');

    // Construcción del output HTML
    $output = '<div class="XXDD valorbolsa1" title="Ingresos promedio estimado">' . $pIng . '</div>';
    $output .= '<div class="XXDD valorbolsa1" title="Valor de la empresa estimado">' . $valEmp . '</div>';
    $output .= '<div class="XXDD valorbolsa1" title="Valor de la acción estimado">' . $valAcc . '</div>';

    return $output;
}

// Refactor(Org): Moved function sumaAcciones from app/Finanza/SumaMensual.php
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

?>
