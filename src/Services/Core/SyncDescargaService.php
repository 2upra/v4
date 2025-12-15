<?php

/**
 * Servicio de descarga de archivos para sincronización.
 * 
 * Maneja la descarga y streaming de archivos de audio
 * para la aplicación Electron.
 *
 * @package Kamples\Services\Core
 * @since 1.0.0
 */

namespace Kamples\Services\Core;

class SyncDescargaService
{
    private static ?SyncDescargaService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): SyncDescargaService
    {
        if (self::$instancia === null) {
            self::$instancia = new SyncDescargaService();
        }
        return self::$instancia;
    }

    /**
     * Descarga un audio mediante token de sincronización.
     *
     * @param string $token Token de descarga.
     * @param string $nonce Nonce de seguridad.
     * @return array|\WP_Error Resultado de la operación.
     */
    public function descargarAudio(string $token, string $nonce)
    {
        if (!wp_verify_nonce($nonce, 'download_' . $token)) {
            $this->logger->warning('sync', 'Nonce inválido', ['token' => $token]);
            return new \WP_Error('invalid_nonce', 'Nonce inválido.', ['status' => 403]);
        }

        $attachmentId = get_transient('sync_token_' . $token);

        if (!$attachmentId) {
            $this->logger->warning('sync', 'Token expirado o inválido', ['token' => $token]);
            return new \WP_Error('invalid_token', 'Token inválido o expirado.', ['status' => 403]);
        }

        delete_transient('sync_token_' . $token);
        $filePath = get_attached_file($attachmentId);

        if (empty($filePath) || !file_exists($filePath)) {
            $this->logger->error('sync', 'Archivo no encontrado', ['token' => $token, 'path' => $filePath]);
            return new \WP_Error('file_not_found', 'Archivo no encontrado.', ['status' => 404]);
        }

        /* Verificar que es un archivo de audio */
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (strpos($mimeType, 'audio/') !== 0) {
            $this->logger->warning('sync', 'Tipo de archivo inválido', ['mime' => $mimeType]);
            return new \WP_Error('invalid_file_type', 'Tipo de archivo inválido.', ['status' => 400]);
        }

        return [
            'success' => true,
            'filePath' => $filePath,
            'mimeType' => $mimeType,
            'fileName' => basename($filePath)
        ];
    }

    /**
     * Envía el archivo de audio al cliente.
     * Este método maneja directamente los headers y el streaming.
     *
     * @param string $filePath Ruta del archivo.
     * @param string $mimeType Tipo MIME.
     * @param string $fileName Nombre del archivo.
     */
    public function enviarArchivo(string $filePath, string $mimeType, string $fileName): void
    {
        /* Limpiar buffers */
        while (ob_get_level()) {
            ob_end_clean();
        }

        /* Configuración del servidor */
        ini_set('zlib.output_compression', 'Off');
        ini_set('output_buffering', 'Off');
        set_time_limit(0);

        /* Headers de descarga */
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');

        $range = 0;
        $size = filesize($filePath);

        /* Manejo de rangos (para descargas parciales) */
        if (isset($_SERVER['HTTP_RANGE'])) {
            list($a, $rangeStr) = explode("=", $_SERVER['HTTP_RANGE'], 2);
            list($rangeStr) = explode(",", $rangeStr, 2);
            list($rangeStart, $rangeEnd) = explode("-", $rangeStr);
            $range = intval($rangeStart);
            $rangeEnd = $rangeEnd ? intval($rangeEnd) : $size - 1;

            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $range-$rangeEnd/$size");
            header('Content-Length: ' . ($rangeEnd - $range + 1));
        }

        /* Enviar archivo */
        $handle = fopen($filePath, 'rb');
        if ($handle !== false) {
            fseek($handle, $range);
            fpassthru($handle);
            fclose($handle);
        }

        flush();
        exit;
    }
}
