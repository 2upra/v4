<?php

/**
 * Servicio de tokens y streaming para descarga de colecciones
 * 
 * Gestiona la generacion de tokens seguros y el streaming de archivos ZIP
 *
 * @package Kamples\Services\Coleccion
 * @since 1.0.0
 */

namespace Kamples\Services\Coleccion;

class ColeccionDescargaTokenService
{
    private static ?ColeccionDescargaTokenService $instancia = null;
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
     * Genera un enlace de descarga seguro con token
     *
     * @param int $userId ID del usuario
     * @param string $zipPath Ruta fisica del ZIP
     * @param int $postId ID del post
     * @return string URL de descarga
     */
    public function generarEnlaceDescarga(int $userId, string $zipPath, int $postId): string
    {
        $token = bin2hex(random_bytes(16));

        $tokenData = [
            'user_id' => $userId,
            'zip_path' => $zipPath,
            'post_id' => $postId,
            'time' => time(),
            'usos' => 0,
            'tipo' => 'coleccion'
        ];

        set_transient('descarga_token_' . $token, $tokenData, HOUR_IN_SECONDS);

        $enlaceDescarga = add_query_arg([
            'descarga_token' => $token,
            'tipo' => 'coleccion'
        ], home_url());

        $this->logger->info('descarga', 'Enlace de descarga de coleccion generado', [
            'postId' => $postId,
            'userId' => $userId
        ]);

        return $enlaceDescarga;
    }

    /**
     * Procesa la descarga de una coleccion desde un token
     * Se llama desde template_redirect hook
     */
    public function procesarDescargaDesdeToken(): void
    {
        if (!isset($_GET['descarga_token']) || !isset($_GET['tipo']) || $_GET['tipo'] !== 'coleccion') {
            return;
        }

        $token = sanitize_text_field($_GET['descarga_token']);

        if (!ctype_xdigit($token)) {
            $this->logger->error('descarga', 'Token invalido', ['token' => $token]);
            wp_die('El token de descarga no es valido.');
        }

        $tokenData = get_transient('descarga_token_' . $token);

        if (!$tokenData) {
            $this->logger->warning('descarga', 'Token expirado o no valido', ['token' => $token]);
            wp_die('El enlace de descarga no es valido o ha expirado.');
        }

        if ($tokenData['usos'] >= 2) {
            delete_transient('descarga_token_' . $token);
            $this->logger->warning('descarga', 'Token excedio usos permitidos', ['token' => $token]);
            wp_die('El enlace de descarga ha excedido el numero de usos permitidos.');
        }

        $zipPath = $tokenData['zip_path'];

        if (!$zipPath || !file_exists($zipPath) || !is_readable($zipPath)) {
            $this->logger->error('descarga', 'Archivo ZIP no accesible', ['path' => $zipPath]);
            wp_die('El archivo no existe o no es accesible.');
        }

        $this->streamZip($zipPath, $token, $tokenData);
    }

    /**
     * Stream del archivo ZIP al usuario
     *
     * @param string $zipPath Ruta del archivo ZIP
     * @param string $token Token de descarga
     * @param array $tokenData Datos del token
     */
    public function streamZip(string $zipPath, string $token, array $tokenData): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        ini_set('zlib.output_compression', 'Off');
        ini_set('output_buffering', 'Off');
        set_time_limit(0);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            wp_die('Error al obtener informacion del archivo.');
        }

        $mimeType = finfo_file($finfo, $zipPath);
        finfo_close($finfo);

        if ($mimeType === false) {
            wp_die('Error al obtener el tipo de archivo.');
        }

        $filename = basename($zipPath);
        $filesize = filesize($zipPath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $filesize);
        header('Accept-Ranges: bytes');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $range = 0;
        if (isset($_SERVER['HTTP_RANGE'])) {
            list(, $rangeStr) = explode("=", $_SERVER['HTTP_RANGE'], 2);
            list($rangeStr) = explode(",", $rangeStr, 2);
            list($rangeStart, $rangeEnd) = explode("-", $rangeStr);
            $range = intval($rangeStart);
            $rangeEnd = $rangeEnd ? intval($rangeEnd) : $filesize - 1;

            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $range-$rangeEnd/$filesize");
            header('Content-Length: ' . ($rangeEnd - $range + 1));
        }

        $fp = fopen($zipPath, 'rb');
        if ($fp === false) {
            wp_die('Error al abrir el archivo.');
        }

        fseek($fp, $range);

        while (!feof($fp)) {
            $data = fread($fp, 8192);
            if ($data === false) {
                fclose($fp);
                wp_die('Error al leer el archivo.');
            }
            print($data);
            flush();
            if (connection_status() != 0) {
                fclose($fp);
                exit;
            }
        }

        fclose($fp);

        $tokenData['usos']++;
        set_transient('descarga_token_' . $token, $tokenData, HOUR_IN_SECONDS);

        if ($tokenData['usos'] >= 3) {
            delete_transient('descarga_token_' . $token);
        }

        $this->logger->info('descarga', 'Descarga de coleccion completada', ['token' => $token]);
        exit;
    }
}
