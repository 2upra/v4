<?php

function calc_ing($m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    global $wpdb;
    $accTot = 810000;
    $tDesc = 0.10;
    $cGan = 0.05;
    $volatilidad = 0.01; 
    $resultados = $wpdb->get_results("
        SELECT user_id, meta_value AS acciones
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'acciones' AND user_id != 1
    ");
    $numAccionesUsuarios = array_map(function($row) {
        return (int) $row->acciones;
    }, $resultados);
    $totalAccionesUsuarios = array_sum($numAccionesUsuarios);
    $oferta = $totalAccionesUsuarios > 0 ? $totalAccionesUsuarios / $accTot : 1;

    $numUsuarios = count($numAccionesUsuarios);
    $demanda = $numUsuarios > 0 ? 1 / (1 + (array_sum(array_map(fn($acciones) => $acciones / $totalAccionesUsuarios, $numAccionesUsuarios)) / $numUsuarios) * 0.8) : 1;
    $ajusteOfertaDemanda = $oferta > 0 ? min(max($demanda / $oferta, 0.9), 1.1) : 1;

    $ingM = array_merge(
        array_fill(0, 6, 35.5),
        array_fill(0, 6, 35.5),
        array_fill(0, 12, 60),
        array_fill(0, 12, 125),
        array_fill(0, max(0, $m - 36), 250)
    );

    if (!empty($ingresosReales)) {
        $mesActual = (new DateTime($fechaInicio))->diff(new DateTime())->m + 1;
        $mesActual = min($mesActual, count($ingM));
        for ($i = 0; $i < min($mesActual, count($ingresosReales)); $i++) {
            $ingM[$i] = $ingresosReales[$i];
        }
        $ajusteDinamico = pow(array_product(array_map(
            fn($i) => $ingresosReales[$i] / ($ingM[$i] * 1.5), // Ajuste más conservador
            range(0, count($ingresosReales) - 1)
        )), 1 / count($ingresosReales));
        $ajusteDinamico = min(max($ajusteDinamico, 0.95), 1.05);

        for ($i = count($ingresosReales); $i < count($ingM); $i++) {
            $ingM[$i] *= $ajusteDinamico;
        }
    }

    $ingM = array_map(fn($ing) => $ing * $ajusteOfertaDemanda, $ingM);
    $ingM = array_map(fn($ing) => $ing * (1 + $volatilidad * (rand(-10, 10) / 100)), $ingM); // Volatilidad más controlada
    $ingM = array_slice($ingM, 0, $m);

    $pIng = array_sum($ingM) / count($ingM);
    $aumPM = (end($ingM) - $ingM[0]) / (count($ingM) - 1);
    $aumPM = min(max($aumPM, -2), 2); // Rango más conservador para el crecimiento mensual

    $tIngE = array_sum(array_map(
        fn($i) => ($pIng + $aumPM * $i) * (1 + $cGan),
        range(1, $m)
    ));
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




