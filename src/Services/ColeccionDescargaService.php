<?php

/**
 * Servicio de descarga de colecciones
 * 
 * Gestiona la creación de ZIPs, tokens de descarga y streaming de colecciones
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class ColeccionDescargaService
{
    private static ?ColeccionDescargaService $instancia = null;
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
     * Procesa una colección para descarga o sincronización
     *
     * @param int $postId ID del post de la colección
     * @param int $userId ID del usuario
     * @param bool $sync Si es sincronización (no crea ZIP)
     * @return string|\WP_Error Ruta del ZIP o true si sync, WP_Error en caso de error
     */
    public function procesarColeccion(int $postId, int $userId, bool $sync = false)
    {
        $this->logger->debug('descarga', 'Inicio de procesarColeccion', [
            'postId' => $postId,
            'userId' => $userId,
            'sync' => $sync
        ]);

        $samples = get_post_meta($postId, 'samples', true);
        $numSamples = is_array($samples) ? count($samples) : 0;

        if ($numSamples === 0) {
            $this->logger->warning('descarga', 'No hay samples en la colección', ['postId' => $postId]);
            return new \WP_Error('no_samples', __('No hay samples en esta colección.', 'kamples'));
        }

        $zipPath = '';
        if (!$sync) {
            $zipName = 'coleccion-' . $postId . '-' . $numSamples . '.zip';
            $uploadDir = wp_upload_dir();
            $zipPath = $uploadDir['path'] . '/' . $zipName;

            $this->limpiarZipsAntiguos($uploadDir['path'], $postId, $zipPath);

            if (!is_dir($uploadDir['path']) || !is_writable($uploadDir['path'])) {
                return new \WP_Error('upload_dir_error', __('El directorio de uploads no tiene permisos de escritura.', 'kamples'));
            }
        }

        list($samplesDescargados, $samplesNoDescargados) = $this->clasificarSamples($samples, $userId);
        $numSamplesNoDescargados = count($samplesNoDescargados);

        if (!$sync) {
            $resultado = $this->crearOValidarZip($zipPath, $samples, $userId, $numSamplesNoDescargados);
            if (is_wp_error($resultado)) {
                return $resultado;
            }
        }

        $resultadoPinkys = $this->verificarYRestarPinkys($userId, $numSamplesNoDescargados, $sync, $zipPath ?? '');
        if (is_wp_error($resultadoPinkys)) {
            return $resultadoPinkys;
        }

        $this->actualizarEstadisticas($userId, $postId, $samplesNoDescargados, $samplesDescargados);

        if (!$sync) {
            return $zipPath;
        }

        return true;
    }

    /**
     * Genera un enlace de descarga seguro con token
     *
     * @param int $userId ID del usuario
     * @param string $zipPath Ruta física del ZIP
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

        $this->logger->info('descarga', 'Enlace de descarga de colección generado', [
            'postId' => $postId,
            'userId' => $userId
        ]);

        return $enlaceDescarga;
    }

    /**
     * Procesa la descarga de una colección desde un token
     * Se llama desde template_redirect hook
     */
    public function procesarDescargaDesdeToken(): void
    {
        if (!isset($_GET['descarga_token']) || !isset($_GET['tipo']) || $_GET['tipo'] !== 'coleccion') {
            return;
        }

        $token = sanitize_text_field($_GET['descarga_token']);

        if (!ctype_xdigit($token)) {
            $this->logger->error('descarga', 'Token inválido', ['token' => $token]);
            wp_die('El token de descarga no es válido.');
        }

        $tokenData = get_transient('descarga_token_' . $token);

        if (!$tokenData) {
            $this->logger->warning('descarga', 'Token expirado o no válido', ['token' => $token]);
            wp_die('El enlace de descarga no es válido o ha expirado.');
        }

        if ($tokenData['usos'] >= 2) {
            delete_transient('descarga_token_' . $token);
            $this->logger->warning('descarga', 'Token excedió usos permitidos', ['token' => $token]);
            wp_die('El enlace de descarga ha excedido el número de usos permitidos.');
        }

        $zipPath = $tokenData['zip_path'];

        if (!$zipPath || !file_exists($zipPath) || !is_readable($zipPath)) {
            $this->logger->error('descarga', 'Archivo ZIP no accesible', ['path' => $zipPath]);
            wp_die('El archivo no existe o no es accesible.');
        }

        $this->streamZip($zipPath, $token, $tokenData);
    }

    /**
     * Limpia ZIPs antiguos de la colección
     */
    private function limpiarZipsAntiguos(string $uploadPath, int $postId, string $zipPathActual): void
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
     * Clasifica samples en descargados y no descargados
     *
     * @param array $samples Array de IDs de samples
     * @param int $userId ID del usuario
     * @return array [samplesDescargados, samplesNoDescargados]
     */
    public function clasificarSamples(array $samples, int $userId): array
    {
        $samplesDescargados = [];
        $samplesNoDescargados = [];

        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];
        if (!is_array($descargasAnteriores)) {
            $descargasAnteriores = [];
        }

        foreach ($samples as $sampleId) {
            if (isset($descargasAnteriores[$sampleId])) {
                $samplesDescargados[] = $sampleId;
            } else {
                $samplesNoDescargados[] = $sampleId;
            }
        }

        return [$samplesDescargados, $samplesNoDescargados];
    }

    /**
     * Crea o valida un archivo ZIP
     */
    private function crearOValidarZip(string $zipPath, array $samples, int $userId, int $numNoDescargados)
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
     * @return bool True si se agregó al menos un archivo
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
     * Verifica y resta pinkys del usuario
     */
    private function verificarYRestarPinkys(int $userId, int $numNoDescargados, bool $sync, string $zipPath)
    {
        if ($numNoDescargados <= 0) {
            return true;
        }

        $pinky = (int)get_user_meta($userId, 'pinky', true);

        if ($pinky < $numNoDescargados) {
            if (!$sync && !empty($zipPath) && file_exists($zipPath)) {
                unlink($zipPath);
            }
            return new \WP_Error('no_pinkys', sprintf(
                __('No tienes suficientes Pinkys. Se requieren %d pinkys', 'kamples'),
                $numNoDescargados
            ));
        }

        if (function_exists('restarPinkys')) {
            restarPinkys($userId, $numNoDescargados);
        }

        return true;
    }

    /**
     * Actualiza estadísticas de descarga
     */
    private function actualizarEstadisticas(int $userId, int $postId, array $samplesNoDescargados, array $samplesDescargados): void
    {
        if (function_exists('actualizarTimestampDescargas')) {
            actualizarTimestampDescargas($userId);
        }

        $this->actualizarDescargas($userId, $samplesNoDescargados, $samplesDescargados);

        $totalDescargas = (int)get_post_meta($postId, 'totalDescargas', true);
        $totalDescargas++;
        update_post_meta($postId, 'totalDescargas', $totalDescargas);

        $this->logger->info('descarga', 'Estadísticas actualizadas', [
            'postId' => $postId,
            'totalDescargas' => $totalDescargas
        ]);
    }

    /**
     * Actualiza el registro de descargas del usuario
     */
    private function actualizarDescargas(int $userId, array $samplesNoDescargados, array $samplesDescargados): void
    {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];
        if (!is_array($descargasAnteriores)) {
            $descargasAnteriores = [];
        }

        foreach ($samplesNoDescargados as $sampleId) {
            $descargasAnteriores[$sampleId] = 1;
        }

        foreach ($samplesDescargados as $sampleId) {
            if (isset($descargasAnteriores[$sampleId])) {
                $descargasAnteriores[$sampleId]++;
            }
        }

        update_user_meta($userId, 'descargas', $descargasAnteriores);
    }

    /**
     * Stream del archivo ZIP al usuario
     */
    private function streamZip(string $zipPath, string $token, array $tokenData): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        ini_set('zlib.output_compression', 'Off');
        ini_set('output_buffering', 'Off');
        set_time_limit(0);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            wp_die('Error al obtener información del archivo.');
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

        $this->logger->info('descarga', 'Descarga de colección completada', ['token' => $token]);
        exit;
    }
}
