<?

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
    <?
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

