<?php

/**
 * Servicio de procesamiento de archivos para AutoPost
 * 
 * Maneja las operaciones de archivo para posts automáticos:
 * - Eliminación de metadatos con FFmpeg
 * - Creación de versiones lite
 * - Validación y copia de archivos
 *
 * @package Kamples\Services\Contenido
 * @since 1.0.0
 */

namespace Kamples\Services\Contenido;

use Kamples\Services\Audio\HashService;

class AutoPostArchivoService
{
    private static ?AutoPostArchivoService $instancia = null;
    private \Logger $logger;
    private HashService $hashService;

    private const DIR_VERIFICAR = '/home/asley01/MEGA/Waw/Verificar/';

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->hashService = HashService::obtenerInstancia();
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
     * Valida un archivo de audio antes de procesarlo
     * 
     * @return array|false Array con datos del archivo o false si no es válido
     */
    public function validarArchivo(string $rutaOriginal, ?int $fileId): array|false
    {
        if (!file_exists($rutaOriginal)) {
            $this->logger->error('automatico', "Archivo original no encontrado: {$rutaOriginal}");
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return false;
        }

        $fileSizeMB = filesize($rutaOriginal) / 1048576;
        if ($fileSizeMB < 0.01) {
            $motivo = "Archivo demasiado pequeño (< 0.01 MB)";
            $this->logger->warning('automatico', "$motivo: $rutaOriginal");
            $this->manejarArchivoFallido($rutaOriginal, $motivo);
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return false;
        }

        $pathParts = pathinfo($rutaOriginal);
        $directory = realpath($pathParts['dirname']);
        if (!$directory) {
            $motivo = "Directorio inválido: {$pathParts['dirname']}";
            $this->logger->error('automatico', $motivo);
            $this->manejarArchivoFallido($rutaOriginal, $motivo);
            if ($fileId) $this->hashService->eliminarHash($fileId);
            return false;
        }

        return [
            'directory' => $directory,
            'extension' => strtolower($pathParts['extension']),
            'basename' => $pathParts['filename']
        ];
    }

    /**
     * Elimina los metadatos del archivo de audio
     */
    public function eliminarMetadatos(string $rutaOriginal, array $pathInfo, ?int $fileId): bool
    {
        $tempPath = "{$pathInfo['directory']}/{$pathInfo['basename']}_temp.{$pathInfo['extension']}";

        $cmdStrip = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginal) . " -map_metadata -1 -map 0:a -c:a copy " . escapeshellarg($tempPath) . " -y";
        exec($cmdStrip, $outputStrip, $returnStrip);

        if ($returnStrip !== 0) {
            $motivo = "Error al eliminar metadatos: " . implode(" | ", $outputStrip);
            $this->logger->error('automatico', $motivo);
            if ($fileId) $this->hashService->eliminarHash($fileId);
            $this->manejarArchivoFallido($rutaOriginal, $motivo);
            return false;
        }

        if (!rename($tempPath, $rutaOriginal)) {
            $motivo = "Error al reemplazar archivo original con versión sin metadata";
            $this->logger->error('automatico', $motivo);
            if (!copy($tempPath, $rutaOriginal)) {
                if ($fileId) $this->hashService->eliminarHash($fileId);
                $this->manejarArchivoFallido($tempPath, "Fallo critico al mover. " . $motivo);
                return false;
            }
            unlink($tempPath);
        }

        return true;
    }

    /**
     * Crea versión lite del audio y la copia a uploads
     */
    public function crearVersionLite(string $rutaOriginal, array $pathInfo, ?int $fileId): ?string
    {
        $rutaWpLiteDos = "{$pathInfo['directory']}/{$pathInfo['basename']}_lite.mp3";
        $cmdLite = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginal) . " -b:a 128k " . escapeshellarg($rutaWpLiteDos) . " -y";
        exec($cmdLite, $outputLite, $returnLite);

        if ($returnLite !== 0 || !file_exists($rutaWpLiteDos)) {
            $motivo = "Error al crear versión lite";
            if ($fileId) $this->hashService->eliminarHash($fileId);
            $this->logger->error('automatico', $motivo);
            $this->manejarArchivoFallido($rutaOriginal, $motivo);
            return null;
        }

        $uploadsDir = wp_upload_dir();
        $targetDirAudio = trailingslashit($uploadsDir['basedir']) . "audio/";

        if (!file_exists($targetDirAudio) && !wp_mkdir_p($targetDirAudio)) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            $this->manejarArchivoFallido($rutaOriginal, "No se pudo crear directorio audio/");
            return null;
        }

        $rutaWpLiteOne = $targetDirAudio . "{$pathInfo['basename']}_lite.mp3";
        if (!copy($rutaWpLiteDos, $rutaWpLiteOne)) {
            if ($fileId) $this->hashService->eliminarHash($fileId);
            $this->manejarArchivoFallido($rutaOriginal, "Error al copiar lite a uploads/audio/");
            unlink($rutaWpLiteDos);
            return null;
        }

        unlink($rutaWpLiteDos);
        chmod($rutaWpLiteOne, 0644);

        return $rutaWpLiteOne;
    }

    /**
     * Mueve un archivo que falló a un directorio de verificación
     */
    public function manejarArchivoFallido(string $rutaArchivo, string $motivo): void
    {
        if (!file_exists(self::DIR_VERIFICAR)) {
            mkdir(self::DIR_VERIFICAR, 0777, true);
        }

        $nombre = basename($rutaArchivo);
        $destino = self::DIR_VERIFICAR . $nombre;

        if (rename($rutaArchivo, $destino)) {
            file_put_contents($destino . ".txt", "Fallo: $nombre\nMotivo: $motivo");
        } else {
            $this->logger->error('automatico', "Error al mover archivo fallido a verificación: $rutaArchivo");
        }
    }
}
