<?php

/**
 * Servicio de posts automáticos (Fachada)
 * 
 * Orquesta la creación de posts automáticos delegando a servicios especializados:
 * - AutoPostArchivoService: Procesamiento de archivos
 * - AutoPostCreacionService: Creación de posts en WordPress
 * - AutoPostScanService: Escaneo y cron jobs
 * - AutoPostMultipleService: Procesamiento de posts múltiples
 *
 * @package Kamples\Services\Contenido
 * @since 1.0.0
 */

namespace Kamples\Services\Contenido;

use Kamples\Services\HashService;

class AutoPostService
{
    private static ?AutoPostService $instancia = null;
    private \Logger $logger;
    private HashService $hashService;
    private AutoPostArchivoService $archivoService;
    private AutoPostCreacionService $creacionService;
    private AutoPostScanService $scanService;
    private AutoPostMultipleService $multipleService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->hashService = HashService::obtenerInstancia();
        $this->archivoService = AutoPostArchivoService::obtenerInstancia();
        $this->creacionService = AutoPostCreacionService::obtenerInstancia();
        $this->scanService = AutoPostScanService::obtenerInstancia();
        $this->multipleService = AutoPostMultipleService::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Procesa un archivo de audio para crear un post automático
     * 
     * @param string $rutaOriginal Ruta absoluta del archivo original
     */
    public function procesarAudio(string $rutaOriginal): void
    {
        $this->logger->info('automatico', "Iniciando procesamiento de audio: {$rutaOriginal}");

        $fileId = $this->hashService->obtenerFileIdPorUrl($rutaOriginal);

        /* Validar archivo */
        $pathInfo = $this->archivoService->validarArchivo($rutaOriginal, $fileId);
        if (!$pathInfo) return;

        /* Eliminar metadatos */
        if (!$this->archivoService->eliminarMetadatos($rutaOriginal, $pathInfo, $fileId)) {
            return;
        }

        /* Crear versión lite */
        $rutaWpLiteOne = $this->archivoService->crearVersionLite($rutaOriginal, $pathInfo, $fileId);
        if (!$rutaWpLiteOne) return;

        $this->logger->info('automatico', "Procesamiento de audio completado. Creando post...");

        /* Crear el post */
        $this->creacionService->crearPost($rutaOriginal, $rutaWpLiteOne, $fileId);
    }

    /**
     * Crea el post automático con los datos del audio
     * Método delegado para compatibilidad
     */
    public function crearAutPost(?string $rutaOriginal, ?string $rutaWpLite, $fileId = null, $autorId = 44, $postOriginal = null)
    {
        return $this->creacionService->crearPost($rutaOriginal, $rutaWpLite, $fileId, $autorId, $postOriginal);
    }

    /**
     * Adjunta archivo a post
     * Método delegado para compatibilidad
     */
    public function adjuntarArchivoAut($archivo, $postId, $fileId = null)
    {
        return $this->creacionService->adjuntarArchivo($archivo, $postId, $fileId);
    }

    /**
     * Ejecuta el escaneo de audios (Cron Job)
     */
    public function procesarAudiosScan(): void
    {
        $audioInfo = $this->scanService->ejecutarScan();

        if ($audioInfo && isset($audioInfo['ruta'])) {
            $this->procesarAudio($audioInfo['ruta']);
        }
    }

    /**
     * Procesa un post con múltiples audios y genera nuevos posts para cada uno
     */
    public function procesarMultiples(int $postIdOriginal): void
    {
        $this->multipleService->procesarMultiples($postIdOriginal);
    }
}
