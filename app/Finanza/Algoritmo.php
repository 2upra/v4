<?

function calc_ing($m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    global $wpdb;

    // Definición de constantes y variables iniciales
    $accTot = 810000;    // Total de acciones
    $tDesc = 0.10;       // Tasa de descuento
    $cGan = 0.05;        // Crecimiento de ganancias
    $volatilidad = 0.01; // Volatilidad

    // Obtención de las acciones de los usuarios desde la base de datos
    $resultados = $wpdb->get_results("
        SELECT user_id, meta_value AS acciones
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'acciones' AND user_id != 1
    ");

    // Sumatoria de las acciones de los usuarios
    $totalAccionesUsuarios = array_sum(array_map(function($row) {
        return (int) $row->acciones;
    }, $resultados));

    // Cálculo de acciones disponibles y factor de escasez
    $accionesDisponibles = $accTot - $totalAccionesUsuarios;
    $factorEscasez = 1 + ($totalAccionesUsuarios / $accTot);

    // Definición de los ingresos mensuales estimados
    $ingM = array_merge(
        array_fill(0, 12, 35.5),  // Meses 1-12
        array_fill(0, 12, 60),    // Meses 13-24
        array_fill(0, 12, 125),   // Meses 25-36
        array_fill(0, max(0, $m - 36), 250) // Meses restantes
    );

    // Ajuste de los ingresos según los ingresos reales proporcionados
    if (!empty($ingresosReales)) {
        // Cálculo del mes actual basado en la fecha de inicio
        $fechaInicioObj = new DateTime($fechaInicio);
        $fechaActualObj = new DateTime();
        $mesActual = (($fechaActualObj->format('Y') - $fechaInicioObj->format('Y')) * 12) + ($fechaActualObj->format('n') - $fechaInicioObj->format('n')) + 1;
        $mesActual = min($mesActual, count($ingM));
        $numIngresosReales = min($mesActual, count($ingresosReales));

        // Reemplazo de los ingresos estimados por los reales
        for ($i = 0; $i < $numIngresosReales; $i++) {
            $ingM[$i] = $ingresosReales[$i];
        }

        // Cálculo del ajuste dinámico
        $ratios = [];
        for ($i = 0; $i < $numIngresosReales; $i++) {
            $denominador = $ingM[$i] * 1.5;
            if ($denominador != 0) {
                $ratios[] = $ingresosReales[$i] / $denominador;
            }
        }
        if (!empty($ratios)) {
            $productoRatios = array_product($ratios);
            $ajusteDinamico = pow($productoRatios, 1 / count($ratios));
            $ajusteDinamico = min(max($ajusteDinamico, 0.95), 1.05);
        } else {
            $ajusteDinamico = 1;
        }

        // Aplicación del ajuste dinámico a los ingresos futuros
        for ($i = $numIngresosReales; $i < count($ingM); $i++) {
            $ingM[$i] *= $ajusteDinamico;
        }
    }

    // Aplicación del factor de escasez y volatilidad
    $ingM = array_map(function($ing) use ($factorEscasez, $volatilidad) {
        $variacion = (mt_rand(-100, 100) / 100) * $volatilidad;
        return $ing * $factorEscasez * (1 + $variacion);
    }, $ingM);

    // Limitación del arreglo de ingresos al número de meses especificado
    $ingM = array_slice($ingM, 0, $m);

    // Cálculo del ingreso promedio
    $pIng = array_sum($ingM) / max(count($ingM), 1);

    // Cálculo del aumento promedio mensual
    $numMeses = count($ingM);
    if ($numMeses > 1) {
        $aumPM = ($ingM[$numMeses - 1] - $ingM[0]) / ($numMeses - 1);
    } else {
        $aumPM = 0;
    }
    $aumPM = min(max($aumPM, -2), 2);

    // Estimación total de ingresos
    $tIngE = 0;
    for ($i = 1; $i <= $m; $i++) {
        $tIngE += ($pIng + $aumPM * $i) * (1 + $cGan);
    }

    // Cálculo del valor de la empresa y de la acción
    $valEmp = $tIngE / (1 + $tDesc);
    $valAcc = $valEmp / $accTot;

    return [
        'valEmp' => $valEmp,
        'valAcc' => $valAcc,
        'pIng' => $pIng,
        'accionesDisponibles' => $accionesDisponibles
    ];
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

?>



