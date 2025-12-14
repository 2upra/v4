<?php

/**
 * Servicio de protección y optimización de audio
 * 
 * Protege archivos de audio contra eliminación y gestiona la optimización
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class AudioProteccionService
{
    private static ?AudioProteccionService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->registrarHooks();
        $this->registrarCronJobs();
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
     * Registra los hooks de WordPress
     */
    private function registrarHooks(): void
    {
        /* Proteger archivos de audio contra eliminación */
        add_filter('pre_delete_attachment', [$this, 'protegerArchivoAudio'], 10, 2);
    }

    /**
     * Registra los cron jobs
     */
    private function registrarCronJobs(): void
    {
        /* Intervalo de 6 horas */
        add_filter('cron_schedules', [$this, 'agregarIntervalos']);

        /* Programar eventos */
        if (!wp_next_scheduled('regenerar_audio_lite_evento')) {
            wp_schedule_event(time(), 'cada_seis_horas', 'regenerar_audio_lite_evento');
        }

        if (!wp_next_scheduled('minutos55_evento')) {
            wp_schedule_event(time(), 'cada55', 'minutos55_evento');
        }

        /* Registrar callbacks */
        add_action('regenerar_audio_lite_evento', [$this, 'regenerarLite']);
        add_action('minutos55_evento', [$this, 'optimizar64kAudios']);
    }

    /**
     * Agrega intervalos personalizados al cron
     */
    public function agregarIntervalos(array $schedules): array
    {
        $schedules['cada_seis_horas'] = [
            'interval' => 21600,
            'display' => __('Cada 6 Horas')
        ];

        $schedules['cada55'] = [
            'interval' => 3300,
            'display' => __('Cada 55 minutos')
        ];

        return $schedules;
    }

    /**
     * Protege archivos de audio contra eliminación accidental
     */
    public function protegerArchivoAudio($delete, $post): bool
    {
        $archivoPath = get_attached_file($post);

        if ($archivoPath && strpos($archivoPath, '/audio/') !== false) {
            $this->logger->warning('audio', "Intento de eliminación de archivo de audio: $archivoPath");
            return false;
        }

        return $delete;
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
    private function limpiarWaveform(int $postId): void
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
