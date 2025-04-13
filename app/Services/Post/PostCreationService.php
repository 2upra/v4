<?php

// Refactor(Org): Función crearPost() movida desde PostService.php

#Crea un post
function crearPost($tipoPost = 'social_post', $estadoPost = 'publish')
{
    $contenido = isset($_POST['textoNormal']) ? sanitize_textarea_field($_POST['textoNormal']) : '';
    $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';

    if (empty($contenido)) {
        error_log('Error en crearPost: El contenido no puede estar vacio.');
        return new WP_Error('empty_content', 'El contenido no puede estar vacio.');
    }

    $titulo = wp_trim_words($contenido, 15, '...');
    $autor = get_current_user_id();

    $idPost = wp_insert_post([
        'post_title'   => $titulo,
        'post_content' => $contenido,
        'post_status'  => $estadoPost,
        'post_author'  => $autor,
        'post_type'    => $tipoPost,
    ]);

    if (is_wp_error($idPost)) {
        $mensajeError = str_replace("\n", " | ", $idPost->get_error_message());
        error_log('Error en crearPost: Error al insertar el post. Detalles: ' . $mensajeError);
        return $idPost;
    }

    return $idPost;
}

// Refactor(Org): Función crearAutPost() movida desde app/Auto/automaticPost.php
function crearAutPost($rutaOriginal = null, $rutaWpLite = null, $file_id = null, $autor_id = null, $post_original = null)
{

    if ($autor_id === null) {
        $autor_id = 44;
    }

    $nombre_archivo = null;
    $carpeta = null;
    $carpeta_abuela = null;
    $extension_original = null;
    $nuevaRutaOriginal = null;

    // Validar y procesar $rutaOriginal si existe
    if (!empty($rutaOriginal)) {
        if (!file_exists($rutaOriginal)) {
            // Puedes decidir si continuar o retornar aquí, dependiendo de si rutaOriginal es obligatoria
        } else {
            $nombre_archivo = pathinfo($rutaOriginal, PATHINFO_FILENAME);
            $carpeta = basename(dirname($rutaOriginal));
            $carpeta_abuela = basename(dirname(dirname($rutaOriginal)));
            $extension_original = pathinfo($rutaOriginal, PATHINFO_EXTENSION);
        }
    }

    // Validar $rutaWpLite
    if (empty($rutaWpLite)) {
        return;
    }

    if (!file_exists($rutaWpLite)) {
        return;
    }

    //Automatic audio solo necesita la ruta lite para funcionar
    $datosAlgoritmo = automaticAudio($rutaWpLite, $nombre_archivo, $carpeta, $carpeta_abuela);

    if (!$datosAlgoritmo) {
        eliminarHash($file_id);
        return;
    }

    $descripcion_corta_es = $datosAlgoritmo['descripcion_corta']['en'] ?? '';
    $nombre_generado = $datosAlgoritmo['nombre_corto']['en'] ?? '';

    if (is_array($nombre_generado)) {
        $nombre_generado = $nombre_generado[0] ?? '';
    }

    if ($nombre_generado) {
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- áéíóúÁÉÍÓÚñÑ]/u', '', trim($nombre_generado));
        $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $nombre_final = substr($nombre_generado_limpio . '_' . $id_unica . '_2upra', 0, 60);
    } else {
        eliminarHash($file_id);
        return;
    }

    // Manejo de renombrado de archivos, solo si $rutaOriginal existe
    if (!empty($rutaOriginal) && file_exists($rutaOriginal)) {
        $nuevaRutaOriginal = dirname($rutaOriginal) . '/' . $nombre_final . '.' . $extension_original;
        if (file_exists($nuevaRutaOriginal) && !unlink($nuevaRutaOriginal)) {
            eliminarHash($file_id);
            return;
        }
        if (!rename($rutaOriginal, $nuevaRutaOriginal)) {
            eliminarHash($file_id);
            return;
        }
    }

    // Manejo de renombrado de rutaWpLite
    $extension_lite = pathinfo($rutaWpLite, PATHINFO_EXTENSION);
    $nuevo_nombre_lite = dirname($rutaWpLite) . '/' . $nombre_final . '_lite.' . $extension_lite;
    if (file_exists($nuevo_nombre_lite) && !unlink($nuevo_nombre_lite)) {
        eliminarHash($file_id);
        return;
    }
    if (!rename($rutaWpLite, $nuevo_nombre_lite)) {
        eliminarHash($file_id);
        return;
    }

    if (is_array($descripcion_corta_es)) {
        $descripcion_corta_es = $descripcion_corta_es[0] ?? '';
    }

    $titulo = mb_substr($descripcion_corta_es, 0, 60);
    $post_data = [
        'post_title'    => $titulo,
        'post_content'  => $descripcion_corta_es,
        'post_status'   => 'publish',
        'post_author'   => $autor_id,
        'post_type'     => 'social_post',
    ];

    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return;
    }

    // Solo actualiza rutaOriginal si existe
    if (!empty($nuevaRutaOriginal)) {
        update_post_meta($post_id, 'rutaOriginal', $nuevaRutaOriginal);
    }

    update_post_meta($post_id, 'rutaLiteOriginal', $nuevo_nombre_lite);
    update_post_meta($post_id, 'postAut', true);

    // Adjuntar archivo original solo si $rutaOriginal existe
    $audio_original_id = null; // Inicializar para evitar errores si no se adjunta
    if (!empty($nuevaRutaOriginal)) {
        $audio_original_id = adjuntarArchivoAut($nuevaRutaOriginal, $post_id, $file_id);
        if (is_wp_error($audio_original_id)) {
            wp_delete_post($post_id, true);
            eliminarHash($file_id);
            return $audio_original_id;
        }
        if (file_exists($nuevaRutaOriginal)) {
            unlink($nuevaRutaOriginal);
        }
    }

    $audio_lite_id = adjuntarArchivoAut($nuevo_nombre_lite, $post_id);
    if (is_wp_error($audio_lite_id)) {
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return $audio_lite_id;
    }

    // Metadatos del post
    $existing_meta = get_post_meta($post_id);

    // Solo actualiza post_audio si se adjuntó un archivo original
    if (!empty($audio_original_id) && !isset($existing_meta['post_audio'])) {
        update_post_meta($post_id, 'post_audio', $audio_original_id);
    }

    if (!isset($existing_meta['post_audio_lite'])) {
        update_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
    }

    if ($autor_id === 44) {
        update_post_meta($post_id, 'paraDescarga', true);
    }

    // Solo actualiza estos metadatos si $rutaOriginal existía
    if (!empty($rutaOriginal)) {
        if (!isset($existing_meta['nombreOriginal'])) {
            update_post_meta($post_id, 'nombreOriginal', $nombre_archivo);
        }
        if (!isset($existing_meta['carpetaOriginal'])) {
            update_post_meta($post_id, 'carpetaOriginal', $carpeta);
        }
        if (!isset($existing_meta['carpetaAbuelaOriginal'])) {
            update_post_meta($post_id, 'carpetaAbuelaOriginal', $carpeta_abuela);
        }
    }

    if (!isset($existing_meta['audio_bpm'])) {
        update_post_meta($post_id, 'audio_bpm', $datosAlgoritmo['bpm'] ?? null);
    }
    if (!isset($existing_meta['audio_key'])) {
        update_post_meta($post_id, 'audio_key', $datosAlgoritmo['key'] ?? null);
    }
    if (!isset($existing_meta['audio_scale'])) {
        update_post_meta($post_id, 'audio_scale', $datosAlgoritmo['scale'] ?? null);
    }
    if (!isset($existing_meta['datosAlgoritmo'])) {
        update_post_meta($post_id, 'datosAlgoritmo', json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE));
    }

    return $post_id;
}
