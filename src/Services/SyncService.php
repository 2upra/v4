<?php

/**
 * Servicio de sincronización para la aplicación Electron.
 * 
 * Gestiona la sincronización de audios entre la aplicación de escritorio
 * y el servidor. Incluye verificación de cambios, generación de tokens
 * de descarga y manejo de timestamps.
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class SyncService
{
    private static ?SyncService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): SyncService
    {
        if (self::$instancia === null) {
            self::$instancia = new SyncService();
        }
        return self::$instancia;
    }

    /**
     * Verifica si hay cambios en los audios del usuario desde la última sincronización.
     *
     * @param int $userId ID del usuario.
     * @param int $lastSyncTimestamp Timestamp de la última sincronización.
     * @param bool $forceSync Si es true, fuerza la sincronización.
     * @return array Datos de respuesta con timestamps y estado.
     */
    public function verificarCambios(int $userId, int $lastSyncTimestamp, bool $forceSync = false): array
    {
        $this->logger->debug('sync', '[verificarCambios] Inicio', [
            'userId' => $userId,
            'lastSync' => $lastSyncTimestamp,
            'force' => $forceSync
        ]);

        /* Transformar ID especial (compatibilidad legacy) */
        if ($userId === 355) {
            $userId = 1;
        }

        global $wpdb;

        $descargasTimestamp = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'descargas_modificado'",
            $userId
        ));

        $samplesTimestamp = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'samplesGuardados_modificado'",
            $userId
        ));

        $respuesta = [
            'descargas_modificado' => $descargasTimestamp,
            'samplesGuardados_modificado' => $samplesTimestamp,
            'force_sync' => false,
        ];

        if ($forceSync) {
            $this->logger->info('sync', 'Forzando sincronización', ['userId' => $userId]);
            $ahora = time();
            $respuesta['descargas_modificado'] = $ahora;
            $respuesta['samplesGuardados_modificado'] = $ahora;
            $respuesta['force_sync'] = true;
        } else {
            $hayCambios = $descargasTimestamp > $lastSyncTimestamp || $samplesTimestamp > $lastSyncTimestamp;
            $this->logger->debug('sync', $hayCambios ? 'Cambios detectados' : 'Sin cambios', ['userId' => $userId]);
        }

        return $respuesta;
    }

    /**
     * Obtiene la lista de audios del usuario para sincronización.
     *
     * @param int $userId ID del usuario.
     * @param int|null $postId ID de post específico (opcional).
     * @return array Lista de audios con URLs de descarga.
     */
    public function obtenerAudiosUsuario(int $userId, ?int $postId = null): array
    {
        /* Transformar ID especial */
        if ($userId === 355) {
            $userId = 1;
        }

        $this->logger->debug('sync', 'Obteniendo audios', ['userId' => $userId, 'postId' => $postId]);

        $descargas = get_user_meta($userId, 'descargas', true);
        $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
        $downloads = [];

        if (!is_array($descargas)) {
            $this->logger->warning('sync', 'Descargas no es un array', ['userId' => $userId]);
            return $downloads;
        }

        $postIds = array_keys($descargas);

        /* Filtrar por post_id si se proporciona */
        if ($postId !== null) {
            $postIds = array_intersect($postIds, [$postId]);
        }

        /* Obtener favoritos en una sola consulta */
        $favoritos = $this->obtenerFavoritos($userId, $postIds);

        foreach ($postIds as $currentPostId) {
            $audioData = $this->procesarAudioParaSync($currentPostId, $userId, $samplesGuardados, $favoritos);
            if ($audioData !== null) {
                $downloads = array_merge($downloads, $audioData);
            }
        }

        $this->logger->info('sync', 'Audios obtenidos', [
            'userId' => $userId,
            'total' => count($downloads)
        ]);

        return $downloads;
    }

    /**
     * Obtiene los favoritos del usuario para una lista de posts.
     *
     * @param int $userId ID del usuario.
     * @param array $postIds Lista de IDs de posts.
     * @return array Mapa de post_id => true para favoritos.
     */
    private function obtenerFavoritos(int $userId, array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        global $wpdb;
        $tableName = $wpdb->prefix . 'post_likes';
        $postIdsStr = implode(',', array_map('intval', $postIds));

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id FROM $tableName WHERE user_id = %d AND like_type = 'favorito' AND post_id IN ($postIdsStr)",
            $userId
        ));

        $favoritos = [];
        foreach ($resultados as $resultado) {
            $favoritos[$resultado->post_id] = true;
        }

        return $favoritos;
    }

    /**
     * Procesa un audio para incluirlo en la respuesta de sincronización.
     *
     * @param int $postId ID del post.
     * @param int $userId ID del usuario.
     * @param array|string $samplesGuardados Samples guardados del usuario.
     * @param array $favoritos Mapa de favoritos.
     * @return array|null Datos del audio o null si no es válido.
     */
    private function procesarAudioParaSync(int $postId, int $userId, $samplesGuardados, array $favoritos): ?array
    {
        $attachmentId = get_post_meta($postId, 'post_audio', true);

        if (empty($attachmentId) || !get_post($attachmentId)) {
            return null;
        }

        $filePath = get_attached_file($attachmentId);

        if (empty($filePath) || !file_exists($filePath)) {
            $this->logger->warning('sync', 'Archivo no existe', ['postId' => $postId, 'path' => $filePath]);
            return null;
        }

        $mimeType = mime_content_type($filePath);
        if (strpos($mimeType, 'audio/') !== 0) {
            $this->logger->warning('sync', 'Archivo no es audio', ['postId' => $postId, 'mime' => $mimeType]);
            return null;
        }

        /* Generar token y nonce de descarga */
        $token = wp_generate_password(20, false);
        $nonce = wp_create_nonce('download_' . $token);
        set_transient('sync_token_' . $token, $attachmentId, 300);

        /* Obtener imagen optimizada */
        $imagenUrl = $this->obtenerImagenOptimizada($postId);

        /* Procesar colecciones */
        $colecciones = isset($samplesGuardados[$postId]) ? $samplesGuardados[$postId] : ['No coleccionados'];
        $downloads = [];

        foreach ($colecciones as $collectionId) {
            $collectionName = ($collectionId !== 'No coleccionados')
                ? sanitize_title(get_the_title($collectionId))
                : 'No coleccionados';

            $downloads[] = [
                'post_id' => $postId,
                'collection' => $collectionName,
                'download_url' => home_url("/wp-json/sync/v1/download/?token=$token&nonce=$nonce"),
                'audio_filename' => get_the_title($attachmentId) . '.' . pathinfo($filePath, PATHINFO_EXTENSION),
                'image' => $imagenUrl,
                'es_favorito' => isset($favoritos[$postId])
            ];
        }

        return $downloads;
    }

    /**
     * Obtiene la imagen optimizada de un post.
     *
     * @param int $postId ID del post.
     * @return string|null URL de la imagen o null.
     */
    private function obtenerImagenOptimizada(int $postId): ?string
    {
        /* Intentar obtener imagen de portada */
        $portadaId = get_post_thumbnail_id($postId);
        if ($portadaId) {
            $portadaUrl = wp_get_attachment_url($portadaId);
            if ($portadaUrl && function_exists('img')) {
                return img($portadaUrl);
            }
            return $portadaUrl;
        }

        /* Intentar imagen temporal */
        $imagenTemporalId = get_post_meta($postId, 'imagenTemporal', true);
        if ($imagenTemporalId) {
            $imagenTemporalUrl = wp_get_attachment_url($imagenTemporalId);
            if ($imagenTemporalUrl && function_exists('img')) {
                return img($imagenTemporalUrl);
            }
            return $imagenTemporalUrl;
        }

        return null;
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

    /**
     * Obtiene información de un usuario.
     *
     * @param int $receptorId ID del usuario receptor.
     * @return array Datos del usuario (imagen, nombre).
     */
    public function obtenerInfoUsuario(int $receptorId): array
    {
        $imagenPerfil = function_exists('imagenPerfil') ? imagenPerfil($receptorId) : '';
        $nombreUsuario = function_exists('obtenerNombreUsuario') ? obtenerNombreUsuario($receptorId) : 'Usuario';

        return [
            'imagenPerfil' => $imagenPerfil ?: 'ruta_por_defecto.jpg',
            'nombreUsuario' => $nombreUsuario ?: 'Usuario Desconocido',
        ];
    }

    /**
     * Actualiza el timestamp de descargas de un usuario.
     *
     * @param int $userId ID del usuario.
     */
    public function actualizarTimestampDescargas(int $userId): void
    {
        $tiempo = time();
        update_user_meta($userId, 'descargas_modificado', $tiempo);
        $this->logger->debug('sync', 'Timestamp descargas actualizado', [
            'userId' => $userId,
            'timestamp' => $tiempo
        ]);
    }

    /**
     * Actualiza el timestamp de samples guardados de un usuario.
     *
     * @param int $userId ID del usuario.
     */
    public function actualizarTimestampSamplesGuardados(int $userId): void
    {
        update_user_meta($userId, 'samplesGuardados_modificado', time());
    }
}
