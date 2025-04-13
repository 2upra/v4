<?php



function rehacerNombreAudio($post_id, $archivo_audio)
{
    // Verificar si el archivo de audio existe
    if (!file_exists($archivo_audio)) {
        //error_log("El archivo de audio no existe en la ruta especificada: {$archivo_audio}");
        return null;
    }

    $user_id = get_current_user_id();

    if (!user_can($user_id, 'administrator')) {
        return;
    }

    // Obtener el contenido del post
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        //error_log("No se pudo obtener el contenido del post ID: {$post_id}");
    }
    //error_log("Contenido del post obtenido para el post ID: {$post_id}");

    // Obtener el nombre del archivo a partir de la ruta
    $nombre_archivo = pathinfo($archivo_audio, PATHINFO_FILENAME);

    // Crear el prompt para la IA con el nombre del archivo incluido
    $prompt = "El archivo se llama '{$nombre_archivo}' es un nombre viejo porque el usuario ha cambiado o mejorado la descripción, la descripción nueva que escribió el usuario es '{$post_content}'. Escucha este audio y por favor, genera un nombre corto que lo represente tomando en cuenta la descripción que generó el usuario. Por lo general son samples, loop, fx, one shot, etc. Imporante: solo responde el nombre, no agregues nada adicional, estas en un entorno automatizado, no hables con el usuario, solo estoy pidiendo el nombre corto como respuesta.";

    // Generar el nombre usando la IA
    $nombre_generado = generarDescripcionIA($archivo_audio, $prompt);

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
        $attachment_id_audio = get_post_meta($post_id, 'post_audio', true);
        $attachment_id_audio_lite = get_post_meta($post_id, 'post_audio_lite', true);

        // Verificar que los IDs de adjunto existan
        if (!$attachment_id_audio) {
            //error_log("No se encontró el meta 'post_audio' para el post ID: {$post_id}");
            return null;
        }

        if (!$attachment_id_audio_lite) {
            //error_log("No se encontró el meta 'post_audio_lite' para el post ID: {$post_id}");
            return null;
        }

        // Renombrar los archivos adjuntos
        $renombrado_audio = renombrar_archivo_adjunto($attachment_id_audio, $nombre_final_con_id, false);
        if (!$renombrado_audio) {
            //error_log("Falló al renombrar el archivo 'post_audio' para el post ID: {$post_id}");
            return null;
        }

        $renombrado_audio_lite = renombrar_archivo_adjunto($attachment_id_audio_lite, $nombre_final_con_id, true);
        if (!$renombrado_audio_lite) {
            //error_log("Falló al renombrar el archivo 'post_audio_lite' para el post ID: {$post_id}");
            return null;
        }

        if (get_post_meta($post_id, 'rutaPerdida', true)) {
            //error_log("No se intentará renombrar, 'rutaPerdida' está marcada como true para el post ID: {$post_id}");
            return null;
        }

        // Actualizar la meta 'rutaOriginal' o buscar en subcarpetas si no existe la ruta
        $ruta_original = get_post_meta($post_id, 'rutaOriginal', true);
        if ($ruta_original && file_exists($ruta_original)) {
            $directorio_original = pathinfo($ruta_original, PATHINFO_DIRNAME);
        } else {
            // Asumiendo que buscarArchivoEnSubcarpetas ahora está disponible globalmente (o incluida)
            $directorio_original = buscarArchivoEnSubcarpetas("/home/asley01/MEGA/Waw/X", basename($ruta_original));
        }

        if ($directorio_original) {
            $ext_extension = pathinfo($ruta_original, PATHINFO_EXTENSION);
            $nueva_ruta_original = $directorio_original . '/' . $nombre_final_con_id . '.' . $ext_extension;

            if (rename($ruta_original, $nueva_ruta_original)) {
                update_post_meta($post_id, 'rutaOriginal', $nueva_ruta_original);
                //error_log("Meta 'rutaOriginal' actualizada a: {$nueva_ruta_original}");
                guardarLog("Archivo renombrado en el servidor de {$ruta_original} a {$nueva_ruta_original}");
            } else {
                guardarLog("Error en renombrar archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                //error_log("Error al renombrar el archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                update_post_meta($post_id, 'rutaOriginalPerdida', true);
            }
        } else {
            //error_log("No se encontró 'rutaOriginal' ni en la meta ni en las subcarpetas para el post ID: {$post_id}");
            update_post_meta($post_id, 'rutaPerdida', true);
        }


        // Actualizar la URL en base de datos si tiene idHash_audioId
        $id_hash_audio = get_post_meta($post_id, 'idHash_audioId', true);
        if ($id_hash_audio) {
            $nueva_url_audio = wp_get_attachment_url($attachment_id_audio);
            actualizarUrlArchivo($id_hash_audio, $nueva_url_audio);
            //error_log("URL de 'post_audio' actualizada para el hash ID: {$id_hash_audio}");
        } else {
            //error_log("Meta 'idHash_audioId' no existe para el post ID: {$post_id}");
        }

        //error_log("Renombrado completado exitosamente para el post ID: {$post_id}");
        update_post_meta($post_id, 'Verificado', true);

        return $nombre_final_con_id;
    } else {
        //error_log("No se recibió una respuesta válida de la IA para el archivo de audio: {$archivo_audio}");
        return null;
    }
}

// Refactor(Org): Función buscarArchivoEnSubcarpetas() movida a app/Utils/SystemUtils.php

// Refactor(Org): Función renombrar_archivo_adjunto() movida a app/Utils/SystemUtils.php
