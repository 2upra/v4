<?php

/**
 * Servicio de regeneración de archivos de audio lite
 * 
 * Regenera versiones MP3 lite (20 segundos, 64kbps) de archivos WAV
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class AudioRegeneracionService
{
    private static ?AudioRegeneracionService $instancia = null;
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
     * Regenera archivos de audio lite que falten
     */
    public function regenerarLite(): void
    {
        global $wpdb;

        $postsConAudio = $wpdb->get_results("
            SELECT post_id, meta_value as audio_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'post_audio'
        ");

        if (empty($postsConAudio)) {
            $this->logger->info('audio', "No se encontraron posts con post_audio");
            return;
        }

        $uploadsDir = wp_upload_dir();
        $audioDir = trailingslashit($uploadsDir['basedir']) . "audio/";

        if (!file_exists($audioDir)) {
            wp_mkdir_p($audioDir);
        }

        foreach ($postsConAudio as $post) {
            $this->procesarRegeneracionLite($post->post_id, $post->audio_id, $audioDir);
        }
    }

    /**
     * Procesa la regeneración de un archivo lite individual
     */
    private function procesarRegeneracionLite(int $postId, int $audioId, string $audioDir): void
    {
        $audioLiteId = get_post_meta($postId, 'post_audio_lite', true);
        $wavFile = get_attached_file($audioId);

        if (!$wavFile || !file_exists($wavFile)) {
            $this->logger->warning('audio', "Archivo WAV no encontrado para post_id: $postId, audio_id: $audioId");
            return;
        }

        $wavInfo = pathinfo($wavFile);
        $mp3Filename = $wavInfo['filename'] . '_lite.mp3';
        $mp3Path = $audioDir . $mp3Filename;

        $regenerar = false;

        if (!$audioLiteId) {
            $this->logger->info('audio', "No existe post_audio_lite para post_id: $postId");
            $regenerar = true;
        } else {
            $liteFile = get_attached_file($audioLiteId);
            if (!$liteFile || !file_exists($liteFile)) {
                $this->logger->info('audio', "Archivo lite no encontrado para post_id: $postId");
                $regenerar = true;
            }
        }

        if ($regenerar) {
            $this->generarArchivoLite($postId, $wavFile, $mp3Path, $mp3Filename);
        }
    }

    /**
     * Genera un archivo MP3 lite usando ffmpeg
     */
    private function generarArchivoLite(int $postId, string $wavFile, string $mp3Path, string $mp3Filename): void
    {
        $comando = "/usr/bin/ffmpeg -i " . escapeshellarg($wavFile)
            . " -b:a 64k -ar 44100 -t 20 -af 'afade=t=out:st=15:d=5' "
            . escapeshellarg($mp3Path) . " -y";

        exec($comando, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($mp3Path)) {
            $this->logger->error('audio', "Error al generar MP3 para post_id: $postId - " . implode(" | ", $output));
            return;
        }

        $filetype = wp_check_filetype($mp3Filename, null);
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => $mp3Filename,
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachId = wp_insert_attachment($attachment, $mp3Path, $postId);

        if (is_wp_error($attachId)) {
            $this->logger->error('audio', "Error al crear attachment para post_id: $postId - " . $attachId->get_error_message());
            return;
        }

        $attachData = wp_generate_attachment_metadata($attachId, $mp3Path);
        wp_update_attachment_metadata($attachId, $attachData);
        update_post_meta($postId, 'post_audio_lite', $attachId);

        $this->logger->info('audio', "Regenerado exitosamente audio lite para post_id: $postId");
    }
}
