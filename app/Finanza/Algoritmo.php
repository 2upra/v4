<?php

function calc_ing($m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    global $wpdb;
    $accTot = 810000; // Total de acciones emitidas
    $tDesc = 0.10; // Tasa de descuento
    $cGan = 0.05; // Costo de capital
    $volatilidad = 0.01; // Volatilidad del mercado

    // Obtener las acciones de los usuarios desde la base de datos
    $resultados = $wpdb->get_results("
        SELECT user_id, meta_value AS acciones
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'acciones' AND user_id != 1
    ");
    
    // Convertir las acciones a un array de enteros
    $numAccionesUsuarios = array_map(function($row) {
        return (int) $row->acciones;
    }, $resultados);
    
    // Sumar el total de acciones de los usuarios
    $totalAccionesUsuarios = array_sum($numAccionesUsuarios);
    
    // Calcular las acciones disponibles para comprar
    $accionesDisponibles = $accTot - $totalAccionesUsuarios;
    
    // Calcular el factor de escasez
    $factorEscasez = 1 + (($accTot - $accionesDisponibles) / $accTot);

    // Ingresos mensuales proyectados
    $ingM = array_merge(
        array_fill(0, 6, 35.5),
        array_fill(0, 6, 35.5),
        array_fill(0, 12, 60),
        array_fill(0, 12, 125),
        array_fill(0, max(0, $m - 36), 250)
    );

    // Ajustar ingresos proyectados con ingresos reales si están disponibles
    if (!empty($ingresosReales)) {
        $mesActual = (new DateTime($fechaInicio))->diff(new DateTime())->m + 1;
        $mesActual = min($mesActual, count($ingM));
        
        for ($i = 0; $i < min($mesActual, count($ingresosReales)); $i++) {
            $ingM[$i] = $ingresosReales[$i];
        }
        
        $ajusteDinamico = pow(array_product(array_map(
            fn($i) => $ingresosReales[$i] / ($ingM[$i] * 1.5),
            range(0, count($ingresosReales) - 1)
        )), 1 / count($ingresosReales));
        
        $ajusteDinamico = min(max($ajusteDinamico, 0.95), 1.05);

        for ($i = count($ingresosReales); $i < count($ingM); $i++) {
            $ingM[$i] *= $ajusteDinamico;
        }
    }

    // Ajustar ingresos proyectados por el factor de escasez
    $ingM = array_map(fn($ing) => $ing * $factorEscasez, $ingM);
    
    // Aplicar volatilidad de manera controlada
    $ingM = array_map(fn($ing) => $ing * (1 + $volatilidad * (rand(-10, 10) / 100)), $ingM);
    
    // Limitar los ingresos proyectados al número de meses especificado
    $ingM = array_slice($ingM, 0, $m);

    // Calcular el promedio de ingresos
    $pIng = array_sum($ingM) / count($ingM);
    
    // Calcular el aumento promedio mensual de ingresos
    $aumPM = (end($ingM) - $ingM[0]) / (count($ingM) - 1);
    $aumPM = min(max($aumPM, -2), 2); // Rango más conservador para el crecimiento mensual

    // Calcular el total de ingresos esperados ajustados por el costo de capital
    $tIngE = array_sum(array_map(
        fn($i) => ($pIng + $aumPM * $i) * (1 + $cGan),
        range(1, $m)
    ));
    
    // Calcular el valor de la empresa descontado
    $valEmp = $tIngE / (1 + $tDesc);
    
    // Calcular el valor de las acciones
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




