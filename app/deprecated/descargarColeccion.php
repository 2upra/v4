<?php

/**
 * Wrappers deprecados para funciones de descarga de colecciones
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Services\ColeccionDescargaService y
 *             Kamples\Views\Components\ColeccionComponents en su lugar.
 * @package app\deprecated
 */

use Kamples\Services\ColeccionDescargaService;
use Kamples\Views\Components\ColeccionComponents;

/**
 * @deprecated Usar ColeccionDescargaService::procesarColeccion()
 */
if (!function_exists('procesarColeccion')) {
    function procesarColeccion($postId, $userId, $sync = false)
    {
        $service = ColeccionDescargaService::obtenerInstancia();
        return $service->procesarColeccion((int)$postId, (int)$userId, (bool)$sync);
    }
}

/**
 * @deprecated Usar ColeccionDescargaService::generarEnlaceDescarga()
 */
if (!function_exists('generarEnlaceDescargaColeccion')) {
    function generarEnlaceDescargaColeccion($userId, $zipPath, $postId)
    {
        $service = ColeccionDescargaService::obtenerInstancia();
        return $service->generarEnlaceDescarga((int)$userId, (string)$zipPath, (int)$postId);
    }
}

/**
 * @deprecated Manejado por ColeccionDescargaService::procesarDescargaDesdeToken()
 */
if (!function_exists('descargaAudioColeccion')) {
    function descargaAudioColeccion(): void
    {
        $service = ColeccionDescargaService::obtenerInstancia();
        $service->procesarDescargaDesdeToken();
    }
}

/**
 * @deprecated Uso interno de ColeccionDescargaService
 */
if (!function_exists('agregarArchivosAlZip')) {
    function agregarArchivosAlZip(\ZipArchive &$zip, array $samples): bool
    {
        /* Manejado internamente por ColeccionDescargaService */
        return true;
    }
}

/**
 * @deprecated Usar ColeccionDescargaService::clasificarSamples()
 */
if (!function_exists('clasificarSamples')) {
    function clasificarSamples(array $samples, int $userId): array
    {
        $service = ColeccionDescargaService::obtenerInstancia();
        return $service->clasificarSamples($samples, $userId);
    }
}

/**
 * @deprecated Uso interno de ColeccionDescargaService
 */
if (!function_exists('actualizarDescargas')) {
    function actualizarDescargas(int $userId, array $samplesNoDescargados, array $samplesDescargados): void
    {
        /* Manejado internamente por ColeccionDescargaService */
    }
}

/**
 * @deprecated Usar ColeccionComponents::renderBotonDescarga()
 */
if (!function_exists('botonDescargaColec')) {
    function botonDescargaColec($postId, $sampleCount): string
    {
        return ColeccionComponents::renderBotonDescarga((int)$postId, (int)$sampleCount);
    }
}

/**
 * @deprecated Usar ColeccionComponents::renderBotonSincronizar()
 */
if (!function_exists('botonSincronizarColec')) {
    function botonSincronizarColec($postId, $sampleCount): string
    {
        return ColeccionComponents::renderBotonSincronizar((int)$postId, (int)$sampleCount);
    }
}

/* Registrar hook de template_redirect para compatibilidad */
if (!has_action('template_redirect', 'descargaAudioColeccion')) {
    add_action('template_redirect', 'descargaAudioColeccion');
}
