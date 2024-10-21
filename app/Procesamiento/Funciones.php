<?

# Paso 1 
function crearPost($tipoPost = 'social_post', $estadoPost = 'publish')
{
    $contenido = sanitize_textarea_field($_POST['textoNormal'] ?? '');
    $tags = sanitize_text_field($_POST['tags'] ?? '');
    if (empty($contenido)) {
        guardarLog('empty_content: El contenido no puede estar vacío.');
        return new WP_Error('empty_content', 'El contenido no puede estar vacío.');
    }
    $titulo = wp_trim_words($contenido, 15, '...');
    $autor = get_current_user_id();
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
    if (!empty($tags)) {
        update_post_meta($postId, 'tagsUsuario', $tags);
    }

    return $postId;
}

#Paso 2
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

#Paso 3
function datosParaAlgoritmo($postId)
{
    $textoNormal = isset($_POST['textoNormal']) ? trim($_POST['textoNormal']) : '';
    $textoNormal = htmlspecialchars_decode($textoNormal, ENT_QUOTES);
    $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];
    $autorId = get_post_field('post_author', $postId);
    $nombreUsuario = get_the_author_meta('user_login', $autorId);
    $nombreMostrar = get_the_author_meta('display_name', $autorId);
    $datosAlgoritmo = [
        'tags' => $tags,
        'texto' => $textoNormal,
        'autor' => [
            'id' => $autorId,
            'usuario' => $nombreUsuario,
            'nombre' => $nombreMostrar,
        ],
    ];
    if ($datosAlgoritmoJson = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE)) {
        update_post_meta($postId, 'datosAlgoritmo', $datosAlgoritmoJson);
    } else {
    }
}

#Paso 4
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

#Paso 5
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

#Paso 5.1
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
    } else {
        guardarLog("Error: No se pudo procesar el archivo para Post ID: $postId y Campo: $campo");
    }

    return false;
}

#Paso 5.2
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

#Paso 5.3
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

#Paso 5.4
function renombrarArchivoAdjunto($postId, $archivoId)
{
    $file_id = intval($_POST['audioId']);

    $post = get_post($postId);
    $author = get_userdata($post->post_author);

    if (!$post || !$author) {
        guardarLog("Error: No se pudo obtener el post o el autor.");
        return new WP_Error('post_or_author_not_found', 'No se pudo obtener el post o el autor.');
    }

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
        $upload_dir = wp_upload_dir();
        $public_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path);

        actualizarUrlArchivo($file_id, $public_url);
        update_attached_file($archivoId, $new_file_path);
        update_post_meta($postId, 'sample', true);
        procesarAudioLigero($postId, $archivoId, 1);
    } else {
        guardarLog("Error: No se pudo renombrar el archivo adjunto de $file_path a $new_file_path.");
        return new WP_Error('rename_failed', 'No se pudo renombrar el archivo adjunto.');
    }
}


#Paso 6
function asignarTags($postId)
{
    if (!empty($_POST['Tags'])) {
        $tags = sanitize_text_field($_POST['Tags']);
        $tags_array = explode(',', $tags);
        wp_set_post_tags($postId, $tags_array, false);
    }
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

    // Obtener las partes del camino del archivo
    $path_parts = pathinfo($audio_path);
    // $unique_id = uniqid('2upra_'); // Ya no es necesario generar un nuevo nombre
    $base_path = $path_parts['dirname'] . '/' . $path_parts['filename'];

    // Eliminar metadatos del archivo original usando ffmpeg
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c:v copy " . escapeshellarg($audio_path . '.tmp') . " && mv " . escapeshellarg($audio_path . '.tmp') . " " . escapeshellarg($audio_path);
    guardarLog("Ejecutando comando para eliminar metadatos del archivo original: {$comando_strip_metadata}");
    exec($comando_strip_metadata, $output_strip, $return_strip);
    if ($return_strip !== 0) {
        guardarLog("Error al eliminar metadatos del archivo original: " . implode("\n", $output_strip));
    } else {
        guardarLog("Metadatos del archivo original eliminados correctamente.");
    }

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

    // Obtener el nombre de usuario del autor del post
    $post_author_id = get_post_field('post_author', $post_id);
    $author_info = get_userdata($post_author_id);
    if ($author_info) {
        $author_username = $author_info->user_login;
        guardarLog("Nombre de usuario del autor obtenido: {$author_username}");
    } else {
        // Fallback en caso de no obtener la información del usuario
        $author_username = "Desconocido";
        guardarLog("No se pudo obtener el nombre de usuario del autor. Se usará 'Desconocido'.");
    }

    $page_name = "2upra.com";

    // Procesar archivo de audio ligero (128 kbps) con metadatos adicionales
    $nuevo_archivo_path_lite = $base_path . '_128k.mp3';
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k -metadata author=" . escapeshellarg($author_username) . " -metadata comment=" . escapeshellarg($page_name) . " " . escapeshellarg($nuevo_archivo_path_lite);
    guardarLog("Ejecutando comando para crear audio ligero con metadatos: {$comando_lite}");
    exec($comando_lite, $output_lite, $return_var_lite);
    if ($return_var_lite !== 0) {
        guardarLog("Error al procesar audio ligero: " . implode("\n", $output_lite));
    } else {
        guardarLog("Audio ligero creado exitosamente con metadatos.");
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
    if (is_wp_error($attach_id_lite)) {
        guardarLog("Error al insertar el adjunto ligero: " . $attach_id_lite->get_error_message());
        return;
    }
    guardarLog("ID de adjunto ligero: {$attach_id_lite}");
    $attach_data_lite = wp_generate_attachment_metadata($attach_id_lite, $nuevo_archivo_path_lite);
    wp_update_attachment_metadata($attach_id_lite, $attach_data_lite);

    // Actualizar el nuevo post con el archivo ligero generado
    $meta_key = ($index == 1) ? "post_audio_lite" : "post_audio_lite_{$index}";
    update_post_meta($post_id, $meta_key, $attach_id_lite);

    // Extraer y guardar la duración del audio
    $duration_command = "/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($nuevo_archivo_path_lite);
    guardarLog("Ejecutando comando para duración del audio: {$duration_command}");
    $duration_in_seconds = shell_exec($duration_command);
    guardarLog("Salida de ffprobe: '{$duration_in_seconds}'");

    // Limpiar y validar la duración del audio
    $duration_in_seconds = trim($duration_in_seconds);
    if (is_numeric($duration_in_seconds)) {
        $duration_in_seconds = (float)$duration_in_seconds;
        $duration_formatted = floor($duration_in_seconds / 60) . ':' . str_pad(floor($duration_in_seconds % 60), 2, '0', STR_PAD_LEFT);
        update_post_meta($post_id, "audio_duration_{$index}", $duration_formatted);
        guardarLog("Duración del audio (formateada): {$duration_formatted}");
    } else {
        guardarLog("Duración del audio no válida para el archivo {$nuevo_archivo_path_lite}");
    }

    guardarLog("datos para sacar meta post id: {$post_id} path_lite: {$nuevo_archivo_path_lite} index: {$index}");
    analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index);
}

/*


*/

function analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index)
{
    $python_command = escapeshellcmd("python3 /var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/audio.py \"{$nuevo_archivo_path_lite}\"");
    iaLog("Ejecutando comando de Python: {$python_command}");
    exec($python_command, $output, $return_var);

    if ($return_var !== 0) {
        iaLog("Error al ejecutar el script de Python. Código de retorno: {$return_var}. Salida: " . implode("\n", $output));
        return;
    }

    $resultados_path = $nuevo_archivo_path_lite . '_resultados.json';
    if (file_exists($resultados_path)) {
        $resultados = json_decode(file_get_contents($resultados_path), true);

        if ($resultados && is_array($resultados)) {
            $suffix = ($index == 1) ? '' : "_{$index}";

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

    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
        return;
    }

    $tags_usuario = get_post_meta($post_id, 'tagsUsuario', true);
    $tags_usuario_texto = $tags_usuario ? (is_array($tags_usuario) ? implode(', ', $tags_usuario) : $tags_usuario) : 'No se agregaron etiquetas por el usuario esta vez';

    $postAut = get_post_meta($post_id, 'postAut', true);
    $verificado = get_post_meta($post_id, 'Verificado', true);

    if ($postAut == 1 && $verificado != 1) {
        iaLog("El post ID: {$post_id} tiene postAut en 1 y no está verificado. No se enviará el contenido a la IA.");
        $post_content = '';
    } else {
        $post_content = get_post_field('post_content', $post_id);
        if (!$post_content) {
            iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
        } else {
            iaLog("Contenido del post obtenido para el post ID: {$post_id}");
        }
    }

    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción o lo acaba de publicar ahora mismo. "
        . "Ten muy en cuenta la descripcion. descripción:\"{$post_content}\". {$tags_usuario_texto}"
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON: "
        . '{"Descripcion":{"es":"(aqui iría una descripcion tuya del audio muy detallada)", "en":"(aqui en ingles)"},'
        . '"instrumentos_posibles":{"es":["Piano", "Guitarra"], "en":["Piano", "Guitar"]},'
        . '"estado_animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"genero_posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"artista_posible":{"es":["Freddie Dredd, Flume"], "en":["Freddie Dredd, Flume"]},'
        . '"tipo_audio":{"es":["aqui necesito que puedas determinar si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"tags_posibles":{"es":["Naturaleza, phonk, memphis, oscuro"], "en":["Nature"]},'
        . '"sugerencia_busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot, o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes. Se muy detallado sin perder precisión";

    $descripcion = generarDescripcionIA($nuevo_archivo_path_lite, $prompt);

    if ($descripcion) {
        // Procesar el JSON eliminando caracteres innecesarios
        $descripcion_procesada = json_decode(trim($descripcion, "```json \n"), true);

        if ($descripcion_procesada) {
            // Definir el sufijo para los datos, según el índice
            $suffix = ($index == 1) ? '' : "_{$index}";

            // Asegurarse de que la clave 'descripcion_ia' esté bien estructurada
            if (isset($descripcion_procesada['descripcion_ia'])) {
                $nuevos_datos = [
                    'descripcion' => [
                        'es' => $descripcion_procesada['descripcion_ia']['es'] ?? '',
                        'en' => $descripcion_procesada['descripcion_ia']['en'] ?? ''
                    ],
                    'instrumentos_posibles' => [
                        'es' => $descripcion_procesada['instrumentos_posibles']['es'] ?? [],
                        'en' => $descripcion_procesada['instrumentos_posibles']['en'] ?? []
                    ],
                    'estado_animo' => [
                        'es' => $descripcion_procesada['estado_animo']['es'] ?? [],
                        'en' => $descripcion_procesada['estado_animo']['en'] ?? []
                    ],
                    'artista_posible' => [
                        'es' => $descripcion_procesada['artista_posible']['es'] ?? [],
                        'en' => $descripcion_procesada['artista_posible']['en'] ?? []
                    ],
                    'genero_posible' => [
                        'es' => $descripcion_procesada['genero_posible']['es'] ?? [],
                        'en' => $descripcion_procesada['genero_posible']['en'] ?? []
                    ],
                    'tipo_audio' => [
                        'es' => $descripcion_procesada['tipo_audio']['es'] ?? '',
                        'en' => $descripcion_procesada['tipo_audio']['en'] ?? ''
                    ],
                    'tags_posibles' => [
                        'es' => $descripcion_procesada['tags_posibles']['es'] ?? [],
                        'en' => $descripcion_procesada['tags_posibles']['en'] ?? []
                    ],
                    'sugerencia_busqueda' => [
                        'es' => $descripcion_procesada['sugerencia_busqueda']['es'] ?? [],
                        'en' => $descripcion_procesada['sugerencia_busqueda']['en'] ?? []
                    ]
                ];

                // Guardar los datos procesados en los metadatos del post
                update_post_meta($post_id, "audio_descripcion{$suffix}", json_encode($nuevos_datos, JSON_UNESCAPED_UNICODE));
                iaLog("Descripción del audio guardada para el post ID: {$post_id}");
            } else {
                iaLog("Error: 'descripcion_ia' no está presente en el JSON procesado.");
            }
        } else {
            iaLog("Error al procesar el JSON de la descripción generada por IA.");
        }
    } else {
        iaLog("No se pudo generar la descripción del audio para el post ID: {$post_id}");
    }

    // Obtener los datos del algoritmo
    $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);

    // Si no existen, inicializarlos como un array vacío
    if (!$datos_algoritmo) {
        $datos_algoritmo = [];
    } else {
        $datos_algoritmo = json_decode($datos_algoritmo, true);
        if (!is_array($datos_algoritmo)) {
            $datos_algoritmo = [];
        }
    }

    // Agregar los nuevos datos del algoritmo, incluyendo la descripción procesada
    $nuevos_datos_algoritmo = [
        'bpm' => $resultados['bpm'] ?? '',
        'emotion' => $resultados['emotion'] ?? '',
        'key' => $resultados['key'] ?? '',
        'scale' => $resultados['scale'] ?? '',
        'descripcion_ia' => $nuevos_datos
    ];

    iaLog("Datos nuevos a agregar: " . json_encode($nuevos_datos_algoritmo));

    // Combinar los nuevos datos con los existentes
    $datos_algoritmo = array_merge($datos_algoritmo, $nuevos_datos_algoritmo);

    iaLog("Metadatos actuales para 'datosAlgoritmo' antes de guardar: " . json_encode($datos_algoritmo));

    // Guardar los datos combinados en los metadatos del post
    update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_algoritmo, JSON_UNESCAPED_UNICODE));

    // Actualizar la bandera de IA
    update_post_meta($post_id, 'flashIA', true);

    iaLog("Metadatos de 'datosAlgoritmo' actualizados para el post ID: {$post_id}");
}

function rehacerDescripcionAudio($post_id, $archivo_audio)
{
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
        $tags_usuario = array_map('trim', explode(',', $tags_usuario_str));
        $tags_usuario = array_filter($tags_usuario); // Eliminar posibles elementos vacíos
        iaLog("TagsUsuario obtenidos y procesados para el post ID: {$post_id}");
    }
    // Convertir el array de tags en una cadena separada por comas para incluir en el prompt
    $tags_usuario_formateados = implode(', ', $tags_usuario);
    iaLog("TagsUsuario formateados: {$tags_usuario_formateados}");


    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción o lo acaba de publicar ahora mismo. "
        . "Ten muy en cuenta la descripcion. descripción:\"{$post_content}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON: "
        . '{"Descripcion":{"es":"(aqui iría una descripcion tuya del audio muy detallada)", "en":"(aqui en ingles)"},'
        . '"Instrumentos posibles":{"es":["Piano", "Guitarra"], "en":["Piano", "Guitar"]},'
        . '"Estado de animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"Genero posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"Artista posible":{"es":["Freddie Dredd, Flume"], "en":["Freddie Dredd, Flume"]},'
        . '"Tipo de audio":{"es":["aqui necesito que puedas determinar si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"Tags posibles":{"es":["Naturaleza, phonk, memphis, oscuro"], "en":["Nature"]},'
        . '"Sugerencia de busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes. Se muy detallado sin perder precisión";

    $descripcion_mejorada = generarDescripcionIA($archivo_audio, $prompt);

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
                'artista_posible' => [
                    'es' => $descripcion_procesada['Artista posible']['es'] ?? [],
                    'en' => $descripcion_procesada['Artista posible']['en'] ?? []
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

            // Agregar el metadato 'ultimoEdit' con la fecha actual
            $fecha_actual = current_time('mysql'); // Formato de fecha de WordPress
            update_post_meta($post_id, 'ultimoEdit', $fecha_actual);

            iaLog("Metadato 'ultimoEdit' agregado para el post ID: {$post_id} con fecha {$fecha_actual}");

            // Marcar el post como IA Pro
            update_post_meta($post_id, 'proIA', false);
        } else {
            iaLog("Error al procesar el JSON de la descripción mejorada generada por IA Pro.");
        }
    } else {
        iaLog("No se pudo generar la descripción mejorada para el post ID: {$post_id}");
    }
}

function mejorarDescripcionAudioPro($post_id, $archivo_audio)
{
    // Comprobar si tiene la meta 'postAut' en 1
    $postAut = get_post_meta($post_id, 'postAut', true);
    // Comprobar si tiene la meta 'Verificado' en 1
    $verificado = get_post_meta($post_id, 'Verificado', true);

    // Si la meta 'postAut' es 1 y la meta 'Verificado' no es 1, no enviar el contenido a la IA
    if ($postAut == 1 && $verificado != 1) {
        iaLog("El post ID: {$post_id} tiene postAut en 1 y no está verificado. No se enviará el contenido a la IA.");
        $post_content = ''; // No enviamos el contenido
    } else {
        // Obtener el contenido actual del post
        $post_content = get_post_field('post_content', $post_id);
        if (!$post_content) {
            iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
        } else {
            iaLog("Contenido del post obtenido para el post ID: {$post_id}");
        }
    }

    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción o lo acaba de publicar ahora mismo. "
        . "Ten muy en cuenta la descripcion. descripción:\"{$post_content}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON: "
        . '{"Descripcion":{"es":"(aqui iría una descripcion tuya del audio muy detallada)", "en":"(aqui en ingles)"},'
        . '"Instrumentos posibles":{"es":["Piano", "Guitarra"], "en":["Piano", "Guitar"]},'
        . '"Estado de animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"Genero posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"Artista posible":{"es":["Freddie Dredd, Flume"], "en":["Freddie Dredd, Flume"]},'
        . '"Tipo de audio":{"es":["aqui necesito que puedas determinar si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"Tags posibles":{"es":["Naturaleza, phonk, memphis, oscuro"], "en":["Nature"]},'
        . '"Sugerencia de busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot, o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes. Se muy detallado sin perder precisión";

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
                'artista_posible' => [
                    'es' => $descripcion_procesada['Artista posible']['es'] ?? [],
                    'en' => $descripcion_procesada['Artista posible']['en'] ?? []
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



function procesarUnAudio()
{
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
function programarMejorarDescripcionAudioPro()
{
    if (!wp_next_scheduled('mejorar_descripcion_audio_event')) {
        wp_schedule_event(time(), 'thirty_minutes', 'mejorar_descripcion_audio_event');
    }
}

add_action('mejorar_descripcion_audio_event', 'procesarUnAudio');

// Registrar intervalo de 30 minutos
function agregarIntervaloCronPersonalizado($schedules)
{
    $schedules['thirty_minutes'] = [
        'interval' => 1800, // 1800 segundos = 30 minutos
        'display'  => __('Cada 30 minutos')
    ];
    return $schedules;
}

add_filter('cron_schedules', 'agregarIntervaloCronPersonalizado');

// Programar evento al activar el plugin o tema
add_action('wp', 'programarMejorarDescripcionAudioPro');
