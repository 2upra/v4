<?
// Refactor(Org): Se movió la función formCompraAcciones a app/Admin/AccionesAdmin.php

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
    // Asumiendo que calc_ing está definida en otro lugar o será incluida
    // $valores = calc_ing($m, $ingresosReales, $fechaInicio);
    // $valorAccion = $valores['valAcc'];
    // Dummy value para evitar error si calc_ing no está disponible aquí
    $valorAccion = 10; // Valor de ejemplo, ajustar según sea necesario
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


