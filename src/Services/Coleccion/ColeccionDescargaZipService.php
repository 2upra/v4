<?php

/**
 * Servicio de creacion y gestion de archivos ZIP para colecciones
 * 
 * Maneja la creacion, validacion y limpieza de archivos ZIP
 *
 * @package Kamples\Services\Coleccion
 * @since 1.0.0
 */

namespace Kamples\Services\Coleccion;

class ColeccionDescargaZipService
{
    private static ?ColeccionDescargaZipService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia unica del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Genera la ruta del archivo ZIP para una coleccion
     *
     * @param int $postId ID del post de la coleccion
     * @param int $numSamples Numero de samples
     * @return array [zipPath, zipName, uploadDir]
     */
    public function generarRutaZip(int $postId, int $numSamples): array
    {
        $zipName = 'coleccion-' . $postId . '-' . $numSamples . '.zip';
        $uploadDir = wp_upload_dir();
        $zipPath = $uploadDir['path'] . '/' . $zipName;

        return [
            'zipPath' => $zipPath,
            'zipName' => $zipName,
            'uploadDir' => $uploadDir
        ];
    }

    /**
     * Verifica si el directorio de uploads es escribible
     *
     * @param array $uploadDir Directorio de uploads
     * @return bool|\WP_Error True si es escribible, WP_Error si no
     */
    public function verificarDirectorioEscribible(array $uploadDir)
    {
        if (!is_dir($uploadDir['path']) || !is_writable($uploadDir['path'])) {
            return new \WP_Error('upload_dir_error', __('El directorio de uploads no tiene permisos de escritura.', 'kamples'));
        }
        return true;
    }

    /**
     * Limpia ZIPs antiguos de la coleccion
     *
     * @param string $uploadPath Ruta del directorio de uploads
     * @param int $postId ID del post
     * @param string $zipPathActual Ruta del ZIP actual a mantener
     */
    public function limpiarZipsAntiguos(string $uploadPath, int $postId, string $zipPathActual): void
    {
        $files = glob($uploadPath . '/coleccion-' . $postId . '-*.zip');
        if ($files) {
            foreach ($files as $file) {
                if ($file !== $zipPathActual && file_exists($file)) {
                    unlink($file);
                    $this->logger->debug('descarga', 'ZIP antiguo eliminado', ['path' => $file]);
                }
            }
        }
    }

    /**
     * Crea o valida un archivo ZIP
     *
     * @param string $zipPath Ruta del ZIP
     * @param array $samples Array de IDs de samples
     * @param int $userId ID del usuario
     * @param int $numNoDescargados Numero de samples no descargados
     * @return bool|\WP_Error True si el ZIP existe/se creo, WP_Error en caso de error
     */
    public function crearOValidarZip(string $zipPath, array $samples, int $userId, int $numNoDescargados)
    {
        if (file_exists($zipPath)) {
            if ($numNoDescargados > 0) {
                $pinky = (int)get_user_meta($userId, 'pinky', true);
                if ($pinky < $numNoDescargados) {
                    return new \WP_Error('no_pinkys', sprintf(
                        __('No tienes suficientes Pinkys. Se requieren %d pinkys', 'kamples'),
                        $numNoDescargados
                    ));
                }
                if (function_exists('restarPinkys')) {
                    restarPinkys($userId, $numNoDescargados);
                }
            }
            return true;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return new \WP_Error('zip_error', __('Error al crear el archivo ZIP.', 'kamples'));
        }

        if (!$this->agregarArchivosAlZip($zip, $samples)) {
            $zip->close();
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            return new \WP_Error('add_file_error', __('Error al agregar archivo al ZIP.', 'kamples'));
        }

        $zip->close();
        return true;
    }

    /**
     * Agrega archivos de audio al ZIP
     *
     * @param \ZipArchive $zip Referencia al archivo ZIP
     * @param array $samples Array de IDs de samples
     * @return bool True si se agrego al menos un archivo
     */
    private function agregarArchivosAlZip(\ZipArchive &$zip, array $samples): bool
    {
        $agregado = false;

        foreach ($samples as $sampleId) {
            $audioIds = get_post_meta($sampleId, 'post_audio', true);

            if (!is_array($audioIds)) {
                if (is_string($audioIds) && !empty($audioIds)) {
                    $audioIds = [$audioIds];
                } else {
                    continue;
                }
            }

            foreach ($audioIds as $audioId) {
                $audioFile = get_attached_file($audioId);

                if (!$audioFile || !file_exists($audioFile)) {
                    $this->logger->warning('descarga', 'Archivo de audio no encontrado', [
                        'sampleId' => $sampleId,
                        'audioId' => $audioId
                    ]);
                    continue;
                }

                if ($zip->addFile($audioFile, basename($audioFile))) {
                    $agregado = true;
                } else {
                    return false;
                }
            }
        }

        return $agregado;
    }

    /**
     * Elimina un archivo ZIP
     *
     * @param string $zipPath Ruta del ZIP a eliminar
     */
    public function eliminarZip(string $zipPath): void
    {
        if (!empty($zipPath) && file_exists($zipPath)) {
            unlink($zipPath);
        }
    }
}
