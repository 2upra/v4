<?php

/**
 * Servicio de escaneo para posts automáticos
 * 
 * Maneja el escaneo de directorios y cron jobs:
 * - Búsqueda de audios válidos
 * - Validación de hashes
 * - Control de bloqueo de procesos
 *
 * @package Kamples\Services\Contenido
 * @since 1.0.0
 */

namespace Kamples\Services\Contenido;

use Kamples\Services\Audio\HashService;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use DirectoryIterator;
use Exception;

class AutoPostScanService
{
    private static ?AutoPostScanService $instancia = null;
    private \Logger $logger;
    private HashService $hashService;
    private AutoPostArchivoService $archivoService;

    private const DIR_KITS = '/home/asley01/MEGA/Waw/Kits/';
    private const LOCK_FILE = '/tmp/procesar_audios.lock';

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->hashService = HashService::obtenerInstancia();
        $this->archivoService = AutoPostArchivoService::obtenerInstancia();
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
     * Ejecuta el escaneo de audios (Cron Job)
     * 
     * @return array|null Información del audio encontrado o null
     */
    public function ejecutarScan(): ?array
    {
        if (defined('LOCAL') && LOCAL === true) {
            return null;
        }

        $fp = fopen(self::LOCK_FILE, 'c');
        if (!$fp) return null;

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return null;
        }

        $audioInfo = null;

        try {
            $audioInfo = $this->buscarUnAudioValido(self::DIR_KITS);
            if ($audioInfo) {
                $hashId = $this->hashService->guardarHash($audioInfo['hash'], $audioInfo['ruta'], 44, 'confirmed');
                if (!$hashId) {
                    $audioInfo = null;
                } else {
                    $audioInfo['hashId'] = $hashId;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('automatico', "Error scan: " . $e->getMessage());
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
            @unlink(self::LOCK_FILE);
        }

        return $audioInfo;
    }

    /**
     * Busca un archivo de audio válido en el directorio
     */
    public function buscarUnAudioValido(string $directorio, int $intentos = 0): ?array
    {
        $maxIntentos = 100;
        $carpetaProtegida = self::DIR_KITS;

        if ($intentos >= $maxIntentos) {
            $this->logger->error('automatico', "Max intentos alcanzados en buscarUnAudioValido");
            return null;
        }

        if (!is_dir($directorio) || !is_readable($directorio)) {
            @shell_exec('sudo /var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh 2>&1');
            return null;
        }

        try {
            $subcarpetas = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $subcarpetas[] = $item->getPathname();
                }
            }
            if (empty($subcarpetas)) {
                $subcarpetas[] = $directorio;
            }

            $carpetaSeleccionada = $subcarpetas[array_rand($subcarpetas)];

            $archivos = [];
            $dirIterator = new DirectoryIterator($carpetaSeleccionada);
            foreach ($dirIterator as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, ['wav', 'mp3'])) {
                        $archivos[] = $file->getPathname();
                    } else {
                        @unlink($file->getPathname());
                    }
                }
            }

            foreach ($subcarpetas as $sub) {
                if ($sub !== $carpetaProtegida) {
                    $fi = new FilesystemIterator($sub);
                    if (!$fi->valid()) {
                        @rmdir($sub);
                    }
                }
            }

            if (empty($archivos)) {
                return $this->buscarUnAudioValido($directorio, $intentos + 1);
            }

            $archivoSeleccionado = $archivos[array_rand($archivos)];
            $hash = $this->hashService->recalcularHash($archivoSeleccionado);

            if (!$hash) {
                @unlink($archivoSeleccionado);
                return $this->buscarUnAudioValido($directorio, $intentos + 1);
            }

            if ($this->debeProcesarse($archivoSeleccionado, $hash)) {
                return ['ruta' => $archivoSeleccionado, 'hash' => $hash];
            } else {
                return $this->buscarUnAudioValido($directorio, $intentos + 1);
            }
        } catch (Exception $e) {
            $this->logger->error('automatico', "Error en buscarUnAudioValido: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Determina si un archivo debe ser procesado
     */
    public function debeProcesarse(string $rutaArchivo, string $fileHash): bool
    {
        $hashesExistentes = $this->hashService->obtenerHashesFiltrados(['wav', 'mp3']);
        $hashVerificado = $this->hashService->verificarCargaArchivoPorHash($fileHash);

        foreach ($hashesExistentes as $h) {
            if ($this->hashService->sonHashesSimilaresEuclidean($fileHash, $h['file_hash'])) {
                if ($hashVerificado && file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                    $this->hashService->eliminarPorHash($fileHash);
                } else {
                    $this->archivoService->manejarArchivoFallido($rutaArchivo, "Hash similar encontrado y fallo verificación.");
                }
                return false;
            }
        }
        return true;
    }
}
