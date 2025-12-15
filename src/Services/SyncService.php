<?php

/**
 * @deprecated Usar Kamples\Services\Core\SyncService en su lugar.
 * Este archivo se mantiene por compatibilidad. Será eliminado en futuras versiones.
 */

namespace Kamples\Services;

use Kamples\Services\Core\SyncService as NewSyncService;

class SyncService
{
    private static ?SyncService $instancia = null;
    private NewSyncService $servicio;

    private function __construct()
    {
        $this->servicio = NewSyncService::obtenerInstancia();
    }

    public static function obtenerInstancia(): SyncService
    {
        if (self::$instancia === null) {
            self::$instancia = new SyncService();
        }
        return self::$instancia;
    }

    public function verificarCambios(int $userId, int $lastSyncTimestamp, bool $forceSync = false): array
    {
        return $this->servicio->verificarCambios($userId, $lastSyncTimestamp, $forceSync);
    }

    public function obtenerAudiosUsuario(int $userId, ?int $postId = null): array
    {
        return $this->servicio->obtenerAudiosUsuario($userId, $postId);
    }

    public function descargarAudio(string $token, string $nonce)
    {
        return $this->servicio->descargarAudio($token, $nonce);
    }

    public function enviarArchivo(string $filePath, string $mimeType, string $fileName): void
    {
        $this->servicio->enviarArchivo($filePath, $mimeType, $fileName);
    }

    public function obtenerInfoUsuario(int $receptorId): array
    {
        return $this->servicio->obtenerInfoUsuario($receptorId);
    }

    public function actualizarTimestampDescargas(int $userId): void
    {
        $this->servicio->actualizarTimestampDescargas($userId);
    }

    public function actualizarTimestampSamplesGuardados(int $userId): void
    {
        $this->servicio->actualizarTimestampSamplesGuardados($userId);
    }
}
