<?php

function crearPost($tipoPost = 'social_post', $estadoPost = 'publish')
{
    // Saneamiento de datos
    $contenido = sanitize_textarea_field($_POST['textoNormal'] ?? '');
    $tags = sanitize_text_field($_POST['tags'] ?? '');

    // Validación del contenido
    if (empty($contenido)) {
        guardarLog('empty_content: El contenido no puede estar vacío.');
        return new WP_Error('empty_content', 'El contenido no puede estar vacío.');
    }

    // Convertir los tags en hashtags
    if (!empty($tags)) {
        $tagsArray = explode(',', $tags);
        $hashtags = array_map(function($tag) {
            return '#' . trim($tag);
        }, $tagsArray);
        $contenido .= ' ' . implode(' ', $hashtags);
    }

    // Generar el título
    $titulo = wp_trim_words($contenido, 15, '...');
    $autor = get_current_user_id();

    // Insertar el post
    $postId = wp_insert_post([
        'post_title'   => $titulo,
        'post_content' => $contenido,
        'post_status'  => $estadoPost,
        'post_author'  => $autor,
        'post_type'    => $tipoPost,
    ]);

    if (is_wp_error($postId)) {
        return $postId;
    }

    return $postId;
}

function datosParaAlgoritmo($postId) {

    // Obtener el texto normal desde la solicitud POST
    $textoNormal = isset($_POST['textoNormal']) ? trim($_POST['textoNormal']) : '';
    
    // Procesar los tags, eliminando espacios y creando un array
    $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];

    $autorId = get_post_field('post_author', $postId);

    // Preparar los datos para el algoritmo 
    $datosAlgoritmo = [
        'tags' => $tags,
        'texto' => $textoNormal,
        'autor' => $autorId,
    ];

    // Guardar log de los datos compilados
    guardarLog("Datos para algoritmo compilados para postId: {$postId}");

    // Codificar los datos en JSON y actualizar metadatos
    if ($datosAlgoritmoJson = json_encode($datosAlgoritmo)) {
        update_post_meta($postId, 'datosAlgoritmo', $datosAlgoritmoJson);
        guardarLog("Metadatos de datosAlgoritmo actualizados para postId: {$postId}");
    } else {
        guardarLog("Error al codificar datosAlgoritmo a JSON para postId: {$postId}");
    }
}

function actualizarMetaDatos($postId)
{
    $meta_fields = [
        'paraColab'    => 'colab',
        'esExclusivo'  => 'exclusivo',
        'paraDescarga' => 'descarga'
    ];

    foreach ($meta_fields as $meta_key => $post_key) {
        if (isset($_POST[$post_key])) {
            $value = $_POST[$post_key] == '1' ? 1 : 0;
        } else {
            $value = 0; 
        }
        update_post_meta($postId, $meta_key, $value);
    }
}

function confirmarArchivos($postId)
{
    foreach (['archivoId', 'audioId', 'imagenId'] as $campo) {
        if (!empty($_POST[$campo])) {
            $file_id = intval($_POST[$campo]);
            if ($file_id > 0) {
                confirmarArchivo($file_id);
            }
        }
    }
}


function asignarTags($postId)
{
    if (!empty($_POST['Tags'])) {
        $tags = sanitize_text_field($_POST['Tags']);
        $tags_array = explode(',', $tags);
        wp_set_post_tags($postId, $tags_array, false);
    }
}

function procesarURLs($postId)
{
    $procesarURLs = [
        'imagenUrl'  => 'procesarArchivo',
        'audioUrl'   => ['procesarArchivo', true],
        'archivoUrl' => 'procesarArchivo',
    ];

    foreach ($procesarURLs as $field => $callback) {
        if (!empty($_POST[$field])) {
            $url = esc_url_raw($_POST[$field]);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                is_array($callback)
                    ? $callback[0]($postId, $field, $callback[1])
                    : $callback($postId, $field);
            }
        }
    }
}

function procesarArchivo($postId, $campo, $renombrar = false)
{
    $url = esc_url_raw($_POST[$campo]);
    $archivoId = obtenerArchivoId($url, $postId);

    if ($archivoId && !is_wp_error($archivoId)) {
        actualizarMetaConArchivo($postId, $campo, $archivoId);

        if ($renombrar) {
            renombrarArchivoAdjunto($postId, $archivoId);
        }

        return true;
    }

    return false;
}

function obtenerArchivoId($url, $postId)
{
    $archivoId = attachment_url_to_postid($url);
    if (!$archivoId) {
        $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $url);
        if (file_exists($file_path)) {
            $archivoId = media_handle_sideload([
                'name'     => basename($file_path),
                'tmp_name' => $file_path
            ], $postId);
        }
    }

    return $archivoId;
}

function actualizarMetaConArchivo($postId, $campo, $archivoId)
{
    $meta_mapping = [
        'imagenUrl' => 'imagenID',
        'audioUrl' => 'post_audio',
        'archivoUrl' => 'archivoID'
    ];

    $meta_key = isset($meta_mapping[$campo]) ? $meta_mapping[$campo] : $campo;
    update_post_meta($postId, $meta_key, $archivoId);

    if ($campo === 'imagenUrl') {
        set_post_thumbnail($postId, $archivoId);
    }
}

function renombrarArchivoAdjunto($postId, $archivoId)
{
    $post = get_post($postId);
    $author = get_userdata($post->post_author);
    $file_path = get_attached_file($archivoId);
    $info = pathinfo($file_path);

    $new_filename = sprintf(
        '2upra_%s_%s.%s',
        sanitize_file_name(mb_substr($author->user_login, 0, 20)),
        sanitize_file_name(mb_substr($post->post_content, 0, 40)),
        $info['extension']
    );

    $new_file_path = $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename;
    if (rename($file_path, $new_file_path)) {
        update_attached_file($archivoId, $new_file_path);
        update_post_meta($postId, 'sample', true);
        procesarAudioLigero($postId, $archivoId, 1);
    } else {
        // Manejar error en el renombrado
        guardarLog('rename_failed:No se pudo renombrar el archivo adjunto.');
        return new WP_Error('rename_failed', 'No se pudo renombrar el archivo adjunto.');
    }
}