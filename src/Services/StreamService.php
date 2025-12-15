<?php

/**
 * StreamService - Wrapper Deprecated
 * 
 * @deprecated Usar Kamples\Services\Audio\StreamService
 * @package Kamples\Services
 */

namespace Kamples\Services;

use Kamples\Services\Audio\StreamService as NuevoStreamService;

class StreamService
{
    private static ?StreamService $instancia = null;
    private NuevoStreamService $servicio;

    private function __construct()
    {
        $this->servicio = NuevoStreamService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function bloquearAccesoDirecto(): void
    {
        $this->servicio->bloquearAccesoDirecto();
    }

    public function generarUrlSegura($audioId)
    {
        return $this->servicio->generarUrlSegura($audioId);
    }

    public function generarToken($audioId)
    {
        return $this->servicio->generarToken($audioId);
    }

    public function verificarToken(string $token): bool
    {
        return $this->servicio->verificarToken($token);
    }

    public function streamAudio(string $token)
    {
        return $this->servicio->streamAudio($token);
    }

    public function usuarioEsAdminOPro(int $userId): bool
    {
        return $this->servicio->usuarioEsAdminOPro($userId);
    }

    public function limpiarCache(): void
    {
        $this->servicio->limpiarCache();
    }

    public function programarLimpiezaCache(): void
    {
        $this->servicio->programarLimpiezaCache();
    }
}
