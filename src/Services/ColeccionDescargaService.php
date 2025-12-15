<?php

/**
 * ColeccionDescargaService - Wrapper Deprecated
 * 
 * @deprecated Usar Kamples\Services\Coleccion\ColeccionDescargaService
 * @package Kamples\Services
 */

namespace Kamples\Services;

use Kamples\Services\Coleccion\ColeccionDescargaService as NuevoColeccionDescargaService;

class ColeccionDescargaService
{
    private static ?ColeccionDescargaService $instancia = null;
    private NuevoColeccionDescargaService $servicio;

    private function __construct()
    {
        $this->servicio = NuevoColeccionDescargaService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function procesarColeccion(int $postId, int $userId, bool $sync = false)
    {
        return $this->servicio->procesarColeccion($postId, $userId, $sync);
    }

    public function generarEnlaceDescarga(int $userId, string $zipPath, int $postId): string
    {
        return $this->servicio->generarEnlaceDescarga($userId, $zipPath, $postId);
    }

    public function procesarDescargaDesdeToken(): void
    {
        $this->servicio->procesarDescargaDesdeToken();
    }

    public function clasificarSamples(array $samples, int $userId): array
    {
        return $this->servicio->clasificarSamples($samples, $userId);
    }
}
