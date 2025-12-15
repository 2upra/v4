<?php

/**
 * DEPRECATED: Wrapper de compatibilidad
 * 
 * Este archivo redirige al nuevo servicio refactorizado.
 * Ubicación nueva: src/Services/Audio/AudioProcessingService.php
 * 
 * @deprecated Usar Kamples\Services\Audio\AudioProcessingService
 * @package Kamples\Services
 */

namespace Kamples\Services;

use Kamples\Services\Audio\AudioProcessingService as NuevoAudioProcessingService;

class AudioProcessingService
{
    private static ?AudioProcessingService $instancia = null;
    private NuevoAudioProcessingService $servicio;

    private function __construct()
    {
        $this->servicio = NuevoAudioProcessingService::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio
     * @deprecated Usar Kamples\Services\Audio\AudioProcessingService::obtenerInstancia()
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
    public function procesarAudioLigero(int $postId, int $audioId, int $index): bool
    {
        return $this->servicio->procesarAudioLigero($postId, $audioId, $index);
    }

    /**
     * @deprecated Usar el servicio nuevo directamente
     */
    public function analizarYGuardarMetasAudio(
        int $postId,
        string $audioPath,
        int $index,
        ?string $nombreArchivo = null,
        ?string $carpeta = null,
        ?string $carpetaAbuela = null
    ): void {
        $this->servicio->analizarYGuardarMetasAudio($postId, $audioPath, $index, $nombreArchivo, $carpeta, $carpetaAbuela);
    }
}
