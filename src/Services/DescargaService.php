<?php

/**
 * Servicio de descargas de audio
 * 
 * Gestiona la descarga de archivos de audio individuales y colecciones
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class DescargaService
{
    private static ?DescargaService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
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
     * Procesa una solicitud de descarga
     *
     * @param int $userId ID del usuario
     * @param int $postId ID del post
     * @param bool $esColeccion Si es una colección
     * @param bool $sync Si es sincronización (sin devolver URL)
     * @return array Resultado de la operación
     */
    public function procesarDescarga(int $userId, int $postId, bool $esColeccion = false, bool $sync = false): array
    {
        if (!$userId) {
            return ['success' => false, 'message' => 'No autorizado.'];
        }

        $post = get_post($postId);
        if (!$post || $post->post_status !== 'publish') {
            return ['success' => false, 'message' => 'Post no válido.'];
        }

        if ($esColeccion) {
            return $this->procesarDescargaColeccion($userId, $postId, $sync);
        }

        return $this->procesarDescargaIndividual($userId, $postId, $sync);
    }

    /**
     * Procesa descarga de audio individual
     */
    private function procesarDescargaIndividual(int $userId, int $postId, bool $sync): array
    {
        $audioId = get_post_meta($postId, 'post_audio', true);
        if (!$audioId) {
            return ['success' => false, 'message' => 'Audio no encontrado.'];
        }

        $descargasAnteriores = get_user_meta($userId, 'descargas', true);
        if (!is_array($descargasAnteriores)) {
            $descargasAnteriores = [];
        }

        $yaDescargado = isset($descargasAnteriores[$postId]);

        /* Cobrar pinkys si es primera descarga */
        if (!$yaDescargado) {
            $pinky = (int) get_user_meta($userId, 'pinky', true);
            if ($pinky < 1) {
                return ['success' => false, 'message' => 'No tienes suficientes Pinkys para esta descarga.'];
            }
            $this->restarPinkys($userId, 1);
        }

        /* Actualizar registro de descargas */
        if (!$yaDescargado) {
            $descargasAnteriores[$postId] = 1;
        } else {
            $descargasAnteriores[$postId]++;
        }

        update_user_meta($userId, 'descargas', $descargasAnteriores);

        /* Actualizar contador del post */
        $totalDescargas = (int) get_post_meta($postId, 'totalDescargas', true);
        update_post_meta($postId, 'totalDescargas', $totalDescargas + 1);

        $this->actualizarTimestampDescargas($userId);

        if ($sync) {
            return ['success' => true, 'message' => 'Sincronizado.'];
        }

        $downloadUrl = $this->generarEnlaceDescarga($userId, $audioId);
        return ['success' => true, 'download_url' => $downloadUrl];
    }

    /**
     * Procesa descarga de colección
     */
    private function procesarDescargaColeccion(int $userId, int $postId, bool $sync): array
    {
        /* La lógica de colecciones se delega a ColeccionService */
        if (function_exists('procesarColeccion')) {
            if (!$sync) {
                $zipUrl = procesarColeccion($postId, $userId);
                if (is_wp_error($zipUrl)) {
                    return ['success' => false, 'message' => $zipUrl->get_error_message()];
                }
                $downloadUrl = generarEnlaceDescargaColeccion($userId, $zipUrl, $postId);
                $this->actualizarTimestampDescargas($userId);
                return ['success' => true, 'download_url' => $downloadUrl];
            } else {
                procesarColeccion($postId, $userId, true);
                $this->actualizarTimestampDescargas($userId);
                return ['success' => true, 'message' => 'Sincronizado.'];
            }
        }

        return ['success' => false, 'message' => 'Función de colecciones no disponible.'];
    }

    /**
     * Genera un enlace de descarga temporal
     *
     * @param int $userId ID del usuario
     * @param int $audioId ID del audio
     * @return string URL de descarga
     */
    public function generarEnlaceDescarga(int $userId, int $audioId): string
    {
        $token = bin2hex(random_bytes(16));

        $tokenData = [
            'user_id' => $userId,
            'audio_id' => $audioId,
            'time' => time(),
            'usos' => 0
        ];

        set_transient('descarga_token_' . $token, $tokenData, HOUR_IN_SECONDS);

        return add_query_arg(['descarga_token' => $token], home_url());
    }

    /**
     * Procesa la descarga de un archivo de audio
     *
     * @param string $token Token de descarga
     * @return bool True si se procesó correctamente
     */
    public function manejarDescarga(string $token): bool
    {
        $tokenData = get_transient('descarga_token_' . $token);

        if (!$tokenData) {
            wp_die('El enlace de descarga no es válido o ha expirado.');
            return false;
        }

        /* Verificar usos */
        if ($tokenData['usos'] >= 2) {
            delete_transient('descarga_token_' . $token);
            wp_die('El enlace de descarga ha excedido el número de usos permitidos.');
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
        $tokenData['usos']++;
        set_transient('descarga_token_' . $token, $tokenData, HOUR_IN_SECONDS);

        if ($tokenData['usos'] >= 3) {
            delete_transient('descarga_token_' . $token);
        }

        exit;
    }

    /**
     * Resta pinkys a un usuario
     */
    private function restarPinkys(int $userId, int $cantidad): void
    {
        if (function_exists('restarPinkys')) {
            restarPinkys($userId, $cantidad);
        } else {
            $pinky = (int) get_user_meta($userId, 'pinky', true);
            update_user_meta($userId, 'pinky', max(0, $pinky - $cantidad));
        }
    }

    /**
     * Actualiza el timestamp de descargas del usuario
     */
    private function actualizarTimestampDescargas(int $userId): void
    {
        if (function_exists('actualizarTimestampDescargas')) {
            actualizarTimestampDescargas($userId);
        } else {
            update_user_meta($userId, 'timestamp_descargas', time());
        }
    }

    /**
     * Verifica si un post ya fue descargado por el usuario
     *
     * @param int $userId ID del usuario
     * @param int $postId ID del post
     * @return bool True si ya fue descargado
     */
    public function yaDescargado(int $userId, int $postId): bool
    {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true);
        return is_array($descargasAnteriores) && isset($descargasAnteriores[$postId]);
    }
}
