<?php

namespace App
\Services\Post;

use App
\Utils\Logger;

class PostAudioRenamingService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function renameAudio(int $postId, string $audioFilePath): ?string
    {
        $userId = get_current_user_id();

        if (!$this->validatePermissionsAndFileExists($userId, $audioFilePath)) {
            return null;
        }

        // --- Start of original rehacerNombreAudio logic (moved here) ---
        // Obtener el contenido del post
        $post_content = get_post_field('post_content', $postId);
        if (!$post_content) {
            $this->logger->error("No se pudo obtener el contenido del post ID: {$postId}");
            return null; // Added return null for consistency
        }
        //error_log("Contenido del post obtenido para el post ID: {$postId}"); // Replaced with logger if needed, but commented out in original

        // Obtener el nombre del archivo a partir de la ruta
        $nombre_archivo = pathinfo($audioFilePath, PATHINFO_FILENAME);

        // Crear el prompt para la IA con el nombre del archivo incluido
        $prompt = "El archivo se llama '{$nombre_archivo}' es un nombre viejo porque el usuario ha cambiado o mejorado la descripción, la descripción nueva que escribió el usuario es '{$post_content}'. Escucha este audio y por favor, genera un nombre corto que lo represente tomando en cuenta la descripción que generó el usuario. Por lo general son samples, loop, fx, one shot, etc. Imporante: solo responde el nombre, no agregues nada adicional, estas en un entorno automatizado, no hables con el usuario, solo estoy pidiendo el nombre corto como respuesta.";

        // Generar el nombre usando la IA
        // This function (generarDescripcionIA) is global. It should ideally be injected.
        // For now, keeping it as is, but noting it for future tasks.
        $nombre_generado = generarDescripcionIA($audioFilePath, $prompt);

        // Verificar si se obtuvo una respuesta válida
        if ($nombre_generado) {
            // Limpiar el nombre generado
            $nombre_generado_limpio = trim($nombre_generado);
            $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- ]/', '', $nombre_generado_limpio);
            $nombre_generado_limpio = substr($nombre_generado_limpio, 0, 60);
            $nombre_final = '2upra_' . $nombre_generado_limpio;
            $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
            $nombre_final_con_id = $nombre_final . '_' . $id_unica;
            $nombre_final_con_id = substr($nombre_final_con_id, 0, 60);

            //error_log("Nombre generado: {$nombre_final_con_id}");

            // Obtener los IDs de los adjuntos desde los metadatos del post
            $attachment_id_audio = get_post_meta($postId, 'post_audio', true);
            $attachment_id_audio_lite = get_post_meta($postId, 'post_audio_lite', true);

            // Verificar que los IDs de adjunto existan
            if (!$attachment_id_audio) {
                $this->logger->error("No se encontró el meta 'post_audio' para el post ID: {$postId}");
                return null;
            }

            if (!$attachment_id_audio_lite) {
                $this->logger->error("No se encontró el meta 'post_audio_lite' para el post ID: {$postId}");
                return null;
            }

            // Renombrar los archivos adjuntos
            // This function (renombrar_archivo_adjunto) is global. It should ideally be injected.
            $renombrado_audio = renombrar_archivo_adjunto($attachment_id_audio, $nombre_final_con_id, false);
            if (!$renombrado_audio) {
                $this->logger->error("Falló al renombrar el archivo 'post_audio' para el post ID: {$postId}");
                return null;
            }

            $renombrado_audio_lite = renombrar_archivo_adjunto($attachment_id_audio_lite, $nombre_final_con_id, true);
            if (!$renombrado_audio_lite) {
                $this->logger->error("Falló al renombrar el archivo 'post_audio_lite' para el post ID: {$postId}");
                return null;
            }

            if (get_post_meta($postId, 'rutaPerdida', true)) {
                $this->logger->log("No se intentará renombrar, 'rutaPerdida' está marcada como true para el post ID: {$postId}");
                return null;
            }

            // Actualizar la meta 'rutaOriginal' o buscar en subcarpetas si no existe la ruta
            $ruta_original = get_post_meta($postId, 'rutaOriginal', true);
            if ($ruta_original && file_exists($ruta_original)) {
                $directorio_original = pathinfo($ruta_original, PATHINFO_DIRNAME);
            } else {
                // This function (buscarArchivoEnSubcarpetas) is global. It should ideally be injected.
                $directorio_original = buscarArchivoEnSubcarpetas("/home/asley01/MEGA/Waw/X", basename($ruta_original));
            }

            if ($directorio_original) {
                $ext_extension = pathinfo($ruta_original, PATHINFO_EXTENSION);
                $nueva_ruta_original = $directorio_original . '/' . $nombre_final_con_id . '.' . $ext_extension;

                if (rename($ruta_original, $nueva_ruta_original)) {
                    update_post_meta($postId, 'rutaOriginal', $nueva_ruta_original);
                    $this->logger->log("Meta 'rutaOriginal' actualizada a: {$nueva_ruta_original}");
                    $this->logger->log("Archivo renombrado en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                } else {
                    $this->logger->error("Error en renombrar archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                    update_post_meta($postId, 'rutaOriginalPerdida', true);
                }
            } else {
                $this->logger->error("No se encontró 'rutaOriginal' ni en la meta ni en las subcarpetas para el post ID: {$postId}");
                update_post_meta($postId, 'rutaPerdida', true);
            }


            // Actualizar la URL en base de datos si tiene idHash_audioId
            $id_hash_audio = get_post_meta($postId, 'idHash_audioId', true);
            if ($id_hash_audio) {
                $nueva_url_audio = wp_get_attachment_url($attachment_id_audio);
                // This function (actualizarUrlArchivo) is global. It should ideally be injected.
                actualizarUrlArchivo($id_hash_audio, $nueva_url_audio);
                $this->logger->log("URL de 'post_audio' actualizada para el hash ID: {$id_hash_audio}");
            } else {
                $this->logger->log("Meta 'idHash_audioId' no existe para el post ID: {$postId}");
            }

            $this->logger->log("Renombrado completado exitosamente para el post ID: {$postId}");
            update_post_meta($postId, 'Verificado', true);

            return $nombre_final_con_id;
        } else {
            $this->logger->error("No se recibió una respuesta válida de la IA para el archivo de audio: {$audioFilePath}");
            return null;
        }
        // --- End of original rehacerNombreAudio logic ---
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
}
