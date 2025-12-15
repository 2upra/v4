<?php

/**
 * @deprecated Usar Kamples\Services\Core\DescargaService en su lugar.
 * Este archivo se mantiene por compatibilidad. Será eliminado en futuras versiones.
 */

namespace Kamples\Services;

use Kamples\Services\Core\DescargaService as NewDescargaService;

class DescargaService
{
    private static ?DescargaService $instancia = null;
    private NewDescargaService $servicio;

    private function __construct()
    {
        $this->servicio = NewDescargaService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function procesarDescarga(int $userId, int $postId, bool $esColeccion = false, bool $sync = false): array
    {
        return $this->servicio->procesarDescarga($userId, $postId, $esColeccion, $sync);
    }

    public function generarEnlaceDescarga(int $userId, int $audioId): string
    {
        return $this->servicio->generarEnlaceDescarga($userId, $audioId);
    }

    public function manejarDescarga(string $token): bool
    {
        return $this->servicio->manejarDescarga($token);
    }

    public function yaDescargado(int $userId, int $postId): bool
    {
        return $this->servicio->yaDescargado($userId, $postId);
    }
}
