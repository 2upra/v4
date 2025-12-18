<?php

/**
 * Wrappers deprecados para funciones auxiliares.
 * 
 * @deprecated Usar Kamples\Services\UtilService en su lugar.
 * @see \Kamples\Services\UtilService
 */

use Kamples\Services\Core\UtilService;
use Kamples\Controllers\Core\UtilController;

/* Registrar hooks del controlador */

$utilController = new UtilController();
$utilController->registrarHooks();

/**
 * @deprecated Usar UtilService::normalizarTexto()
 */
function normalizarTexto($texto)
{
    $utilService = UtilService::obtenerInstancia();
    return $utilService->normalizarTexto($texto);
}

/**
 * @deprecated Usar UtilService::logResumenDePuntos()
 */
function logResumenDePuntos($userId, $resumenPuntos)
{
    $utilService = UtilService::obtenerInstancia();
    $utilService->logResumenDePuntos($userId, $resumenPuntos);
}

/**
 * @deprecated Usar UtilService::tiempoRelativo()
 */
function TiempoRelativoNoti($fecha)
{
    $utilService = UtilService::obtenerInstancia();
    return $utilService->tiempoRelativo($fecha);
}

/**
 * @deprecated El controlador maneja esto automáticamente.
 */
function ajustarZonaHoraria()
{
    $utilController = new UtilController();
    $utilController->ajustarZonaHoraria();
}
