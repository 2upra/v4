<?php

// Refactor(Org): Función obtenerImagenAleatoria movida a app/Utils/ImageUtils.php

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

// Refactor(Org): Moved function nombreUnicoFile from HashUtils.php
function nombreUnicoFile($dir, $name, $ext)
{
    return basename($name, $ext) . $ext;
}

// Refactor(Org): Función movida desde app/Content/Posts/View/componentPost.php
function subirImagenALibreria($file_path, $postId)
{
    if (!file_exists($file_path)) {
        return false;
    }
    $file_contents = file_get_contents($file_path);
    if ($file_contents === false) {
        return false;
    }
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($file_ext === 'jfif') {
        $file_ext = 'jpeg';
        $new_file_name = pathinfo($file_path, PATHINFO_FILENAME) . '.jpeg';
        $upload_file = wp_upload_bits($new_file_name, null, $file_contents);
    } else {
        $upload_file = wp_upload_bits(basename($file_path), null, $file_contents);
    }

    if ($upload_file['error']) {
        return false;
    }
    $filetype = wp_check_filetype($upload_file['file'], null);
    if (!$filetype['type']) {
        return false;
    }
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($upload_file['file'], PATHINFO_BASENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_parent'    => $postId,
    );
    $attach_id = wp_insert_attachment($attachment, $upload_file['file'], $postId);
    if (!is_wp_error($attach_id)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
    return false;
}

// Refactor(Org): Función renombrar_archivo_adjunto movida desde app/Services/Post/PostAttachmentService.php
#PASO 5.2
function renombrarArchivoAdjunto($postId, $archivoId, $indice) // Recibe índice directamente
{
    // Validar que $archivoId es un ID de adjunto válido
    if (!$archivoId || get_post_type($archivoId) !== 'attachment') {
        error_log("Error en renombrarArchivoAdjunto: ID de archivo inválido {$archivoId} para postId {$postId}.");
        return false;
    }

    // Obtener post y autor
    $post = get_post($postId);
    if (!$post) {
        error_log("Error en renombrarArchivoAdjunto: No se pudo obtener el post para postId: {$postId}.");
        return false;
    }
    $author = get_userdata($post->post_author);
    if (!$author) {
        error_log("Error en renombrarArchivoAdjunto: No se pudo obtener el autor para postId: {$postId}.");
        return false;
    }

    // Obtener ruta del archivo
    $file_path = get_attached_file($archivoId);
    if (!$file_path || !file_exists($file_path)) {
        error_log("Error en renombrarArchivoAdjunto: El archivo adjunto no existe para archivoId: {$archivoId}. Ruta esperada: {$file_path}");
        return false;
    }

    $info = pathinfo($file_path);
    $random_id = wp_rand(10000, 99999); // Usar wp_rand para mejor aleatoriedad en WP
    $autor_login_sanitized = sanitize_file_name(mb_substr($author->user_login, 0, 20));
    // Usar el título del post en lugar del contenido para el nombre, es más predecible y corto
    $post_title_sanitized = sanitize_file_name(wp_trim_words($post->post_title, 5, '')); // 5 palabras del título
    $post_title_sanitized = mb_substr($post_title_sanitized, 0, 40); // Limitar longitud

    // Construir nuevo nombre de archivo
    $new_filename = sprintf(
        '2upra_%s_%s_%d_%d.%s',
        $autor_login_sanitized,
        $post_title_sanitized ?: "post{$postId}", // Fallback si el título está vacío
        $indice, // Incluir índice en el nombre
        $random_id,
        strtolower($info['extension']) // Usar extensión en minúsculas
    );
    $new_file_path = $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename;

    // Intentar renombrar
    if (rename($file_path, $new_file_path)) {
        // Actualizar la ruta del archivo en la base de datos de WordPress
        $update_path_result = update_attached_file($archivoId, $new_file_path);
        if (!$update_path_result) {
             error_log("Error en renombrarArchivoAdjunto: El archivo se renombró en el sistema ({$new_file_path}) pero falló al actualizar la ruta en WP para archivoId: {$archivoId}.");
             // Podría intentar revertir el rename aquí, pero es complejo.
             return false;
        }

        // Obtener la nueva URL pública (esto debería funcionar después de update_attached_file)
        $public_url = wp_get_attachment_url($archivoId);
        if (!$public_url) {
            error_log("Error en renombrarArchivoAdjunto: No se pudo obtener la nueva URL pública para archivoId: {$archivoId} después de renombrar.");
            // El archivo se renombró, pero la URL podría estar mal.
        }

        // Actualizar URL en algún otro lugar si es necesario (Función actualizarUrlArchivo)
        // ¿De dónde viene idHash aquí? Se necesita obtenerlo correctamente.
        // Asumiendo que idHash se relaciona con el archivo original subido antes de confirmar.
        // Necesitamos una forma de vincular el $archivoId procesado con su 'idHash' original si es necesario.
        // Por ahora, comentaremos esta parte ya que 'idHash' no está definido aquí.
        /*
        $idHashCampo = "idHash_audioId{$indice}"; // Construir la meta key esperada
        $idHash = get_post_meta($postId, $idHashCampo, true);
        if (!empty($idHash) && $public_url) {
            actualizarUrlArchivo($idHash, $public_url);
        } else {
             error_log("Advertencia en renombrarArchivoAdjunto: No se encontró idHash ({$idHashCampo}) o URL pública para actualizar URL externa. PostID: {$postId}, ArchivoID: {$archivoId}");
        }
        */

        // Actualizar metadatos adicionales
        update_post_meta($postId, 'sample', true); // ¿Qué significa 'sample'? Asegúrate que esto es correcto.
        procesarAudioLigero($postId, $archivoId, $indice); // Procesar versión ligera

        return true; // Éxito

    } else {
        error_log("Error en renombrarArchivoAdjunto: No se pudo renombrar el archivo adjunto de {$file_path} a {$new_file_path}. Verificar permisos.");
        return false;
    }
}
