<?php

/**
 * @deprecated Usar Kamples\Services\Audio\AudioProteccionService
 * 
 * Este archivo es un wrapper de compatibilidad.
 * La funcionalidad ha sido movida a src/Services/Audio/
 *
 * @package Kamples\Services
 */

namespace Kamples\Services;

use Kamples\Services\Audio\AudioProteccionService as NuevoAudioProteccionService;

class AudioProteccionService
{
    private static ?AudioProteccionService $instancia = null;
    private NuevoAudioProteccionService $servicio;

    private function __construct()
    {
        $this->servicio = NuevoAudioProteccionService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function protegerArchivoAudio($delete, $post): bool
    {
        return $this->servicio->protegerArchivoAudio($delete, $post);
    }

    public function agregarIntervalos(array $schedules): array
    {
        return $this->servicio->agregarIntervalos($schedules);
    }

    public function regenerarLite(): void
    {
        $this->servicio->regenerarLite();
    }

    public function optimizar64kAudios(int $limite = 10000): void
    {
        $this->servicio->optimizar64kAudios($limite);
    }

    public function optimizarAudioPost(int $postId): void
    {
        $this->servicio->optimizarAudioPost($postId);
    }

    public function limpiarWaveform(int $postId): void
    {
        $this->servicio->limpiarWaveform($postId);
    }
}
