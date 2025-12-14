<?php

/**
 * Wrappers deprecados para funciones de stream.php
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Services\StreamService y
 *             Kamples\Controllers\StreamController en su lugar.
 * @package app\deprecated
 */

use Kamples\Services\StreamService;

define('ENABLE_BROWSER_AUDIO_CACHE', true);

/**
 * @deprecated Usar StreamService::generarUrlSegura()
 */
function audioUrlSegura($audioId)
{
    $service = StreamService::obtenerInstancia();
    return $service->generarUrlSegura($audioId);
}

/**
 * @deprecated Usar StreamService::bloquearAccesoDirecto()
 */
function bloquear_acceso_directo_archivos(): void
{
    $service = StreamService::obtenerInstancia();
    $service->bloquearAccesoDirecto();
}

/**
 * @deprecated Usar StreamService::verificarToken()
 */
function verificarAudio($token): bool
{
    $service = StreamService::obtenerInstancia();
    return $service->verificarToken($token);
}

/**
 * @deprecated Usar StreamService::generarToken()
 */
function tokenAudio($audioId)
{
    $service = StreamService::obtenerInstancia();
    return $service->generarToken($audioId);
}

/**
 * @deprecated Usar StreamService::streamAudio()
 */
function audioStreamEnd($data)
{
    $service = StreamService::obtenerInstancia();
    return $service->streamAudio($data['token']);
}

/**
 * @deprecated Usar StreamService::limpiarCache()
 */
function clean_audio_cache(): void
{
    $service = StreamService::obtenerInstancia();
    $service->limpiarCache();
}

/**
 * @deprecated Usar StreamService::programarLimpiezaCache()
 */
function schedule_audio_cache_cleanup(): void
{
    $service = StreamService::obtenerInstancia();
    $service->programarLimpiezaCache();
}

/**
 * @deprecated Usar StreamService::usuarioEsAdminOPro()
 */
function usuarioEsAdminOPro($userId): bool
{
    $service = StreamService::obtenerInstancia();
    return $service->usuarioEsAdminOPro((int)$userId);
}

/**
 * @deprecated Función interna, no se usa externamente
 */
function decrementaUsosToken($uniqueId): void
{
    /* Manejado internamente por StreamService */
}

/**
 * @deprecated No es necesario registrar manualmente
 */
function unschedule_audio_cache_cleanup(): void
{
    $timestamp = wp_next_scheduled('audio_cache_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'audio_cache_cleanup');
    }
}
