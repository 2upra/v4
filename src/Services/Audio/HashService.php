<?php

namespace Kamples\Services\Audio;

/**
 * Servicio fachada de gestion de hashes de archivos.
 * 
 * Orquesta los servicios especializados:
 * - HashCalculoService: Calculo y comparacion de hashes
 * - HashCrudService: CRUD en base de datos
 * - HashVerificacionService: Consultas y verificacion
 *
 * @since 1.0.0
 */
class HashService
{
    private static ?HashService $instancia = null;
    private HashCalculoService $calculoService;
    private HashCrudService $crudService;
    private HashVerificacionService $verificacionService;

    private function __construct()
    {
        $this->calculoService = HashCalculoService::obtenerInstancia();
        $this->crudService = HashCrudService::obtenerInstancia();
        $this->verificacionService = HashVerificacionService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa los hooks del servicio
     */
    public static function inicializar(): void
    {
        HashVerificacionService::inicializar();
    }

    /* 
     * Metodos de calculo (delegados a HashCalculoService)
     */

    public function sonHashesSimilares(string $hash1, string $hash2, float $umbral = 0.7): bool
    {
        return $this->calculoService->sonHashesSimilares($hash1, $hash2, $umbral);
    }

    public function sonHashesSimilaresEuclidean(string $hash1, string $hash2, float $umbral = 0.85): bool
    {
        return $this->calculoService->sonHashesSimilaresEuclidean($hash1, $hash2, $umbral);
    }

    public function recalcularHash(string $audioFilePath)
    {
        return $this->calculoService->recalcularHash($audioFilePath);
    }

    public function nombreUnicoFile(string $dir, string $name, string $ext): string
    {
        return $this->calculoService->nombreUnicoFile($dir, $name, $ext);
    }

    /* 
     * Metodos CRUD (delegados a HashCrudService)
     */

    public function guardarHash(string $hash, string $url, int $userId, string $status = 'pending')
    {
        return $this->crudService->guardarHash($hash, $url, $userId, $status);
    }

    public function actualizarEstadoArchivo(int $id, string $estado): bool
    {
        return $this->crudService->actualizarEstadoArchivo($id, $estado);
    }

    public function actualizarUrlArchivo(int $fileId, string $newUrl): bool
    {
        return $this->crudService->actualizarUrlArchivo($fileId, $newUrl);
    }

    public function confirmarHashId(int $fileId): bool
    {
        return $this->crudService->confirmarHashId($fileId);
    }

    public function eliminarHash(int $id): bool
    {
        return $this->crudService->eliminarHash($id);
    }

    public function eliminarPorHash(string $fileHash): bool
    {
        return $this->crudService->eliminarPorHash($fileHash);
    }

    /* 
     * Metodos de verificacion (delegados a HashVerificacionService)
     */

    public function obtenerFileIdPorUrl(string $url)
    {
        return $this->verificacionService->obtenerFileIdPorUrl($url);
    }

    public function obtenerHash(string $fileHash): ?array
    {
        return $this->verificacionService->obtenerHash($fileHash);
    }

    public function obtenerHashesFiltrados(array $extensiones): array
    {
        return $this->verificacionService->obtenerHashesFiltrados($extensiones);
    }

    public function verificarCargaArchivoPorHash(string $fileHash): bool
    {
        return $this->verificacionService->verificarCargaArchivoPorHash($fileHash);
    }

    public function limpiarArchivosPendientes(): void
    {
        $this->verificacionService->limpiarArchivosPendientes();
    }
}
