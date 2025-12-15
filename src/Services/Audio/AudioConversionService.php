<?php

/**
 * Servicio de conversión de audio
 * 
 * Maneja las operaciones de conversión y procesamiento FFmpeg:
 * - Creación de versiones ligeras (128k)
 * - Eliminación de metadatos
 * - Extracción de duración
 * - Inserción en biblioteca de medios
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class AudioConversionService
{
    private static ?AudioConversionService $instancia = null;
    private \Logger $logger;

    /* Rutas de ejecutables */
    private const FFMPEG_PATH = '/usr/bin/ffmpeg';
    private const FFPROBE_PATH = '/usr/bin/ffprobe';

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
     * Elimina los metadatos de un archivo de audio
     */
    public function eliminarMetadatos(string $audioPath): bool
    {
        $tempPath = $audioPath . '.tmp';
        $comando = sprintf(
            '%s -i %s -map_metadata -1 -c:v copy %s && mv %s %s',
            self::FFMPEG_PATH,
            escapeshellarg($audioPath),
            escapeshellarg($tempPath),
            escapeshellarg($tempPath),
            escapeshellarg($audioPath)
        );

        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->logger->warning('audio', 'Error al eliminar metadatos del archivo original', ['output' => implode("\n", $output)]);
            return false;
        }

        $this->logger->debug('audio', 'Metadatos del archivo original eliminados correctamente');
        return true;
    }

    /**
     * Crea una versión ligera (128k) del audio con metadatos personalizados
     */
    public function crearVersionLigera(string $audioPath, string $outputPath, string $author, string $comment): bool
    {
        $comando = sprintf(
            '%s -i %s -b:a 128k -metadata author=%s -metadata comment=%s %s',
            self::FFMPEG_PATH,
            escapeshellarg($audioPath),
            escapeshellarg($author),
            escapeshellarg($comment),
            escapeshellarg($outputPath)
        );

        $this->logger->debug('audio', "Ejecutando comando para crear audio ligero: {$comando}");
        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->logger->error('audio', 'Error al procesar audio ligero', ['output' => implode("\n", $output)]);
            return false;
        }

        $this->logger->info('audio', 'Audio ligero creado exitosamente con metadatos');
        return true;
    }

    /**
     * Extrae y guarda la duración del audio
     */
    public function guardarDuracion(string $filePath, int $postId, int $index): void
    {
        $comando = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            self::FFPROBE_PATH,
            escapeshellarg($filePath)
        );

        $durationInSeconds = trim(shell_exec($comando));

        if (is_numeric($durationInSeconds)) {
            $durationInSeconds = (float)$durationInSeconds;
            $durationFormatted = floor($durationInSeconds / 60) . ':' . str_pad(floor($durationInSeconds % 60), 2, '0', STR_PAD_LEFT);
            update_post_meta($postId, "audio_duration_{$index}", $durationFormatted);
            $this->logger->debug('audio', "Duración del audio (formateada): {$durationFormatted}");
        } else {
            $this->logger->warning('audio', "Duración del audio no válida para el archivo {$filePath}");
        }
    }

    /**
     * Inserta un archivo en la biblioteca de medios de WordPress
     */
    public function insertarEnMediaLibrary(string $filePath, int $postId): ?int
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $filetype = wp_check_filetype(basename($filePath), null);

        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filePath)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachId = wp_insert_attachment($attachment, $filePath, $postId);

        if (is_wp_error($attachId)) {
            $this->logger->error('audio', 'Error al insertar el adjunto ligero', ['error' => $attachId->get_error_message()]);
            return null;
        }

        $attachData = wp_generate_attachment_metadata($attachId, $filePath);
        wp_update_attachment_metadata($attachId, $attachData);

        $this->logger->debug('audio', "ID de adjunto ligero: {$attachId}");
        return $attachId;
    }

    /**
     * Obtiene el nombre de usuario del autor del post
     */
    public function obtenerNombreAutor(int $postId): string
    {
        $postAuthorId = get_post_field('post_author', $postId);
        $authorInfo = get_userdata($postAuthorId);

        if ($authorInfo) {
            return $authorInfo->user_login;
        }

        $this->logger->warning('audio', "No se pudo obtener el nombre de usuario del autor para Post ID: {$postId}");
        return 'Desconocido';
    }
}
