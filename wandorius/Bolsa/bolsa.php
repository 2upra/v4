<?php

function calc_ing($m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    $accTot = 810000;
    $tDesc = 0.10;
    $cGan = 0.05;
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
            fn($i) => $ingresosReales[$i] / ($ingM[$i] * 2),
            range(0, count($ingresosReales) - 1)
        )), 1 / count($ingresosReales));

        for ($i = count($ingresosReales); $i < count($ingM); $i++) {
            $ingM[$i] *= $ajusteDinamico;
        }
    }
    $ingM = array_slice($ingM, 0, $m);
    $pIng = array_sum($ingM) / count($ingM);
    $aumPM = (end($ingM) - $ingM[0]) / (count($ingM) - 1);
    $tIngE = array_sum(array_map(
        fn($i) => ($pIng + $aumPM * $i) * (1 + $cGan),
        range(1, $m)
    ));
    return [
        'valEmp' => $tIngE / (1 + $tDesc),
        'valAcc' => $tIngE / (1 + $tDesc) / $accTot,
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




