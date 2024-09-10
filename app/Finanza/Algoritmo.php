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

function agregar_acciones_unica_vez($user_id, $monto_pagado, $m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    $transaccion_key = 'transaccion_' . md5($monto_pagado);
    $transaccion_realizada = get_user_meta($user_id, $transaccion_key, true);

    if ($transaccion_realizada) {
        return [
            'status' => 'error',
            'message' => 'Esta transacción ya se ha realizado anteriormente.'
        ];
    }
    $valores = calc_ing($m, $ingresosReales, $fechaInicio);
    $valorAccion = $valores['valAcc'];
    $numAcciones = $monto_pagado / $valorAccion;
    $accionesActuales = (int) get_user_meta($user_id, 'acciones', true);
    $nuevasAcciones = $accionesActuales + $numAcciones;
    update_user_meta($user_id, 'acciones', $nuevasAcciones);
    update_user_meta($user_id, $transaccion_key, true);

    return [
        'status' => 'success',
        'user_id' => $user_id,
        'acciones_compradas' => $numAcciones,
        'acciones_totales' => $nuevasAcciones,
        'valor_accion' => $valorAccion
    ];
}

function formCompraAcciones() {
    if (!current_user_can('administrator')) {
        return '<p>No tienes permisos para ver este formulario.</p>';
    }

    ob_start();
    ?>
    <form id="formulario-acciones" method="post">
        <label for="user_id">ID de Usuario:</label>
        <input type="number" id="user_id" name="user_id" required>
        
        <label for="monto_pagado">Monto Pagado:</label>
        <input type="number" id="monto_pagado" name="monto_pagado" required>
        
        <input type="submit" name="submit_acciones" value="Agregar Acciones">
    </form>
    <?php
    if (isset($_POST['submit_acciones'])) {
        $user_id = intval($_POST['user_id']);
        $monto_pagado = floatval($_POST['monto_pagado']);
        
        $resultado = agregar_acciones_unica_vez($user_id, $monto_pagado);
        
        if ($resultado['status'] === 'success') {
            echo '<p>Acciones agregadas exitosamente.</p>';
            echo '<p>ID de Usuario: ' . $resultado['user_id'] . '</p>';
            echo '<p>Acciones Compradas: ' . $resultado['acciones_compradas'] . '</p>';
            echo '<p>Acciones Totales: ' . $resultado['acciones_totales'] . '</p>';
            echo '<p>Valor de la Acción: ' . $resultado['valor_accion'] . '</p>';
        } else {
            echo '<p>Error: ' . $resultado['message'] . '</p>';
        }
    }
    return ob_get_clean();
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




