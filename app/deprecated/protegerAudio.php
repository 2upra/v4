<?php

/**
 * Wrappers deprecados para funciones de protegerAudio.php
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Services\AudioProteccionService en su lugar.
 * @package app\deprecated
 */

use Kamples\Services\AudioProteccionService;

/* Inicializar el servicio */

AudioProteccionService::obtenerInstancia();

/**
 * @deprecated Usar AudioProteccionService::regenerarLite()
 */
function regenerarLite(): void
{
    $service = AudioProteccionService::obtenerInstancia();
    $service->regenerarLite();
}

/**
 * @deprecated Usar AudioProteccionService::agregarIntervalos()
 */
function intervalo_cada_seis_horas($schedules): array
{
    $service = AudioProteccionService::obtenerInstancia();
    return $service->agregarIntervalos($schedules);
}

/**
 * @deprecated Usar AudioProteccionService::agregarIntervalos()
 */
function minutos55($schedules): array
{
    $service = AudioProteccionService::obtenerInstancia();
    return $service->agregarIntervalos($schedules);
}

/**
 * @deprecated Usar AudioProteccionService::optimizar64kAudios()
 */
function optimizar64kAudios($limite = 10000): void
{
    $service = AudioProteccionService::obtenerInstancia();
    $service->optimizar64kAudios($limite);
}

/**
 * @deprecated Usar AudioProteccionService::optimizarAudioPost()
 */
function optimizarAudioPost($postId): void
{
    $service = AudioProteccionService::obtenerInstancia();
    $service->optimizarAudioPost((int)$postId);
}
