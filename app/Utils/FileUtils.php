<?php

// Refactor(Org): Función obtenerImagenAleatoria movida a app/Utils/ImageUtils.php
// Refactor(Org): Función subirImagenDesdeURL movida a app/Utils/ImageUtils.php

// Refactor(Org): Función adjuntarArchivo() movida a app/Services/Post/PostAttachmentService.php

// Refactor(Org): Moved function nombreUnicoFile from HashUtils.php
function nombreUnicoFile($dir, $name, $ext)
{
    return basename($name, $ext) . $ext;
}

// Refactor(Org): Función subirImagenALibreria movida a app/Utils/ImageUtils.php

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
