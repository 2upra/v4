<?php

function calc_ing($m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01', $numAccionesUsuarios = [])
{
    $accTot = 810000;
    $tDesc = 0.10;
    $cGan = 0.05;
    $volatilidad = 0.02; // Factor de volatilidad

    // Calcular oferta y demanda en base al número de acciones de los usuarios
    $totalAccionesUsuarios = array_sum($numAccionesUsuarios);
    
    // Evitar división por cero en la oferta
    $oferta = $totalAccionesUsuarios > 0 ? $totalAccionesUsuarios / $accTot : 1;

    // Calcular la concentración de acciones (demanda)
    $numUsuarios = count($numAccionesUsuarios);
    
    // Evitar división por cero en la demanda
    $demanda = $numUsuarios > 0 ? 1 / (1 + (array_sum(array_map(fn($acciones) => $acciones / $totalAccionesUsuarios, $numAccionesUsuarios)) / $numUsuarios)) : 1;

    // Ajuste basado en oferta y demanda
    $ajusteOfertaDemanda = $oferta > 0 ? $demanda / $oferta : 1;

    // Generación de ingresos mensuales base
    $ingM = array_merge(
        array_fill(0, 6, 35.5),
        array_fill(0, 6, 35.5),
        array_fill(0, 12, 60),
        array_fill(0, 12, 125),
        array_fill(0, max(0, $m - 36), 250)
    );

    // Ajuste de ingresos reales si se proporcionan
    if (!empty($ingresosReales)) {
        $mesActual = (new DateTime($fechaInicio))->diff(new DateTime())->m + 1;
        $mesActual = min($mesActual, count($ingM));
        for ($i = 0; $i < min($mesActual, count($ingresosReales)); $i++) {
            $ingM[$i] = $ingresosReales[$i];
        }
        $ajusteDinamico = pow(array_product(array_map(
            fn($i) => $ingresosReales[$i] / ($ingM[$i] * 2),
            range(0, count($ingresosReales) - 1)
        )), 1 / count($ingresosReales));

        for ($i = count($ingresosReales); $i < count($ingM); $i++) {
            $ingM[$i] *= $ajusteDinamico;
        }
    }

    // Aplicar ajuste de oferta y demanda
    $ingM = array_map(fn($ing) => $ing * $ajusteOfertaDemanda, $ingM);

    // Aplicar volatilidad
    $ingM = array_map(fn($ing) => $ing * (1 + $volatilidad * (rand(-100, 100) / 100)), $ingM);

    // Recortar a los meses solicitados
    $ingM = array_slice($ingM, 0, $m);

    // Cálculo del promedio de ingresos
    $pIng = array_sum($ingM) / count($ingM);

    // Cálculo del aumento promedio mensual
    $aumPM = (end($ingM) - $ingM[0]) / (count($ingM) - 1);

    // Cálculo del total de ingresos esperados
    $tIngE = array_sum(array_map(
        fn($i) => ($pIng + $aumPM * $i) * (1 + $cGan),
        range(1, $m)
    ));

    // Valor de la empresa y valor por acción
    $valEmp = $tIngE / (1 + $tDesc);
    $valAcc = $valEmp / $accTot;

    return [
        'valEmp' => $valEmp,
        'valAcc' => $valAcc,
        'pIng' => $pIng
    ];
}


function valores()
{
    global $pIng, $valEmp, $valAcc;

    $resultados = calc_ing();
    
    $pIng = "$" . number_format($resultados['pIng'], 2, '.', '.');
    $valEmp = "$" . number_format($resultados['valEmp'], 2, '.', '.');
    $valAcc = "$" . number_format($resultados['valAcc'], 2, '.', '.');

    $output = '<div class="XXDD valorbolsa1" title="Ingresos promedio estimado">' . $pIng . '</div>';
    $output .= '<div class="XXDD valorbolsa1" title="Valor de la empresa estimado">' . $valEmp . '</div>';
    $output .= '<div class="XXDD valorbolsa1" title="Valor de la acción estimado">' . $valAcc . '</div>';

    return $output;
}




