<?php

namespace App\Services\Post;

use App\Utils\Logger;
use App\Services\IAService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PostAudioRenamingService
{
    private Logger $logger;
    private IAService $iaService;
    private PostAttachmentService $postAttachmentService;

    const BASE_AUDIO_SEARCH_PATH = '/home/asley01/MEGA/Waw/X';

    public function __construct(Logger $logger, IAService $iaService, PostAttachmentService $postAttachmentService)
    {
        $this->logger = $logger;
        $this->iaService = $iaService;
        $this->postAttachmentService = $postAttachmentService;
    }

    public function renameAudio(int $postId, string $audioFilePath): ?string
    {
        $userId = get_current_user_id();

        if (!$this->validatePermissionsAndFileExists($userId, $audioFilePath)) {
            return null;
        }

        $post_content = get_post_field('post_content', $postId);
        if (!$post_content) {
            $this->logger->error("No se pudo obtener el contenido del post ID: {$postId}");
            return null;
        }

        $nombre_archivo = pathinfo($audioFilePath, PATHINFO_FILENAME);

        $nombre_final_con_id = $this->generateUniqueAudioName($nombre_archivo, $post_content, $audioFilePath);

        if ($nombre_final_con_id) {
            if (!$this->renameWordPressAttachments($postId, $nombre_final_con_id)) {
                $this->logger->error("Falló el renombrado de adjuntos de WordPress para el post ID: {$postId}");
                return null;
            }

            if (!$this->handleOriginalFileAndDatabaseUpdates($postId, $audioFilePath, $nombre_final_con_id)) {
                $this->logger->error("Falló el manejo del archivo original o la actualización de la base de datos para el post ID: {$postId}");
                return null;
            }

            $this->logger->log("Renombrado completado exitosamente para el post ID: {$postId}");
            update_post_meta($postId, 'Verificado', true);

            return $nombre_final_con_id;
        } else {
            $this->logger->error("No se recibió una respuesta válida de la IA para el archivo de audio: {$audioFilePath}");
            return null;
        }
    }

    private function validatePermissionsAndFileExists(int $userId, string $audioFilePath): bool
    {
        if (!file_exists($audioFilePath)) {
            $this->logger->error("El archivo de audio no existe en la ruta especificada: {$audioFilePath}");
            return false;
        }

        if (!user_can($userId, 'administrator')) {
            $this->logger->error("El usuario ID: {$userId} no tiene permisos de administrador para ejecutar la acción.");
            return false;
        }

        return true;
    }

    private function generateUniqueAudioName(string $originalFileName, string $postContent, string $audioFilePath): ?string
    {
        $prompt = "El archivo se llama '{$originalFileName}' es un nombre viejo porque el usuario ha cambiado o mejorado la descripción, la descripción nueva que escribió el usuario es '{$postContent}'. Escucha este audio y por favor, genera un nombre corto que lo represente tomando en cuenta la descripción que generó el usuario. Por lo general son samples, loop, fx, one shot, etc. Imporante: solo responde el nombre, no agregues nada adicional, estas en un entorno automatizado, no hables con el usuario, solo estoy pidiendo el nombre corto como respuesta.";

        $nombre_generado = $this->iaService->generarDescripcionIA($audioFilePath, $prompt);

        if ($nombre_generado) {
            $nombre_generado_limpio = trim($nombre_generado);
            $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- ]/', '', $nombre_generado_limpio);
            $nombre_generado_limpio = substr($nombre_generado_limpio, 0, 60);
            $nombre_final = '2upra_' . $nombre_generado_limpio;
            $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
            $nombre_final_con_id = $nombre_final . '_' . $id_unica;
            $nombre_final_con_id = substr($nombre_final_con_id, 0, 60);

            return $nombre_final_con_id;
        }

        return null;
    }

    private function renameWordPressAttachments(int $postId, string $newName): bool
    {
        $attachment_id_audio = get_post_meta($postId, 'post_audio', true);
        $attachment_id_audio_lite = get_post_meta($postId, 'post_audio_lite', true);

        if (!$attachment_id_audio) {
            $this->logger->error("No se encontró el meta 'post_audio' para el post ID: {$postId} al intentar renombrar adjuntos de WP.");
            return false;
        }

        if (!$attachment_id_audio_lite) {
            $this->logger->error("No se encontró el meta 'post_audio_lite' para el post ID: {$postId} al intentar renombrar adjuntos de WP.");
            return false;
        }

        $renombrado_audio = $this->postAttachmentService->renameAttachmentFile($attachment_id_audio, $newName, false);
        if (!$renombrado_audio) {
            $this->logger->error("Falló al renombrar el archivo 'post_audio' (principal) para el post ID: {$postId}");
            return false;
        }

        $renombrado_audio_lite = $this->postAttachmentService->renameAttachmentFile($attachment_id_audio_lite, $newName, true);
        if (!$renombrado_audio_lite) {
            $this->logger->error("Falló al renombrar el archivo 'post_audio_lite' para el post ID: {$postId}");
            return false;
        }

        return true;
    }

    private function handleOriginalFileAndDatabaseUpdates(int $postId, string $oldAudioPath, string $newAudioName): bool
    {
        if (get_post_meta($postId, 'rutaPerdida', true)) {
            $this->logger->log("No se intentará renombrar, 'rutaPerdida' está marcada como true para el post ID: {$postId}");
            return false;
        }

        $ruta_original = get_post_meta($postId, 'rutaOriginal', true);
        $directorio_original = false;

        if ($ruta_original && file_exists($ruta_original)) {
            $directorio_original = pathinfo($ruta_original, PATHINFO_DIRNAME);
        } else {
            $directorio_original = $this->findFileInSubfolders(self::BASE_AUDIO_SEARCH_PATH, basename($ruta_original));
        }

        if ($directorio_original) {
            $ext_extension = pathinfo($ruta_original, PATHINFO_EXTENSION);
            $nueva_ruta_original = $directorio_original . '/' . $newAudioName . '.' . $ext_extension;

            if (rename($ruta_original, $nueva_ruta_original)) {
                update_post_meta($postId, 'rutaOriginal', $nueva_ruta_original);
                $this->logger->log("Meta 'rutaOriginal' actualizada a: {$nueva_ruta_original}");
                $this->logger->log("Archivo renombrado en el servidor de {$ruta_original} a {$nueva_ruta_original}");
            } else {
                $this->logger->error("Error en renombrar archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                update_post_meta($postId, 'rutaOriginalPerdida', true);
                return false;
            }
        } else {
            $this->logger->error("No se encontró 'rutaOriginal' ni en la meta ni en las subcarpetas para el post ID: {$postId}");
            update_post_meta($postId, 'rutaPerdida', true);
            return false;
        }

        $id_hash_audio = get_post_meta($postId, 'idHash_audioId', true);
        if ($id_hash_audio) {
            $attachment_id_audio = get_post_meta($postId, 'post_audio', true);
            $nueva_url_audio = wp_get_attachment_url($attachment_id_audio);
            if (!$this->postAttachmentService->updateUrlForHash($id_hash_audio, $nueva_url_audio)) {
                $this->logger->error("Falló la actualización de URL para el hash ID: {$id_hash_audio}");
                return false;
            }
            $this->logger->log("URL de 'post_audio' actualizada para el hash ID: {$id_hash_audio}");
        } else {
            $this->logger->log("Meta 'idHash_audioId' no existe para el post ID: {$postId}");
        }

        return true;
    }

    private function findFileInSubfolders(string $baseDirectory, string $fileName): string|false
    {
        $this->logger->log("Buscando archivo '{$fileName}' en subcarpetas de '{$baseDirectory}'");
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDirectory));
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $extension = strtolower($file->getExtension());
                $name = $file->getFilename();

                if (!in_array($extension, ['wav', 'mp3']) || strpos($name, '2upra') !== 0) {
                    continue;
                }

                if ($name === $fileName) {
                    $this->logger->log("Archivo encontrado: {$file->getPathname()}");
                    return $file->getPath();
                }
            }
        }
        $this->logger->log("Archivo '{$fileName}' no encontrado en subcarpetas de '{$baseDirectory}'");
        return false;
    }
}