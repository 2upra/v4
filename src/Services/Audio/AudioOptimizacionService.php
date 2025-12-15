<?php

/**
 * Servicio de optimización de archivos de audio
 * 
 * Optimiza audios a 64kbps para streaming y gestiona waveforms
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class AudioOptimizacionService
{
    private static ?AudioOptimizacionService $instancia = null;
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
     * Optimiza audios a 64kbps en lote
     */
    public function optimizar64kAudios(int $limite = 10000): void
    {
        $query = new \WP_Query([
            'post_type' => 'social_post',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'audio_optimizado',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'rola',
                    'compare' => 'NOT EXISTS',
                    'value' => '1'
                ]
            ],
            'posts_per_page' => $limite,
            'fields' => 'ids',
            'no_found_rows' => true
        ]);

        if ($query->have_posts()) {
            foreach ($query->posts as $postId) {
                $this->optimizarAudioPost($postId);
            }
        }

        wp_reset_postdata();
    }

    /**
     * Optimiza el audio de un post individual
     */
    public function optimizarAudioPost(int $postId): void
    {
        $audioId = get_post_meta($postId, 'post_audio', true);
        $audioLiteId = get_post_meta($postId, 'post_audio_lite', true);
        $audioOptimizadoMeta = get_post_meta($postId, 'audio_optimizado', true);

        if ($audioOptimizadoMeta) {
            return;
        }

        if (!$audioId) {
            $this->logger->warning('audio', "No se encontró el audio para el post ID $postId");
            return;
        }

        $archivoOriginal = get_attached_file($audioId);

        if (!$archivoOriginal || !file_exists($archivoOriginal)) {
            $this->logger->warning('audio', "No se encontró el archivo original para el ID: $audioId");
            return;
        }

        /* Mover el ID actual a post_audio_lite_128k si existe */
        if ($audioLiteId) {
            update_post_meta($postId, 'post_audio_lite_128k', $audioLiteId);
        }

        /* Obtener duración original */
        $duracionOriginal = shell_exec("/usr/bin/ffprobe -i " . escapeshellarg($archivoOriginal) . " -show_entries format=duration -v quiet -of csv='p=0'");
        $duracionOriginal = (float) trim($duracionOriginal);
        update_post_meta($postId, 'duracionAudio', $duracionOriginal);

        /* Generar ruta optimizada */
        $rutaInfo = pathinfo($archivoOriginal);
        $rutaOptimizada = $rutaInfo['dirname'] . '/' . $rutaInfo['filename'] . '_optimizado.mp3';

        /* Convertir audio */
        $comando = "/usr/bin/ffmpeg -i " . escapeshellarg($archivoOriginal)
            . " -b:a 64k -ar 44100 -t 20 -af 'afade=t=out:st=15:d=5' "
            . escapeshellarg($rutaOptimizada) . " -y";

        exec($comando, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->logger->error('audio', "Error al optimizar el archivo $archivoOriginal: " . implode("\n", $output));
            return;
        }

        /* Insertar nuevo adjunto */
        $nuevoAudioId = wp_insert_attachment([
            'post_mime_type' => 'audio/mpeg',
            'post_title' => $rutaInfo['filename'] . '_optimizado',
            'post_content' => '',
            'post_status' => 'inherit'
        ], $rutaOptimizada, $postId);

        if ($nuevoAudioId) {
            wp_update_attachment_metadata($nuevoAudioId, wp_generate_attachment_metadata($nuevoAudioId, $rutaOptimizada));
            update_post_meta($postId, 'post_audio_lite', $nuevoAudioId);

            /* Limpiar waveform si el audio era más largo */
            if ($duracionOriginal > 20) {
                $this->limpiarWaveform($postId);
                update_post_meta($postId, 'recortado', true);
            }

            update_post_meta($postId, 'audio_optimizado', 1);
            $this->logger->info('audio', "Audio optimizado correctamente para post ID $postId");
        } else {
            $this->logger->error('audio', "Error al insertar el adjunto optimizado para el post ID $postId.");
        }
    }

    /**
     * Limpia los datos de waveform de un post
     */
    public function limpiarWaveform(int $postId): void
    {
        $waveCargada = get_post_meta($postId, 'waveCargada', true);
        $waveformImageId = get_post_meta($postId, 'waveform_image_id', true);

        if ($waveCargada == 1 && $waveformImageId) {
            wp_delete_attachment($waveformImageId, true);
            delete_post_meta($postId, 'waveCargada');
            delete_post_meta($postId, 'waveform_image_id');
            delete_post_meta($postId, 'waveform_image_url');
            $this->logger->info('audio', "Waveform eliminado para el post ID $postId.");
        }
    }
}
