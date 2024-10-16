<?

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

    // Guardar los tags en el meta campo 'tagsUsuario'
    if (!empty($tags)) {
        update_post_meta($postId, 'tagsUsuario', $tags);
    }

    return $postId;
}
function datosParaAlgoritmo($postId)
{

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
    $campos = ['archivoId', 'audioId', 'imagenId'];

    foreach ($campos as $campo) {
        if (!empty($_POST[$campo])) {
            $file_id = intval($_POST[$campo]);

            if ($file_id > 0) {
                update_post_meta($postId, 'idHash_' . $campo, $file_id);
                guardarLog("idHash_{$campo} actualizado para postId: {$postId}");
                confirmarHashId($file_id);
            }
        }
    }
}

function eliminarAdjuntosPost($post_id)
{
    $adjuntos = get_attached_media('', $post_id);

    foreach ($adjuntos as $adjunto) {
        wp_delete_attachment($adjunto->ID, true);
        guardarLog("Adjunto eliminado: {$adjunto->ID} para postId: {$post_id}");

        $file_hash = get_post_meta($post_id, 'idHash_archivoId', true);

        if ($file_hash) {
            eliminarHash($file_hash);
            guardarLog("Hash eliminado: {$file_hash} para postId: {$post_id}");

            delete_post_meta($post_id, 'idHash_archivoId');
        }
    }
}

// Hook para ejecutar la función antes de que se borre un post
add_action('before_delete_post', 'eliminarAdjuntosPost');

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
                'key' => "post_audio_lite",
                'value' => $audio_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    // Obtener el archivo de audio original
    $audio_path = get_attached_file($audio_id);
    guardarLog("Ruta del archivo de audio original: {$audio_path}");

    // Si ya existe un archivo ligero, asociarlo al nuevo post
    if ($existing_lite_audio) {
        $existing_lite_audio_id = $existing_lite_audio[0]->ID;
        guardarLog("Archivo ligero ya existente encontrado: ID $existing_lite_audio_id, asociando al nuevo post.");
        $meta_key = ($index == 1) ? "post_audio_lite" : "post_audio_lite_{$index}";
        update_post_meta($post_id, $meta_key, $existing_lite_audio_id);
        update_post_meta($post_id, 'AudioDuplicado', true);
        analizarYGuardarMetasAudio($post_id, $audio_path, $index);
        return;
    }

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
    $python_command = escapeshellcmd("python3 /var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/audio.py \"{$nuevo_archivo_path_lite}\"");
    iaLog("Ejecutando comando de Python: {$python_command}");
    exec($python_command, $output, $return_var);

    // Verificar si el script de Python se ejecutó correctamente
    if ($return_var !== 0) {
        iaLog("Error al ejecutar el script de Python. Código de retorno: {$return_var}. Salida: " . implode("\n", $output));
        return;
    }

    // Leer los resultados del archivo JSON generado por el script de Python
    $resultados_path = $nuevo_archivo_path_lite . '_resultados.json';
    if (file_exists($resultados_path)) {
        $resultados = json_decode(file_get_contents($resultados_path), true);

        if ($resultados && is_array($resultados)) {
            $suffix = ($index == 1) ? '' : "_{$index}";

            // Guardar los metadatos del audio generados por Python
            update_post_meta($post_id, "audio_bpm{$suffix}", $resultados['bpm'] ?? '');
            update_post_meta($post_id, "audio_pitch{$suffix}", $resultados['pitch'] ?? '');
            update_post_meta($post_id, "audio_emotion{$suffix}", $resultados['emotion'] ?? '');
            update_post_meta($post_id, "audio_key{$suffix}", $resultados['key'] ?? '');
            update_post_meta($post_id, "audio_scale{$suffix}", $resultados['scale'] ?? '');
            update_post_meta($post_id, "audio_strength{$suffix}", $resultados['strength'] ?? '');
        } else {
            iaLog("El archivo de resultados JSON no contiene datos válidos.");
        }
    } else {
        iaLog("No se encontró el archivo de resultados en {$resultados_path}");
    }

    // Obtener el contenido del post
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
        return;
    }

    // Obtener los tagsUsuario del post
    $tags_usuario = get_post_meta($post_id, 'tagsUsuario', true);
    if ($tags_usuario) {
        $tags_usuario_texto = is_array($tags_usuario) ? implode(', ', $tags_usuario) : $tags_usuario;
    } else {
        $tags_usuario_texto = 'Sin etiquetas';
    }

    $prompt = "Un usuario acaba de subir un audio con la siguiente descripción: {$post_content}. Los tags asociados son: {$tags_usuario_texto}."
        . "Por favor, determina una descripción del audio utilizando el siguiente formato (ESTOS SON DATOS DE EJEMPLO): "
        . '{"Descripcion":"Descripción del audio generada por IA", "Instrumentos posibles":["Piano", "Guitarra", "Batería"], "Estado de animo":["Tranquilo", "Suave"], "Genero posible":["Hip hop", "Electrónica"], "Tipo de audio":["Sample"], "Tags posibles":["Naturaleza", "Percusión", "Relajación"], "Sugerencia de busqueda":["Sonido relajante", "percusión suave", "baterías para hip hop", "efectos cinematograficos"]}. '
        . "Nota adicional: solo responde con la estructura, intenta ser muy detallista con los datos, no digas nada adicional al usuario, el audio se esta subiendo a una biblioteca de samples por eso es importante determinar los datos sabiendo que son para que sea mas facil encontrarlos en base a las descripciones y busqueda, la descripcion tiene que ser corta y breve, agrega solo datos en español, los tipos de audios hay muchos tipos, pueden ser samples, efectos, vocales, kicks, percusiones, intenta determinar que tipo de audio, habrá ocaciones que no se pueda determinar un genero porque un kick o un efecto de explosion no tiene genero ni un estado de animo como tal, se puede omitir cosas, las sugerencias de busqueda piensa en como el usuario puede buscar el audio y encontrarlo";

    // Generar la descripción con la IA
    $descripcion = generarDescripcionIA($nuevo_archivo_path_lite, $prompt);

    // Guardar la descripción generada como meta del post
    if ($descripcion) {
        // Limpiar la descripción generada (remover caracteres innecesarios como '```json' y asegurarnos de que esté en UTF-8)
        $descripcion_limpia = json_decode(trim($descripcion, "```json \n"), true);

        if ($descripcion_limpia) {
            $suffix = ($index == 1) ? '' : "_{$index}";
            update_post_meta($post_id, "audio_descripcion{$suffix}", json_encode($descripcion_limpia, JSON_UNESCAPED_UNICODE));
            iaLog("Descripción del audio guardada para el post ID: {$post_id}");
        } else {
            iaLog("Error al procesar el JSON de la descripción generada por IA.");
        }
    } else {
        iaLog("No se pudo generar la descripción del audio para el post ID: {$post_id}");
    }

    // Actualizar el metadato 'datosAlgoritmo' sumando la nueva información
    $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);

    if (!$datos_algoritmo) {
        $datos_algoritmo = [];
    } else {
        $datos_algoritmo = json_decode($datos_algoritmo, true);
        if (!is_array($datos_algoritmo)) {
            $datos_algoritmo = [];
        }
    }

    // Concatenar la información del script de Python y la IA
    $nuevos_datos = [
        'bpm' => $resultados['bpm'] ?? '',
        'emotion' => $resultados['emotion'] ?? '',
        'key' => $resultados['key'] ?? '',
        'scale' => $resultados['scale'] ?? '',
        'descripcion_ia' => $descripcion_limpia ?? []
    ];

    iaLog("Datos nuevos a agregar: " . json_encode($nuevos_datos));

    // Agregar los nuevos datos al metadato existente
    $datos_algoritmo = array_merge($datos_algoritmo, $nuevos_datos);

    iaLog("Metadatos actuales para 'datosAlgoritmo' antes de guardar: " . json_encode($datos_algoritmo));

    // Guardar nuevamente el metadato actualizado
    update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_algoritmo, JSON_UNESCAPED_UNICODE));
    update_post_meta($post_id, 'flashIA', true);

    iaLog("Metadatos de 'datosAlgoritmo' actualizados para el post ID: {$post_id}");
}

function mejorarDescripcionAudioPro($post_id, $archivo_audio)
{
    // Obtener el contenido actual del post
    iaLog("Iniciando mejora de descripción para el post ID: {$post_id}");
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
        return;
    }
    iaLog("Contenido del post obtenido para el post ID: {$post_id}");

    // Obtener los tags manuales del usuario desde la meta 'tagsUsuario'
    $tags_usuario_str = get_post_meta($post_id, 'tagsUsuario', true);
    if (!$tags_usuario_str) {
        iaLog("No se encontraron tagsUsuario para el post ID: {$post_id}");
        $tags_usuario = [];
    } else {
        // Convertir la cadena de tags en un array, eliminando espacios y manejando mayúsculas/minúsculas
        $tags_usuario = array_map('trim', explode(',', $tags_usuario_str));
        $tags_usuario = array_filter($tags_usuario); // Eliminar posibles elementos vacíos
        iaLog("TagsUsuario obtenidos y procesados para el post ID: {$post_id}");
    }

    // Convertir el array de tags en una cadena separada por comas para incluir en el prompt
    $tags_usuario_formateados = implode(', ', $tags_usuario);
    iaLog("TagsUsuario formateados: {$tags_usuario_formateados}");

    // Crear el prompt para el modelo Pro, incluyendo los tags manuales
    $prompt = "El usuario ya subió este audio, pero se necesita una descripción del audio mejorada. "
        . "El post original dice: \"{$post_content}\". "
        . "Además, el usuario ha agregado los siguientes tags: {$tags_usuario_formateados}. "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON (ESTOS SON DATOS DE EJEMPLO): "
        . '{"Descripcion":{"es":"(aqui iría una descripcion tuya del audio muy detallada)", "en":"(aqui en ingles)"},'
        . '"Instrumentos posibles":{"es":["Piano", "Guitarra"], "en":["Piano", "Guitar"]},'
        . '"Estado de animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"Genero posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"Tipo de audio":{"es":["Sample, loop o one shot"], "en":["Sample"]},'
        . '"Tags posibles":{"es":["Naturaleza, phonk, memphis, oscuro"], "en":["Nature"]},'
        . '"Sugerencia de busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . " Nota adicional: solo responde con la estructura, intenta ser muy detallista y preciso con los datos, no digas nada adicional al usuario. Algo importante a tener en cuenta es que debe determinarse bien si es un loop, o un one shot, o sea, los sonidos o golpes de sonidos, suenan una sola vez, y los loop es una secuencia de sonido, tu me entiendes, esa informacion va en tipo de audio, en los tags posible, preferiblemente tags de una sola palabra, no frases."
        . "La descripción tiene que ser corta y breve, agrega solo datos en español y también en inglés, agrega muchas sugerencias de busqueda para optimizar el SEO.";

    // Usar el modelo Pro para generar la nueva descripción
    $descripcion_mejorada = generarDescripcionIAPro($archivo_audio, $prompt);

    if ($descripcion_mejorada) {
        iaLog("Descripción mejorada generada correctamente para el post ID: {$post_id}");
        // Limpiar y procesar la nueva descripción generada
        $descripcion_procesada = json_decode(trim($descripcion_mejorada, "```json \n"), true);

        if ($descripcion_procesada) {
            iaLog("Descripción JSON procesada correctamente para el post ID: {$post_id}");

            // Obtener el metadato 'datosAlgoritmo' existente
            $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
            if ($datos_algoritmo) {
                $datos_algoritmo = json_decode($datos_algoritmo, true);
                iaLog("DatosAlgoritmo existentes obtenidos para el post ID: {$post_id}");
            } else {
                $datos_algoritmo = [];
                iaLog("No se encontraron datosAlgoritmo existentes para el post ID: {$post_id}");
            }

            // Preservar los datos esenciales
            $datos_preservados = [
                'tags' => $datos_algoritmo['tags'] ?? [],
                'bpm' => $datos_algoritmo['bpm'] ?? null,
                'key' => $datos_algoritmo['key'] ?? null,
                'scale' => $datos_algoritmo['scale'] ?? null,
                'autor' => $datos_algoritmo['autor'] ?? []
            ];
            iaLog("Datos preservados para el post ID: {$post_id}");

            // Agregar la nueva descripción IA mejorada con traducciones
            $nuevos_datos = [
                'descripcion_ia_pro' => [
                    'es' => $descripcion_procesada['Descripcion']['es'] ?? '',
                    'en' => $descripcion_procesada['Descripcion']['en'] ?? ''
                ],
                'instrumentos_posibles' => [
                    'es' => $descripcion_procesada['Instrumentos posibles']['es'] ?? [],
                    'en' => $descripcion_procesada['Instrumentos posibles']['en'] ?? []
                ],
                'estado_animo' => [
                    'es' => $descripcion_procesada['Estado de animo']['es'] ?? [],
                    'en' => $descripcion_procesada['Estado de animo']['en'] ?? []
                ],
                'genero_posible' => [
                    'es' => $descripcion_procesada['Genero posible']['es'] ?? [],
                    'en' => $descripcion_procesada['Genero posible']['en'] ?? []
                ],
                'tipo_audio' => [
                    'es' => $descripcion_procesada['Tipo de audio']['es'] ?? [],
                    'en' => $descripcion_procesada['Tipo de audio']['en'] ?? []
                ],
                'tags_posibles' => [
                    'es' => $descripcion_procesada['Tags posibles']['es'] ?? [],
                    'en' => $descripcion_procesada['Tags posibles']['en'] ?? []
                ],
                'sugerencia_busqueda' => [
                    'es' => $descripcion_procesada['Sugerencia de busqueda']['es'] ?? [],
                    'en' => $descripcion_procesada['Sugerencia de busqueda']['en'] ?? []
                ]
            ];
            iaLog("Nuevos datos generados para el post ID: {$post_id}");

            // Combinar los datos preservados con los nuevos
            $datos_actualizados = array_merge($datos_preservados, $nuevos_datos);

            // Guardar los metadatos actualizados
            update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_actualizados, JSON_UNESCAPED_UNICODE));
            iaLog("Metadatos actualizados para el post ID: {$post_id}");

            // Marcar que la descripción fue mejorada
            update_post_meta($post_id, 'proIA', true);
            iaLog("Post con descripción mejorada marcado como IA Pro para el post ID: {$post_id}");
        } else {
            iaLog("Error al procesar el JSON de la descripción mejorada generada por IA Pro.");
        }
    } else {
        iaLog("No se pudo generar la descripción mejorada para el post ID: {$post_id}");
    }
}



function procesarUnAudio() {
    global $wpdb;

    // Buscar el post con un archivo de audio válido (post_audio_lite) que aún no tenga 'proIA' en true y procesar solo uno
    iaLog("Iniciando el procesamiento de un audio...");
    
    $query = "
        SELECT p.ID, pm.meta_value AS archivo_audio_id
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        LEFT JOIN {$wpdb->postmeta} proIA_meta ON p.ID = proIA_meta.post_id AND proIA_meta.meta_key = 'proIA'
        WHERE pm.meta_key = 'post_audio_lite'
        AND proIA_meta.meta_value IS NULL
        AND p.post_status = 'publish'
        ORDER BY p.post_date DESC
        LIMIT 1
    ";

    iaLog("Ejecutando consulta SQL para buscar post con audio...");
    
    $post_con_audio = $wpdb->get_row($query);

    if ($post_con_audio) {
        $post_id = $post_con_audio->ID;
        $archivo_audio_id = $post_con_audio->archivo_audio_id;

        iaLog("Post encontrado. ID: {$post_id}, archivo_audio_id: {$archivo_audio_id}");

        // Obtener la URL del archivo de audio a partir de la ID
        $archivo_audio_url = wp_get_attachment_url($archivo_audio_id);

        // Si no se encuentra la URL, saltar al siguiente post
        if (!$archivo_audio_url) {
            iaLog("No se encontró la URL del archivo de audio para el post ID: {$post_id}. Saliendo.");
            return;
        }

        iaLog("URL del archivo de audio obtenida: {$archivo_audio_url}");

        // Convertir la URL a la ruta del servidor
        $upload_dir = wp_upload_dir();
        $archivo_audio_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $archivo_audio_url);

        iaLog("Ruta del servidor del archivo de audio: {$archivo_audio_path}");

        // Verificar si el archivo de audio existe en el servidor
        if (file_exists($archivo_audio_path)) {
            iaLog("El archivo de audio existe en el servidor. Iniciando mejora de descripción...");

            // Ejecutar la función para mejorar la descripción del audio
            mejorarDescripcionAudioPro($post_id, $archivo_audio_path);

            iaLog("Mejora de descripción completada para el post ID: {$post_id}");
        } else {
            iaLog("El archivo de audio no existe en el servidor para el post ID: {$post_id}");
        }
    } else {
        iaLog("No se encontraron posts pendientes de mejora de descripción.");
    }

    iaLog("Procesamiento de audio completado.");
}


// Función para ejecutarla cada 30 minutos
function programarMejorarDescripcionAudioPro() {
    if (!wp_next_scheduled('mejorar_descripcion_audio_event')) {
        wp_schedule_event(time(), 'thirty_minutes', 'mejorar_descripcion_audio_event');
    }
}

add_action('mejorar_descripcion_audio_event', 'procesarUnAudio');

// Registrar intervalo de 30 minutos
function agregarIntervaloCronPersonalizado($schedules) {
    $schedules['thirty_minutes'] = [
        'interval' => 1800, // 1800 segundos = 30 minutos
        'display'  => __('Cada 30 minutos')
    ];
    return $schedules;
}

add_filter('cron_schedules', 'agregarIntervaloCronPersonalizado');

// Programar evento al activar el plugin o tema
add_action('wp', 'programarMejorarDescripcionAudioPro');
