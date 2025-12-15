<?php

namespace Kamples\Services\Audio;

/**
 * Servicio de gestión de waveforms.
 * 
 * Maneja la subida y gestión de imágenes de waveform de audio.
 *
 * @since 1.0.0
 */
class WaveformService
{
    private \Logger $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Guarda una imagen de waveform para un post.
     *
     * @param array $file Archivo subido ($_FILES['image']).
     * @param int $postId ID del post.
     * @return array|false Datos de la imagen guardada o false en caso de error.
     */
    public function guardarWaveform(array $file, int $postId): array|false
    {
        if (empty($file) || $postId <= 0) {
            $this->logger->error('audio', 'Datos incompletos para guardar waveform');
            return false;
        }

        /* Eliminar imagen anterior si waveCargada es false */
        if (get_post_meta($postId, 'waveCargada', true) === 'false') {
            $this->eliminarWaveformExistente($postId);
        }

        /* Agregar el ID del post al nombre del archivo */
        add_filter('wp_handle_upload_prefilter', function ($fileData) use ($postId) {
            $fileData['name'] = $postId . '_' . $fileData['name'];
            return $fileData;
        });

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $authorId = get_post_field('post_author', $postId);

        /* Simular $_FILES para media_handle_upload */
        $_FILES['imagen_waveform'] = $file;
        $attachmentId = media_handle_upload('imagen_waveform', $postId, ['post_author' => $authorId]);

        if (is_wp_error($attachmentId)) {
            $this->logger->error('audio', 'Error al subir waveform', ['error' => $attachmentId->get_error_message()]);
            return false;
        }

        $imageUrl = wp_get_attachment_url($attachmentId);
        $filePath = get_attached_file($attachmentId);
        $fileSize = file_exists($filePath) ? size_format(filesize($filePath), 2) : '0 B';

        update_post_meta($postId, 'waveform_image_id', $attachmentId);
        update_post_meta($postId, 'waveform_image_url', $imageUrl);
        update_post_meta($postId, 'waveCargada', true);

        $this->logger->info('audio', "Waveform guardado para post {$postId}");

        return [
            'url' => $imageUrl,
            'size' => $fileSize,
            'attachment_id' => $attachmentId
        ];
    }

    /**
     * Elimina la waveform existente de un post.
     *
     * @param int $postId ID del post.
     * @return bool True si se eliminó, false si no existía.
     */
    public function eliminarWaveformExistente(int $postId): bool
    {
        $existingAttachmentId = get_post_meta($postId, 'waveform_image_id', true);

        if ($existingAttachmentId) {
            wp_delete_attachment($existingAttachmentId, true);
            delete_post_meta($postId, 'waveform_image_id');
            delete_post_meta($postId, 'waveform_image_url');

            $this->logger->info('audio', "Waveform eliminado para post {$postId}");
            return true;
        }

        return false;
    }

    /**
     * Resetea los metadatos de waveform de todos los posts.
     * 
     * Útil para regenerar todas las waveforms.
     *
     * @return int Número de posts procesados.
     */
    public function resetearTodasLasWaveforms(): int
    {
        $this->logger->info('audio', 'Iniciando reset de waveforms');

        $args = [
            'post_type' => 'social_post',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'waveCargada',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ];

        $query = new \WP_Query($args);
        $contador = 0;

        $this->logger->info('audio', "Posts encontrados con waveform: {$query->found_posts}");

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $postId = get_the_ID();

                update_post_meta($postId, 'waveCargada', false);
                $this->eliminarWaveformExistente($postId);

                $contador++;
            }
        }

        wp_reset_postdata();

        $this->logger->info('audio', "Reset completado. Posts procesados: {$contador}");

        return $contador;
    }

    /**
     * Obtiene la URL de waveform de un post.
     *
     * @param int $postId ID del post.
     * @return string|null URL de la waveform o null si no existe.
     */
    public function obtenerWaveformUrl(int $postId): ?string
    {
        $url = get_post_meta($postId, 'waveform_image_url', true);
        return !empty($url) ? $url : null;
    }

    /**
     * Verifica si un post tiene waveform cargada.
     *
     * @param int $postId ID del post.
     * @return bool True si tiene waveform cargada.
     */
    public function tieneWaveformCargada(int $postId): bool
    {
        return (bool) get_post_meta($postId, 'waveCargada', true);
    }
}
