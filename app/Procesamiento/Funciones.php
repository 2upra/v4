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
    
    // Solución al problema de codificación del texto
    $textoNormal = htmlspecialchars_decode($textoNormal, ENT_QUOTES);

    // Procesar los tags, eliminando espacios y creando un array
    $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];

    // Obtener la ID del autor
    $autorId = get_post_field('post_author', $postId);

    // Obtener el nombre de usuario y el nombre para mostrar
    $nombreUsuario = get_the_author_meta('user_login', $autorId);
    $nombreMostrar = get_the_author_meta('display_name', $autorId);

    // Preparar los datos para el algoritmo 
    $datosAlgoritmo = [
        'tags' => $tags,
        'texto' => $textoNormal,
        'autor' => [
            'id' => $autorId,
            'usuario' => $nombreUsuario,
            'nombre' => $nombreMostrar,
        ],
    ];

    // Guardar log de los datos compilados
    guardarLog("Datos para algoritmo compilados para postId: {$postId}");

    // Codificar los datos en JSON y actualizar metadatos
    if ($datosAlgoritmoJson = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE)) {
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
    guardarLog("Inicio de procesarArchivo para Post ID: $postId y Campo: $campo"); // Log inicial
    
    // Obtener la URL del archivo desde el campo proporcionado
    $url = esc_url_raw($_POST[$campo]);
    guardarLog("URL del archivo obtenida: $url");

    // Obtener el ID del archivo asociado con la URL y el Post ID
    $archivoId = obtenerArchivoId($url, $postId);
    guardarLog("Resultado de obtenerArchivoId: " . (is_wp_error($archivoId) ? "Error - " . $archivoId->get_error_message() : "Archivo ID: $archivoId"));

    // Verificar si se obtuvo correctamente el archivoId y no es un error
    if ($archivoId && !is_wp_error($archivoId)) {
        // Actualizar los metadatos con el archivo adjunto
        actualizarMetaConArchivo($postId, $campo, $archivoId);
        guardarLog("Metadatos actualizados para Post ID: $postId con Archivo ID: $archivoId en el campo: $campo");

        // Si se requiere renombrar el archivo, se ejecuta la función correspondiente
        if ($renombrar) {
            guardarLog("Renombrar archivo activado para Post ID: $postId y Archivo ID: $archivoId");
            renombrarArchivoAdjunto($postId, $archivoId);
        }

        guardarLog("Fin de procesarArchivo - operación exitosa para Post ID: $postId y Campo: $campo"); // Log final exitoso
        return true;
    } else {
        guardarLog("Error: No se pudo procesar el archivo para Post ID: $postId y Campo: $campo"); // Log de error
    }

    guardarLog("Fin de procesarArchivo - operación fallida para Post ID: $postId y Campo: $campo"); // Log final fallido
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
    $file_id = intval($_POST['audioId']);
    guardarLog("Inicio de renombrarArchivoAdjunto para Post ID: $postId y Archivo ID: $archivoId"); // Log inicial
    
    // Obtener información del post y del autor
    $post = get_post($postId);
    $author = get_userdata($post->post_author);
    
    if (!$post || !$author) {
        guardarLog("Error: No se pudo obtener el post o el autor.");
        return new WP_Error('post_or_author_not_found', 'No se pudo obtener el post o el autor.');
    }
    
    // Obtener la ruta del archivo adjunto
    $file_path = get_attached_file($archivoId);
    guardarLog("Ruta del archivo actual: $file_path");

    $info = pathinfo($file_path);
    
    // Generar el nuevo nombre de archivo
    $new_filename = sprintf(
        '2upra_%s_%s.%s',
        sanitize_file_name(mb_substr($author->user_login, 0, 20)),
        sanitize_file_name(mb_substr($post->post_content, 0, 40)),
        $info['extension']
    );
    guardarLog("Nuevo nombre de archivo generado: $new_filename");

    // Definir la nueva ruta del archivo
    $new_file_path = $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename;
    guardarLog("Nueva ruta de archivo: $new_file_path");

    // Intentar renombrar el archivo
    if (rename($file_path, $new_file_path)) {
        guardarLog("Archivo renombrado con éxito de $file_path a $new_file_path");

        // Convertir la ruta de archivo del servidor a la URL pública
        $upload_dir = wp_upload_dir(); // Obtener directorio de uploads
        $public_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path);
        guardarLog("URL pública generada: $public_url");

        // Actualizar la URL pública en la base de datos
        actualizarUrlArchivo($file_id, $public_url);

        // Actualizar la ruta del archivo adjunto en la base de datos
        update_attached_file($archivoId, $new_file_path);
        guardarLog("Ruta del archivo actualizada en la base de datos para Archivo ID: $archivoId");

        // Actualizar los metadatos del post
        update_post_meta($postId, 'sample', true);
        guardarLog("Metadato 'sample' actualizado para Post ID: $postId");

        // Procesar el audio ligero
        procesarAudioLigero($postId, $archivoId, 1);
        guardarLog("procesarAudioLigero ejecutado para Post ID: $postId y Archivo ID: $archivoId");
    } else {
        // Manejar error en el renombrado
        $error_message = "Error: No se pudo renombrar el archivo adjunto de $file_path a $new_file_path.";
        guardarLog($error_message);
        return new WP_Error('rename_failed', 'No se pudo renombrar el archivo adjunto.');
    }

    guardarLog("Fin de renombrarArchivoAdjunto para Post ID: $postId y Archivo ID: $archivoId"); // Log final
}



function procesarAudioLigero($post_id, $audio_id, $index)
{
    guardarLog("INICIO procesarAudioLigero para Post ID: $post_id y Audio ID: $audio_id");

    // Verificar si ya existe un archivo de audio ligero asociado a este audio_id
    $existing_lite_audio = get_posts(array(
        'post_type' => 'attachment',
        'meta_query' => array(
            array(
                'key' => "post_audio_lite", // Reemplazar con la clave meta adecuada si es diferente
                'value' => $audio_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    // Si ya existe un archivo ligero, asociarlo al nuevo post
    if ($existing_lite_audio) {
        $existing_lite_audio_id = $existing_lite_audio[0]->ID;
        guardarLog("Archivo ligero ya existente encontrado: ID $existing_lite_audio_id, asociando al nuevo post.");
        $meta_key = ($index == 1) ? "post_audio_lite" : "post_audio_lite_{$index}";
        update_post_meta($post_id, $meta_key, $existing_lite_audio_id);
        update_post_meta($post_id, 'AudioDuplicado', true);
        analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index); //TEMPORAL
        return;
    }

    // Obtener el archivo de audio original
    $audio_path = get_attached_file($audio_id);
    guardarLog("Ruta del archivo de audio original: {$audio_path}");

    // Obtener las partes del camino del archivo
    $path_parts = pathinfo($audio_path);
    $unique_id = uniqid('2upra_');
    $base_path = $path_parts['dirname'] . '/' . $unique_id;

    // Procesar archivo de audio ligero (128 kbps)
    $nuevo_archivo_path_lite = $base_path . '_128k.mp3';
    $comando_lite = "/usr/bin/ffmpeg -i {$audio_path} -b:a 128k {$nuevo_archivo_path_lite}";
    guardarLog("Ejecutando comando: {$comando_lite}");
    exec($comando_lite, $output_lite, $return_var_lite);
    if ($return_var_lite !== 0) {
        guardarLog("Error al procesar audio ligero: " . implode("\n", $output_lite));
    }

    // Insertar archivo en la biblioteca de medios
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Archivo ligero
    $filetype_lite = wp_check_filetype(basename($nuevo_archivo_path_lite), null);
    $attachment_lite = array(
        'post_mime_type' => $filetype_lite['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', basename($nuevo_archivo_path_lite)),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id_lite = wp_insert_attachment($attachment_lite, $nuevo_archivo_path_lite, $post_id);
    guardarLog("ID de adjunto ligero: {$attach_id_lite}");
    $attach_data_lite = wp_generate_attachment_metadata($attach_id_lite, $nuevo_archivo_path_lite);
    wp_update_attachment_metadata($attach_id_lite, $attach_data_lite);

    // Actualizar el nuevo post con el archivo ligero generado
    $meta_key = ($index == 1) ? "post_audio_lite" : "post_audio_lite_{$index}";
    update_post_meta($post_id, $meta_key, $attach_id_lite);

    // Extraer y guardar la duración del audio
    $duration_command = "/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$nuevo_archivo_path_lite}";
    guardarLog("Ejecutando comando para duración del audio: {$duration_command}");
    $duration_in_seconds = shell_exec($duration_command);
    guardarLog("Salida de ffprobe: '{$duration_in_seconds}'");

    // Limpiar y validar la duración del audio
    $duration_in_seconds = trim($duration_in_seconds);
    if (is_numeric($duration_in_seconds)) {
        $duration_in_seconds = (float)$duration_in_seconds;
        $duration_formatted = floor($duration_in_seconds / 60) . ':' . str_pad($duration_in_seconds % 60, 2, '0', STR_PAD_LEFT);
        update_post_meta($post_id, "audio_duration_{$index}", $duration_formatted);
        guardarLog("Duración del audio (formateada): {$duration_formatted}");
    } else {
        guardarLog("Duración del audio no válida para el archivo {$audio_path}");
    }

    guardarLog("datos para sacar meta post id: {$post_id} path_lite: {$nuevo_archivo_path_lite} index: {$index}");
    analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index);
}

function analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index)
{
    // Ejecutar el script de Python para análisis de audio
    $python_command = "python3 /var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/audio.py \"{$nuevo_archivo_path_lite}\"";
    guardarLog("Ejecutando comando de Python: {$python_command}");
    exec($python_command);

    // Leer los resultados del archivo JSON
    $resultados_path = $nuevo_archivo_path_lite . '_resultados.json';
    if (file_exists($resultados_path)) {
        $resultados = json_decode(file_get_contents($resultados_path), true);
        if ($resultados) {
            $suffix = ($index == 1) ? '' : "_{$index}";
            
            update_post_meta($post_id, "audio_bpm{$suffix}", $resultados['bpm']);
            update_post_meta($post_id, "audio_pitch{$suffix}", $resultados['pitch']);
            update_post_meta($post_id, "audio_emotion{$suffix}", $resultados['emotion']);
            update_post_meta($post_id, "audio_key{$suffix}", $resultados['key']);
            update_post_meta($post_id, "audio_scale{$suffix}", $resultados['scale']);
            update_post_meta($post_id, "audio_strength{$suffix}", $resultados['strength']);
        }
    } else {
        guardarLog("No se encontró el archivo de resultados en {$resultados_path}");
    }

    // Generar descripción del audio usando la API de Gemini
    $prompt = "Describe el contenido de este audio en español. Incluye detalles sobre el género musical, instrumentos, ritmo, y cualquier característica distintiva que notes.";
    $descripcion = generarDescripcionIA($nuevo_archivo_path_lite, $prompt);

    // Guardar la descripción generada como meta del post
    if ($descripcion) {
        $suffix = ($index == 1) ? '' : "_{$index}";
        update_post_meta($post_id, "audio_descripcion{$suffix}", $descripcion);
        guardarLog("Descripción del audio guardada para el post ID: {$post_id}");
    } else {
        guardarLog("No se pudo generar la descripción del audio para el post ID: {$post_id}");
    }
}

