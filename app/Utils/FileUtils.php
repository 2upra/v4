<?php

function obtenerImagenAleatoria($directory)
{
    static $cache = array();

    if (isset($cache[$directory])) {
        return $cache[$directory][array_rand($cache[$directory])];
    }

    if (!is_dir($directory)) {
        return false;
    }

    $images = glob(rtrim($directory, '/') . '/*.{jpg,jpeg,png,gif,jfif}', GLOB_BRACE);

    if (!$images) {
        return false;
    }

    $cache[$directory] = $images;
    return $images[array_rand($images)];
}

/**
 * Sube una imagen desde una URL a la biblioteca de medios de WordPress.
 *
 * @param string $image_url La URL de la imagen a subir.
 * @param int $post_id El ID del post al que se adjuntará la imagen (opcional, por defecto 0).
 * @return int|false El ID del adjunto si tiene éxito, false en caso contrario.
 */
function subirImagenDesdeURL($image_url, $post_id = 0) {
    // Asegurarse de que las funciones necesarias de WordPress estén cargadas.
    if (!function_exists('media_sideload_image')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    // Usar la función media_sideload_image para subir la imagen al servidor.
    // El último parámetro 'id' indica que queremos que devuelva el ID del adjunto.
    $media_id = media_sideload_image($image_url, $post_id, null, 'id');

    // Verificar si hubo un error durante la subida.
    if (is_wp_error($media_id)) {
        // Opcional: Registrar el error para depuración.
        // error_log('Error al subir imagen desde URL: ' . $media_id->get_error_message());
        return false;
    }

    return $media_id;
}

function adjuntarArchivo($newPostId, $fileUrl) {
    global $wpdb;

    // Obtener ID del adjunto si ya existe
    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE guid = %s",
        $fileUrl
    ));

    if (!$attachment_id) {
        $uploads_dir = wp_upload_dir();
        $file_path = str_replace($uploads_dir['baseurl'], $uploads_dir['basedir'], $fileUrl);

        if (file_exists($file_path)) {
            $mime_type = mime_content_type($file_path);
            $data = [
                'guid'           => $fileUrl,
                'post_mime_type' => $mime_type,
                'post_title'     => wp_basename($file_path),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $attachment_id = wp_insert_attachment($data, $file_path, $newPostId);

            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $attach_data);
            }
        } else {
            guardarLog("Archivo físico no encontrado para adjuntar.");
            return false;
        }
    }

    if ($attachment_id) {
        // Recuperar metadatos existentes
        $fileAdjIds = get_post_meta($newPostId, 'fileAdjIds', true) ?: [];
        $fileAdjUrls = get_post_meta($newPostId, 'fileAdjUrls', true) ?: [];

        // Actualizar las listas
        $fileAdjIds[] = $attachment_id;
        $fileAdjUrls[] = $fileUrl;

        // Guardar los datos actualizados
        update_post_meta($newPostId, 'fileAdjIds', array_unique($fileAdjIds));
        update_post_meta($newPostId, 'fileAdjUrls', array_unique($fileAdjUrls));

        // Obtener MIME type y procesar según el tipo
        $mime_type = get_post_mime_type($attachment_id);

        if (strpos($mime_type, 'audio') !== false) {
            // Actualizar lista de audios
            $audioAdjIds = get_post_meta($newPostId, 'audioAdjIds', true) ?: [];
            $audioAdjIds[] = $attachment_id;
            update_post_meta($newPostId, 'audioAdjIds', array_unique($audioAdjIds));

            $index = 1; // O ajusta el índice según sea necesario
            procesarAudioLigero($newPostId, $attachment_id, $index);
        } elseif (strpos($mime_type, 'image') !== false) {
            // Establecer la primera imagen como portada
            $imgAdjIds = get_post_meta($newPostId, 'imgAdjIds', true) ?: [];
            if (empty($imgAdjIds)) {
                set_post_thumbnail($newPostId, $attachment_id);
            }
            $imgAdjIds[] = $attachment_id;
            update_post_meta($newPostId, 'imgAdjIds', array_unique($imgAdjIds));
        } else {
            guardarLog("Archivo adjuntado (tipo desconocido) con ID: $attachment_id");
        }

        guardarLog("Archivo adjuntado con ID: $attachment_id");
        return true;
    }

    return false;
}
