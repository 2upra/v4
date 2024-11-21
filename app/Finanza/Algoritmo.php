<?

function calc_ing($m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    global $wpdb;

    // Configuración inicial
    $accTot = 810000;    // Total de acciones
    $tDesc = 0.10;       // Tasa de descuento
    $cGan = 0.05;        // Crecimiento de ganancias
    $volatilidad = 0.01; // Volatilidad

    // Validación de entradas
    validarEntradas($m, $ingresosReales, $fechaInicio);

    // Obtención de las acciones de los usuarios
    $resultados = $wpdb->get_results("
        SELECT user_id, meta_value AS acciones
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'acciones' AND user_id != 1
    ");

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

// Funciones auxiliares

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

?>



