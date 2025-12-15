<?php

/**
 * DEPRECATED: Wrapper de compatibilidad
 * 
 * Este archivo redirige al nuevo servicio refactorizado.
 * Ubicación nueva: src/Services/Contenido/AutoPostService.php
 * 
 * @deprecated Usar Kamples\Services\Contenido\AutoPostService
 * @package Kamples\Services
 */

namespace Kamples\Services;

use Kamples\Services\Contenido\AutoPostService as NuevoAutoPostService;

class AutoPostService
{
    private static ?AutoPostService $instancia = null;
    private NuevoAutoPostService $servicio;

    private function __construct()
    {
        $this->servicio = NuevoAutoPostService::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio
     * @deprecated Usar Kamples\Services\Contenido\AutoPostService::obtenerInstancia()
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * @deprecated Usar el servicio nuevo directamente
     */
    public function procesarAudio(string $rutaOriginal): void
    {
        $this->servicio->procesarAudio($rutaOriginal);
    }

    /**
     * @deprecated Usar el servicio nuevo directamente
     */
    public function crearAutPost(?string $rutaOriginal, ?string $rutaWpLite, $fileId = null, $autorId = 44, $postOriginal = null)
    {
        return $this->servicio->crearAutPost($rutaOriginal, $rutaWpLite, $fileId, $autorId, $postOriginal);
    }

    /**
     * @deprecated Usar el servicio nuevo directamente
     */
    public function adjuntarArchivoAut($archivo, $postId, $fileId = null)
    {
        return $this->servicio->adjuntarArchivoAut($archivo, $postId, $fileId);
    }

    /**
     * @deprecated Usar el servicio nuevo directamente
     */
    public function procesarAudiosScan(): void
    {
        $this->servicio->procesarAudiosScan();
    }

    /**
     * @deprecated Usar el servicio nuevo directamente
     */
    public function procesarMultiples(int $postIdOriginal): void
    {
        $this->servicio->procesarMultiples($postIdOriginal);
    }
}
