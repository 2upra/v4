<?php

/**
 * Servicio de envío de archivos para descargas.
 * 
 * Maneja el streaming y envío de archivos de audio.
 *
 * @package Kamples\Services\Core
 * @since 1.0.0
 */

namespace Kamples\Services\Core;

class DescargaEnvioService
{
    private static ?DescargaEnvioService $instancia = null;
    private DescargaTokenService $tokenService;
    private \Logger $logger;

    private function __construct()
    {
        $this->tokenService = DescargaTokenService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
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
     * Procesa la descarga de un archivo de audio
     *
     * @param string $token Token de descarga
     * @return bool True si se procesó correctamente
     */
    public function manejarDescarga(string $token): bool
    {
        $tokenData = $this->tokenService->validarToken($token);

        if (!$tokenData) {
            wp_die('El enlace de descarga no es válido o ha expirado.');
            return false;
        }

        $audioId = $tokenData['audio_id'];
        $audioPath = get_attached_file($audioId);

        if (!$audioPath || !file_exists($audioPath) || !is_readable($audioPath)) {
            wp_die('El archivo no existe o no es accesible.');
            return false;
        }

        $this->enviarArchivo($audioPath, $token, $tokenData);
        return true;
    }

    /**
     * Envía el archivo de audio para descarga
     */
    private function enviarArchivo(string $audioPath, string $token, array $tokenData): void
    {
        /* Limpiar buffers */
        while (ob_get_level()) {
            ob_end_clean();
        }

        ini_set('zlib.output_compression', 'Off');
        ini_set('output_buffering', 'Off');
        set_time_limit(0);

        /* Obtener MIME type */
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $audioPath);
        finfo_close($finfo);

        $filename = basename($audioPath);

        /* Headers */
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($audioPath));
        header('Accept-Ranges: bytes');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        /* Manejar range requests */
        $range = 0;
        if (isset($_SERVER['HTTP_RANGE'])) {
            list($a, $rangeStr) = explode("=", $_SERVER['HTTP_RANGE'], 2);
            list($rangeStr) = explode(",", $rangeStr, 2);
            list($rangeStr, $rangeEnd) = explode("-", $rangeStr);
            $range = intval($rangeStr);
            $size = filesize($audioPath);
            $rangeEnd = $rangeEnd ? intval($rangeEnd) : $size - 1;

            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $range-$rangeEnd/$size");
            header('Content-Length: ' . ($rangeEnd - $range + 1));
        }

        /* Enviar archivo */
        $fp = fopen($audioPath, 'rb');
        fseek($fp, $range);

        while (!feof($fp)) {
            $data = fread($fp, 8192);
            print($data);
            flush();
            if (connection_status() != 0) {
                fclose($fp);
                exit;
            }
        }

        fclose($fp);

        /* Actualizar usos del token */
        $this->tokenService->actualizarTokenUsos($token, $tokenData);

        exit;
    }
}
