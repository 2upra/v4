<?php

namespace Kamples\Services;

use Kamples\Services\Audio\HashService as NuevoHashService;

/**
 * @deprecated Usar Kamples\Services\Audio\HashService
 * 
 * Wrapper de compatibilidad. Redirige todas las llamadas al nuevo servicio.
 */
class HashService
{
    private static ?HashService $instancia = null;
    private NuevoHashService $nuevoServicio;

    private function __construct()
    {
        $this->nuevoServicio = NuevoHashService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public static function inicializar(): void
    {
        NuevoHashService::inicializar();
    }

    public function sonHashesSimilares(string $hash1, string $hash2, float $umbral = 0.7): bool
    {
        return $this->nuevoServicio->sonHashesSimilares($hash1, $hash2, $umbral);
    }

    public function sonHashesSimilaresEuclidean(string $hash1, string $hash2, float $umbral = 0.85): bool
    {
        return $this->nuevoServicio->sonHashesSimilaresEuclidean($hash1, $hash2, $umbral);
    }

    public function recalcularHash(string $audioFilePath)
    {
        return $this->nuevoServicio->recalcularHash($audioFilePath);
    }

    public function nombreUnicoFile(string $dir, string $name, string $ext): string
    {
        return $this->nuevoServicio->nombreUnicoFile($dir, $name, $ext);
    }

    public function guardarHash(string $hash, string $url, int $userId, string $status = 'pending')
    {
        return $this->nuevoServicio->guardarHash($hash, $url, $userId, $status);
    }

    public function actualizarEstadoArchivo(int $id, string $estado): bool
    {
        return $this->nuevoServicio->actualizarEstadoArchivo($id, $estado);
    }

    public function actualizarUrlArchivo(int $fileId, string $newUrl): bool
    {
        return $this->nuevoServicio->actualizarUrlArchivo($fileId, $newUrl);
    }

    public function confirmarHashId(int $fileId): bool
    {
        return $this->nuevoServicio->confirmarHashId($fileId);
    }

    public function eliminarHash(int $id): bool
    {
        return $this->nuevoServicio->eliminarHash($id);
    }

    public function eliminarPorHash(string $fileHash): bool
    {
        return $this->nuevoServicio->eliminarPorHash($fileHash);
    }

    public function obtenerFileIdPorUrl(string $url)
    {
        return $this->nuevoServicio->obtenerFileIdPorUrl($url);
    }

    public function obtenerHash(string $fileHash): ?array
    {
        return $this->nuevoServicio->obtenerHash($fileHash);
    }

    public function obtenerHashesFiltrados(array $extensiones): array
    {
        return $this->nuevoServicio->obtenerHashesFiltrados($extensiones);
    }

    public function verificarCargaArchivoPorHash(string $fileHash): bool
    {
        return $this->nuevoServicio->verificarCargaArchivoPorHash($fileHash);
    }

    public function limpiarArchivosPendientes(): void
    {
        $this->nuevoServicio->limpiarArchivosPendientes();
    }
}
