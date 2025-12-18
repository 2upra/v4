<?php

/**
 * Servicio de gestión de audios para sincronización.
 * 
 * Maneja la obtención y procesamiento de audios del usuario
 * para la sincronización con la aplicación Electron.
 *
 * @package Kamples\Services\Core
 * @since 1.0.0
 */

namespace Kamples\Services\Core;

use Kamples\Services\Usuario\PerfilService;
use Kamples\Services\Contenido\ImagenService;

class SyncAudioService
{
    private static ?SyncAudioService $instancia = null;
    private \Logger $logger;
    private SyncVerificacionService $verificacionService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->verificacionService = SyncVerificacionService::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): SyncAudioService
    {
        if (self::$instancia === null) {
            self::$instancia = new SyncAudioService();
        }
        return self::$instancia;
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
        $userId = $this->verificacionService->normalizarUserId($userId);

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
            if ($portadaUrl) {
                return ImagenService::obtenerInstancia()->optimizar($portadaUrl);
            }
            return $portadaUrl;
        }

        /* Intentar imagen temporal */
        $imagenTemporalId = get_post_meta($postId, 'imagenTemporal', true);
        if ($imagenTemporalId) {
            $imagenTemporalUrl = wp_get_attachment_url($imagenTemporalId);
            if ($imagenTemporalUrl) {
                return ImagenService::obtenerInstancia()->optimizar($imagenTemporalUrl);
            }
            return $imagenTemporalUrl;
        }

        return null;
    }

    /**
     * Obtiene información de un usuario.
     *
     * @param int $receptorId ID del usuario receptor.
     * @return array Datos del usuario (imagen, nombre).
     */
    public function obtenerInfoUsuario(int $receptorId): array
    {
        $imagenPerfil = PerfilService::obtenerInstancia()->obtenerImagenPerfil($receptorId);
        $usuario = get_userdata($receptorId);
        $nombreUsuario = $usuario ? ($usuario->display_name ?: $usuario->user_login) : 'Usuario';

        return [
            'imagenPerfil' => $imagenPerfil ?: 'ruta_por_defecto.jpg',
            'nombreUsuario' => $nombreUsuario ?: 'Usuario Desconocido',
        ];
    }
}
