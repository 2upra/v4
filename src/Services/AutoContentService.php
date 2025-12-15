<?php

/**
 * @deprecated Usar Kamples\Services\Contenido\AutoContentService en su lugar.
 * Este archivo se mantiene por compatibilidad. Será eliminado en futuras versiones.
 */

namespace Kamples\Services;

use Kamples\Services\Contenido\AutoContentService as NewAutoContentService;

class AutoContentService
{
    private static ?AutoContentService $instancia = null;
    private NewAutoContentService $servicio;

    private function __construct()
    {
        $this->servicio = NewAutoContentService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function mejorarDescripcionAudioPro(int $postId, string $archivoAudio): void
    {
        $this->servicio->mejorarDescripcionAudioPro($postId, $archivoAudio);
    }

    public function procesarUnAudio(): void
    {
        $this->servicio->procesarUnAudio();
    }

    public function rehacerNombreAudio(int $postId, string $archivoAudio): ?string
    {
        return $this->servicio->rehacerNombreAudio($postId, $archivoAudio);
    }
}
