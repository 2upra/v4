<?php

namespace Kamples\Services;

/**
 * Servicio de optimización de imágenes.
 * 
 * Maneja la optimización de imágenes usando CDN y
 * operaciones de archivos adjuntos.
 *
 * @since 1.0.0
 */
class ImagenService
{
    private static ?ImagenService $instancia = null;
    private ?\Logger $logger = null;

    /**
     * Constructor privado (Singleton).
     */
    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia singleton del servicio.
     *
     * @return self
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Optimiza una URL de imagen usando CDN de WordPress.
     *
     * @param string|null $url URL de la imagen
     * @param int $quality Calidad (1-100)
     * @param string $strip Qué metadatos eliminar ('all', 'color', 'none')
     * @return string URL optimizada
     */
    public function optimizar(?string $url, int $quality = 40, string $strip = 'all'): string
    {
        if ($url === null || $url === '') {
            return '';
        }

        $parsedUrl = parse_url($url);

        /* Si ya es una URL del CDN, no la modificamos */
        if (strpos($url, 'https://i0.wp.com/') === 0) {
            $cdnUrl = $url;
        } else {
            $path = isset($parsedUrl['host'])
                ? $parsedUrl['host'] . ($parsedUrl['path'] ?? '')
                : ltrim($parsedUrl['path'] ?? '', '/');
            $cdnUrl = 'https://i0.wp.com/' . $path;
        }

        $query = [
            'quality' => $quality,
            'strip' => $strip,
        ];

        return add_query_arg($query, $cdnUrl);
    }

    /**
     * Sube una imagen desde una URL.
     *
     * @param string $imageUrl URL de la imagen
     * @param int $postId ID del post al que se asociará
     * @return int|false ID del adjunto o false
     */
    public function subirImagenDesdeUrl(string $imageUrl, int $postId)
    {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $media = media_sideload_image($imageUrl, $postId, null, 'id');

        if (is_wp_error($media)) {
            $this->log('error', 'Error al subir imagen: ' . $media->get_error_message());
            return false;
        }

        return $media;
    }

    /**
     * Adjunta un archivo a un post.
     *
     * @param int $postId ID del post
     * @param string $fileUrl URL del archivo
     * @return bool
     */
    public function adjuntarArchivo(int $postId, string $fileUrl): bool
    {
        global $wpdb;

        /* Verificar si el adjunto ya existe */
        $attachmentId = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE guid = %s",
            $fileUrl
        ));

        if (!$attachmentId) {
            $uploadsDir = wp_upload_dir();
            $filePath = str_replace($uploadsDir['baseurl'], $uploadsDir['basedir'], $fileUrl);

            if (!file_exists($filePath)) {
                $this->log('error', "Archivo físico no encontrado: $filePath");
                return false;
            }

            $mimeType = mime_content_type($filePath);
            $data = [
                'guid'           => $fileUrl,
                'post_mime_type' => $mimeType,
                'post_title'     => wp_basename($filePath),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $attachmentId = wp_insert_attachment($data, $filePath, $postId);

            if (is_wp_error($attachmentId)) {
                $this->log('error', 'Error al crear adjunto: ' . $attachmentId->get_error_message());
                return false;
            }

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachData = wp_generate_attachment_metadata($attachmentId, $filePath);
            wp_update_attachment_metadata($attachmentId, $attachData);
        }

        if ($attachmentId) {
            $this->actualizarMetadatosAdjunto($postId, $attachmentId, $fileUrl);
            return true;
        }

        return false;
    }

    /**
     * Actualiza los metadatos de un adjunto.
     *
     * @param int $postId ID del post
     * @param int $attachmentId ID del adjunto
     * @param string $fileUrl URL del archivo
     * @return void
     */
    private function actualizarMetadatosAdjunto(int $postId, int $attachmentId, string $fileUrl): void
    {
        /* Recuperar metadatos existentes */
        $fileAdjIds = get_post_meta($postId, 'fileAdjIds', true) ?: [];
        $fileAdjUrls = get_post_meta($postId, 'fileAdjUrls', true) ?: [];

        /* Actualizar las listas */
        $fileAdjIds[] = $attachmentId;
        $fileAdjUrls[] = $fileUrl;

        /* Guardar los datos actualizados */
        update_post_meta($postId, 'fileAdjIds', array_unique($fileAdjIds));
        update_post_meta($postId, 'fileAdjUrls', array_unique($fileAdjUrls));

        /* Obtener MIME type y procesar según el tipo */
        $mimeType = get_post_mime_type($attachmentId);

        if (strpos($mimeType, 'audio') !== false) {
            $this->procesarAudioAdjunto($postId, $attachmentId);
        } elseif (strpos($mimeType, 'image') !== false) {
            $this->procesarImagenAdjunta($postId, $attachmentId);
        }

        $this->log('info', "Archivo adjuntado con ID: $attachmentId");
    }

    /**
     * Procesa un audio adjunto.
     *
     * @param int $postId ID del post
     * @param int $attachmentId ID del adjunto
     * @return void
     */
    private function procesarAudioAdjunto(int $postId, int $attachmentId): void
    {
        $audioAdjIds = get_post_meta($postId, 'audioAdjIds', true) ?: [];
        $audioAdjIds[] = $attachmentId;
        update_post_meta($postId, 'audioAdjIds', array_unique($audioAdjIds));

        /* Procesar audio usando el servicio */
        $audioProcessingService = AudioProcessingService::obtenerInstancia();
        $audioProcessingService->procesarAudioLigero($postId, $attachmentId, 1);
    }

    /**
     * Procesa una imagen adjunta.
     *
     * @param int $postId ID del post
     * @param int $attachmentId ID del adjunto
     * @return void
     */
    private function procesarImagenAdjunta(int $postId, int $attachmentId): void
    {
        $imgAdjIds = get_post_meta($postId, 'imgAdjIds', true) ?: [];

        /* Establecer la primera imagen como portada */
        if (empty($imgAdjIds)) {
            set_post_thumbnail($postId, $attachmentId);
        }

        $imgAdjIds[] = $attachmentId;
        update_post_meta($postId, 'imgAdjIds', array_unique($imgAdjIds));
    }

    /**
     * Registra un mensaje en el log.
     *
     * @param string $nivel Nivel del log
     * @param string $mensaje Mensaje
     * @return void
     */
    private function log(string $nivel, string $mensaje): void
    {
        if ($this->logger) {
            $this->logger->$nivel('post', $mensaje);
        }
    }
}
