<?php

/**
 * Wrappers deprecados para el módulo financiero.
 * 
 * @deprecated Usar Kamples\Services\FinanzaService y Kamples\Views\Components\FinanzaComponents
 */

use Kamples\Services\Finanza\FinanzaService;
use Kamples\Views\Components\FinanzaComponents;
use Kamples\Controllers\Finanza\FinanzaController;
use Kamples\Services\Usuario\PerfilService;

/* 
 * Funciones de cálculo de acciones
 */

/**
 * @deprecated Usar FinanzaService::obtenerInstancia()->calcularIngresos()
 */
function calc_ing($m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    return FinanzaService::obtenerInstancia()->calcularIngresos($m, $ingresosReales, $fechaInicio);
}

/**
 * @deprecated Usar FinanzaService::obtenerInstancia()->agregarAccionesUnicaVez()
 */
function agregar_acciones_unica_vez($user_id, $monto_pagado, $m = 48, $ingresosReales = [], $fechaInicio = '2024-01-01')
{
    return FinanzaService::obtenerInstancia()->agregarAccionesUnicaVez($user_id, $monto_pagado, $m, $ingresosReales, $fechaInicio);
}

/**
 * @deprecated Usar FinanzaService::obtenerInstancia()->definirAccionesUsuario()
 */
function definir_acciones_usuario($usuarios_acciones, $actualizar_si_existe = false)
{
    FinanzaService::obtenerInstancia()->definirAccionesUsuario($usuarios_acciones, $actualizar_si_existe);
}

/**
 * @deprecated Usar FinanzaService::obtenerInstancia()->obtenerHistorialAccionesUsuario()
 */
function obtenerHistorialAccionesUsuario()
{
    return FinanzaService::obtenerInstancia()->obtenerHistorialAccionesUsuario();
}

/**
 * @deprecated Usar FinanzaService::obtenerInstancia()->registrarHistorialAcciones()
 */
function registrarHistorialAcciones()
{
    FinanzaService::obtenerInstancia()->registrarHistorialAcciones();
}

/**
 * @deprecated Usar FinanzaService::obtenerInstancia()->calcularAccionPorUsuario()
 */
function calcularAccionPorUsuario($mostrarTodos = true)
{
    $datos = FinanzaService::obtenerInstancia()->calcularAccionPorUsuario($mostrarTodos);

    if (is_string($datos)) {
        return $datos;
    }

    $valAcc = FinanzaService::obtenerInstancia()->calcularIngresos(48, [])['valAcc'];

    $output = '<table><thead><tr><th>Perfil</th><th>Usuario</th><th>Valor Total</th></tr></thead><tbody>';

    foreach ($datos as $usuario) {
        $imagen = function_exists('imagenPerfil') ? imagenPerfil($usuario['user_id']) : PerfilService::obtenerInstancia()->obtenerImagenPerfil($usuario['user_id']);
        $output .= sprintf(
            '<tr><td><img src="%s" alt="%s" /></td><td>%s</td><td>$%s</td></tr>',
            esc_url($imagen),
            esc_attr($usuario['usuario']),
            esc_html($usuario['usuario']),
            number_format($usuario['valor_total'], 2, '.', '.')
        );
    }

    return $output . '</tbody></table>';
}

/**
 * @deprecated Usar FinanzaService::obtenerInstancia()->sumarAccionesMensual()
 */
function sumaAcciones($mostrarTodos = false)
{
    return FinanzaService::obtenerInstancia()->sumarAccionesMensual($mostrarTodos);
}

/**
 * @deprecated Usar FinanzaService::obtenerInstancia()->obtenerTodasLasTransacciones()
 */
function get_all_transactions()
{
    return FinanzaService::obtenerInstancia()->obtenerTodasLasTransacciones();
}

/**
 * @deprecated Usar FinanzaComponents::renderTablaTransacciones()
 */
function generate_transactions_table()
{
    return FinanzaComponents::renderTablaTransacciones();
}

/* 
 * Funciones de componentes UI
 */

/**
 * @deprecated Usar FinanzaComponents::renderPanelInversor()
 */
function panelInversor()
{
    return FinanzaComponents::renderPanelInversor();
}

/**
 * @deprecated Usar FinanzaComponents::renderValores()
 */
function valores()
{
    return FinanzaComponents::renderValores();
}

/**
 * @deprecated Usar FinanzaComponents::renderBotonDonar()
 */
function botonComprarAcciones($textoBoton = 'Donar')
{
    return FinanzaComponents::renderBotonDonar($textoBoton);
}

/**
 * @deprecated Usar FinanzaComponents::renderBotonCompra()
 */
function botonCompra($postId)
{
    return FinanzaComponents::renderBotonCompra($postId);
}

/**
 * @deprecated Usar FinanzaComponents::renderBotonSponsor()
 */
function botonSponsor()
{
    return FinanzaComponents::renderBotonSponsor();
}

/**
 * @deprecated Usar FinanzaComponents::renderModalComprarAcciones()
 */
function modalComprarAcciones()
{
    return FinanzaComponents::renderModalComprarAcciones();
}

/**
 * @deprecated Usar FinanzaComponents::renderFormCompraAcciones()
 */
function formCompraAcciones()
{
    return FinanzaComponents::renderFormCompraAcciones();
}

/* 
 * Funciones de gráficos
 */

/**
 * @deprecated Usar FinanzaComponents::renderGraficoHistorial()
 */
function graficoHistorialAcciones()
{
    return FinanzaComponents::renderGraficoHistorial();
}

/**
 * @deprecated Usar FinanzaComponents::renderGraficoCapital()
 */
function capitalValores()
{
    return FinanzaComponents::renderGraficoCapital();
}

/**
 * @deprecated Usar FinanzaComponents::renderGraficoBolsa()
 */
function bolsavalores()
{
    return FinanzaComponents::renderGraficoBolsa();
}

/* 
 * Funciones de Stripe (legacy)
 */

/**
 * @deprecated Los endpoints REST son manejados por AccionesController
 */
function crear_sesion_acciones(\WP_REST_Request $request)
{
    $controller = new \Kamples\Controllers\Finanza\AccionesController();
    return $controller->crearSesion($request);
}

/**
 * @deprecated Los endpoints REST son manejados por AccionesController
 */
function manejador_webhook_acciones(\WP_REST_Request $request)
{
    $controller = new \Kamples\Controllers\Finanza\AccionesController();
    return $controller->webhook($request);
}

/**
 * @deprecated Los endpoints REST son manejados por CompraController
 */
function crear_sesion_compra(\WP_REST_Request $request)
{
    $controller = new \Kamples\Controllers\Finanza\CompraController();
    return $controller->crearSesion($request);
}

/**
 * @deprecated Los endpoints REST son manejados por CompraController
 */
function manejador_webhook_compra(\WP_REST_Request $request)
{
    $controller = new \Kamples\Controllers\Finanza\CompraController();
    return $controller->webhook($request);
}

/**
 * @deprecated Los endpoints REST son manejados por SuscripcionController
 */
function crear_sesion_pro(\WP_REST_Request $request)
{
    $controller = new \Kamples\Controllers\Finanza\SuscripcionController();
    return $controller->crearSesion($request);
}

/**
 * @deprecated Los endpoints REST son manejados por SuscripcionController
 */
function stripe_webhook_pro(\WP_REST_Request $request)
{
    $controller = new \Kamples\Controllers\Finanza\SuscripcionController();
    return $controller->webhook($request);
}

/* 
 * Funciones de validación y utilidades
 */

if (!function_exists('validarEntradas')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
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
}

if (!function_exists('sumarAccionesArray')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function sumarAccionesArray($resultados)
    {
        return array_sum(array_map(function ($row) {
            return (int) $row->acciones;
        }, $resultados));
    }
}

if (!function_exists('calcularFactorEscasez')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function calcularFactorEscasez($accionesUsuarios, $totalAcciones)
    {
        return 1 + ($accionesUsuarios / $totalAcciones);
    }
}

if (!function_exists('generarIngresosEstimados')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function generarIngresosEstimados($m)
    {
        return array_merge(
            array_fill(0, 12, 35.5),
            array_fill(0, 12, 60),
            array_fill(0, 12, 125),
            array_fill(0, max(0, $m - 36), 250)
        );
    }
}

if (!function_exists('ajustarIngresos')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function ajustarIngresos($ingM, $ingresosReales, $fechaInicio)
    {
        $fechaInicioObj = new DateTime($fechaInicio);
        $fechaActualObj = new DateTime();
        $mesActual = (($fechaActualObj->format('Y') - $fechaInicioObj->format('Y')) * 12) +
            ($fechaActualObj->format('n') - $fechaInicioObj->format('n')) + 1;
        $mesActual = min($mesActual, count($ingM));
        $numIngresosReales = min($mesActual, count($ingresosReales));

        for ($i = 0; $i < $numIngresosReales; $i++) {
            $ingM[$i] = $ingresosReales[$i];
        }

        $ratios = [];
        for ($i = 0; $i < $numIngresosReales; $i++) {
            $denominador = $ingM[$i] * 1.5;
            if ($denominador != 0) {
                $ratios[] = $ingresosReales[$i] / $denominador;
            }
        }

        $ajusteDinamico = !empty($ratios) ? pow(array_product($ratios), 1 / count($ratios)) : 1;
        $ajusteDinamico = min(max($ajusteDinamico, 0.95), 1.05);

        for ($i = $numIngresosReales; $i < count($ingM); $i++) {
            $ingM[$i] *= $ajusteDinamico;
        }

        return $ingM;
    }
}

if (!function_exists('aplicarVolatilidad')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function aplicarVolatilidad($ing, $factorEscasez, $volatilidad)
    {
        $variacion = (mt_rand(-100, 100) / 100) * $volatilidad;
        return $ing * $factorEscasez * (1 + $variacion);
    }
}

if (!function_exists('calcularPromedioIngresos')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function calcularPromedioIngresos($ingM)
    {
        return array_sum($ingM) / max(count($ingM), 1);
    }
}

if (!function_exists('calcularAumentoPromedioMensual')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function calcularAumentoPromedioMensual($ingM)
    {
        $numMeses = count($ingM);
        if ($numMeses > 1) {
            return ($ingM[$numMeses - 1] - $ingM[0]) / ($numMeses - 1);
        }
        return 0;
    }
}

if (!function_exists('estimarIngresosTotales')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function estimarIngresosTotales($pIng, $aumPM, $m, $cGan)
    {
        $tIngE = 0;
        for ($i = 1; $i <= $m; $i++) {
            $tIngE += ($pIng + $aumPM * $i) * (1 + $cGan);
        }
        return $tIngE;
    }
}

if (!function_exists('calcularValorEmpresa')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function calcularValorEmpresa($tIngE, $tDesc)
    {
        return $tIngE / (1 + $tDesc);
    }
}

if (!function_exists('calcularValorAccion')) {
    /**
     * @deprecated Uso interno de FinanzaService
     */
    function calcularValorAccion($valEmp, $accTot)
    {
        return $valEmp / $accTot;
    }
}
