<?php

/**
 * Wrappers deprecados para funciones de vistas.
 * 
 * @deprecated Usar Kamples\Services\VistaService en su lugar.
 * @see \Kamples\Services\VistaService
 */

use Kamples\Services\VistaService;
use Kamples\Controllers\VistaController;

/* Registrar hooks del controlador */

$vistaController = new VistaController();
$vistaController->registrarHooks();

/**
 * @deprecated Usar VistaController::guardarVista() vía AJAX.
 */
function guardarVista()
{
    $vistaController = new VistaController();
    $vistaController->guardarVista();
}

/**
 * @deprecated Usar VistaService::obtenerVistasPosts()
 */
function obtenerVistasPosts($userId)
{
    $vistaService = new VistaService();
    return $vistaService->obtenerVistasPosts($userId);
}

/**
 * @deprecated Usar VistaService::limpiarVistasAntiguas()
 */
function limpiarVistasAntiguas($vistas, $dias)
{
    $vistaService = new VistaService();
    return $vistaService->limpiarVistasAntiguas($vistas, $dias);
}
