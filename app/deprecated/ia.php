<?php

/**
 * Wrappers deprecados para funciones de IA.
 * 
 * @deprecated Usar Kamples\Services\IAService en su lugar.
 * @see \Kamples\Services\IAService
 */

use Kamples\Services\Contenido\IAService;
use Kamples\Controllers\Contenido\IAController;

/* Registrar hooks del controlador */

$iaController = new IAController();
$iaController->registrarHooks();

/**
 * @deprecated Usar IAService::generarDescripcionConUri()
 */
function generarDescripcionIAConURI($audio_uri, $prompt)
{
    $iaService = new IAService();
    return $iaService->generarDescripcionConUri($audio_uri, $prompt);
}

/**
 * @deprecated Usar IAService::subirArchivo()
 */
function subirArchivo($archivo_path)
{
    $iaService = new IAService();
    return $iaService->subirArchivo($archivo_path);
}

/**
 * @deprecated Usar IAService::generarDescripcion()
 */
function generarDescripcionIA($archivo_path, $prompt)
{
    $iaService = new IAService();
    return $iaService->generarDescripcion($archivo_path, $prompt);
}

/**
 * @deprecated Usar IAService::generarDescripcionPro()
 */
function generarDescripcionIAPro($archivo_path, $prompt)
{
    $iaService = new IAService();
    return $iaService->generarDescripcionPro($archivo_path, $prompt);
}
