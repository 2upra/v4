<?php

/**
 * Servicio de envio de archivos de audio
 * 
 * Gestiona el streaming de audio con soporte para range requests y cache
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class StreamEnvioService
{
    private static ?StreamEnvioService $instancia = null;
    private const ENABLE_BROWSER_CACHE = true;

    private function __construct() {}

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
     * Realiza el streaming del archivo de audio
     *
     * @param string $audioId ID del audio
     * @return \WP_Error|void Error si falla, o realiza streaming y termina
     */
    public function streamAudio(string $audioId)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $uploadDir = wp_upload_dir();
        $cacheDir = $uploadDir['basedir'] . '/audio_cache';

        if (!file_exists($cacheDir)) {
            wp_mkdir_p($cacheDir);
        }

        $cacheFile = $cacheDir . '/audio_' . $audioId . '.cache';

        /* Cargar desde cache o archivo original */
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 24 * 60 * 60)) {
            $file = $cacheFile;
        } else {
            $originalFile = get_attached_file($audioId);
            if (!file_exists($originalFile)) {
                return new \WP_Error('no_audio', 'Archivo de audio no encontrado.', ['status' => 404]);
            }

            if (!@copy($originalFile, $cacheFile)) {
                return new \WP_Error('copy_failed', 'Error al copiar el archivo de audio al cache.', ['status' => 500]);
            }

            $file = $cacheFile;
        }

        $this->enviarArchivoAudio($file, $audioId);
    }

    /**
     * Envia el archivo de audio con soporte para range requests
     */
    private function enviarArchivoAudio(string $file, $audioId): void
    {
        $fp = @fopen($file, 'rb');
        if (!$fp) {
            wp_die('No se pudo abrir el archivo de audio.');
        }

        $size = filesize($file);
        $length = $size;
        $start = 0;
        $end = $size - 1;

        $etag = '"' . md5($file . filemtime($file)) . '"';

        /* Headers basicos */
        header('Content-Type: ' . get_post_mime_type($audioId));
        header('Accept-Ranges: bytes');

        /* Headers de cache */
        if (self::ENABLE_BROWSER_CACHE) {
            $cacheTime = 60 * 60 * 2190;
            header('Cache-Control: public, max-age=' . $cacheTime);
            header('Pragma: public');
            header('ETag: ' . $etag);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');

            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        } else {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
        }

        /* Manejar Range Requests */
        if (isset($_SERVER['HTTP_RANGE'])) {
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            if ($range == '-') {
                $start = $size - substr($range, 1);
            } else {
                $rangeParts = explode('-', $range);
                $start = (int)$rangeParts[0];
                $end = isset($rangeParts[1]) && is_numeric($rangeParts[1]) ? (int)$rangeParts[1] : $size;
            }

            $end = min($end, $size - 1);

            if ($start > $end || $start > $size - 1) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }

        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);

        /* Streaming con rate limiting */
        $buffer = 1024 * 8;
        $sleep = 1000;
        $sent = 0;

        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            echo fread($fp, $buffer);
            $sent += $buffer;
            flush();

            if ($sent >= 64 * 1024) {
                usleep($sleep);
                $sent = 0;
            }
        }

        fclose($fp);
        exit();
    }

    /**
     * Limpia el cache de audio
     */
    public function limpiarCache(): void
    {
        $uploadDir = wp_upload_dir();
        $cacheDir = $uploadDir['basedir'] . '/audio_cache';

        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            $now = time();

            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) > 7 * 24 * 60 * 60)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Programa la limpieza automatica del cache
     */
    public function programarLimpiezaCache(): void
    {
        if (!wp_next_scheduled('audio_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'audio_cache_cleanup');
        }
    }

    /**
     * Obtiene el directorio de cache
     *
     * @return string Ruta al directorio de cache
     */
    public function obtenerDirCache(): string
    {
        $uploadDir = wp_upload_dir();
        return $uploadDir['basedir'] . '/audio_cache';
    }
}
