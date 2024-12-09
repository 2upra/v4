<?

# Paso 1 
function crearPost($tipoPost = 'social_post', $estadoPost = 'publish') {
    $contenido = sanitize_textarea_field($_POST['textoNormal'] ?? '');
    $tags = sanitize_text_field($_POST['tags'] ?? '');
    
    if (empty($contenido)) {
        error_log('Error en crearPost: El contenido no puede estar vacío.');
        return new WP_Error('empty_content', 'El contenido no puede estar vacío.');
    }
    
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
        error_log('Error en crearPost: Error al insertar el post. Detalles: ' . $postId->get_error_message());
        return $postId;
    }
    
    // Actualizar metadatos (tags)
    if (!empty($tags)) {
        if (update_post_meta($postId, 'tagsUsuario', $tags) === false) {
            error_log('Error en crearPost: Fallo al actualizar meta tagsUsuario para el post ID ' . $postId);
        }
    }

    // Guardar notificaciones pendientes
    $seguidores = get_user_meta($autor, 'seguidores', true);
    if (!empty($seguidores) && is_array($seguidores)) {
        $autor_nombre = get_the_author_meta('display_name', $autor);
        $contenido_corto = wp_trim_words($contenido, 10, '...');

        $notificaciones = get_option('notificaciones_pendientes', []);
        foreach ($seguidores as $seguidor_id) {
            $notificaciones[] = [
                'seguidor_id' => $seguidor_id,
                'mensaje' => "{$autor_nombre} ha publicado: \"{$contenido_corto}\"",
                'post_id' => $postId,
                'titulo' => 'Nueva publicación'
            ];
        }

        update_option('notificaciones_pendientes', $notificaciones);

        // Asegurar que el cron esté programado
        if (!wp_next_scheduled('wp_enqueue_notifications')) {
            wp_schedule_event(time(), 'minute', 'wp_enqueue_notifications');
        }
    } else {
        error_log("El usuario $autor no tiene seguidores o la lista de seguidores no es válida.");
    }
    
    return $postId;
}


add_action('wp_enqueue_notifications', 'procesar_notificaciones');

function procesar_notificaciones() {
    $notificaciones_pendientes = get_option('notificaciones_pendientes', []);
    if (empty($notificaciones_pendientes)) {
        return;
    }
    
    // Procesar un lote de notificaciones (por ejemplo, 5)
    $lote = array_splice($notificaciones_pendientes, 0, 5);

    foreach ($lote as $notificacion) {
        crearNotificacion(
            $notificacion['seguidor_id'],
            $notificacion['mensaje'],
            false,
            $notificacion['post_id'],
            $notificacion['titulo'],
            null
        );
    }

    // Guardar las notificaciones restantes
    update_option('notificaciones_pendientes', $notificaciones_pendientes);

    // Si ya no quedan notificaciones, elimina la tarea programada
    if (empty($notificaciones_pendientes)) {
        wp_clear_scheduled_hook('wp_enqueue_notifications');
    }
}



#Paso 2
function actualizarMetaDatos($postId)
{
    $meta_fields = [
        'paraColab'    => 'colab',
        'esExclusivo'  => 'exclusivo',
        'paraDescarga' => 'descarga',
        'rola'         => 'music',
        'fan'          => 'fan',
        'artista'      => 'artista'
        
    ];

    foreach ($meta_fields as $meta_key => $post_key) {
        $value = isset($_POST[$post_key]) && $_POST[$post_key] == '1' ? 1 : 0;
        if (update_post_meta($postId, $meta_key, $value) === false) {
            error_log("Error en actualizarMetaDatos: Fallo al actualizar el meta $meta_key para el post ID $postId.");
        }
    }

    if (isset($_POST['music']) && $_POST['music'] == '1') {
        registrarNombreRolas($postId);
    }
}




#Paso 2.1
function registrarNombreRolas($postId)
{
    for ($i = 1; $i <= 30; $i++) {
        $rola_key = 'nombreRola' . $i;
        if (isset($_POST[$rola_key])) {
            $nombre_rola = sanitize_text_field($_POST[$rola_key]);
            if (update_post_meta($postId, $rola_key, $nombre_rola) === false) {
                error_log("Error en registrarNombreRolas: Fallo al actualizar el meta $rola_key para el post ID $postId.");
            }
        }
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
    $datosAlgoritmoJson = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE);
    if ($datosAlgoritmoJson === false) {
        error_log("Error en datosParaAlgoritmo: Fallo al codificar JSON para el post ID: " . $postId . ". Error: " . json_last_error_msg());
    } else {
        if (update_post_meta($postId, 'datosAlgoritmo', $datosAlgoritmoJson) === false) {
            error_log("Error en datosParaAlgoritmo: Fallo al actualizar meta datosAlgoritmo para el post ID " . $postId);
        }
    }
}

#Paso 4
function confirmarArchivos($postId)
{
    $tiposCampos = ['archivoId', 'audioId', 'imagenId'];
    $maxCampos = 30;
    foreach ($tiposCampos as $tipo) {
        for ($i = 1; $i <= $maxCampos; $i++) {
            $campo = $tipo . $i;
            if (!empty($_POST[$campo])) {
                $file_id = intval($_POST[$campo]);
                if ($file_id > 0) {
                    if (update_post_meta($postId, 'idHash_' . $campo, $file_id) === false) {
                        error_log("Error en confirmarArchivos: Fallo al actualizar meta idHash_{$campo} para el post ID: {$postId}");
                    }
                    confirmarHashId($file_id);
                }
            }
        }
    }
}

#Paso 5
function procesarURLs($postId)
{
    $tiposURLs = [
        'imagenUrl'  => ['procesarArchivo', false],
        'audioUrl'   => ['procesarArchivo', true],
        'archivoUrl' => ['procesarArchivo', false],
    ];
    $maxCampos = 30;
    foreach ($tiposURLs as $tipo => $callbackData) {
        $funcionCallback = $callbackData[0];
        $parametroAdicional = isset($callbackData[1]) ? $callbackData[1] : null;
        for ($i = 1; $i <= $maxCampos; $i++) {
            $campo = $tipo . $i;
            if (!empty($_POST[$campo])) {
                $url = esc_url_raw($_POST[$campo]);
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    if ($parametroAdicional !== null) {
                        call_user_func($funcionCallback, $postId, $campo, $parametroAdicional);
                    } else {
                        call_user_func($funcionCallback, $postId, $campo);
                    }
                } else {
                    error_log("Error en procesarURLs: URL inválida en el campo: {$campo} para postId: {$postId}");
                }
            }
        }
    }
}

#Paso 5.1 #Prepara para buscar la ID y actualizar la meta
function procesarArchivo($postId, $campo, $renombrar = false)
{
    $url = esc_url_raw($_POST[$campo]);
    $archivoId = obtenerArchivoId($url, $postId);
    if ($archivoId && !is_wp_error($archivoId)) {
        if (actualizarMetaConArchivo($postId, $campo, $archivoId) === false) {
            error_log("Error en procesarArchivo: Fallo al actualizar meta con archivo para Post ID: $postId y Campo: $campo");
            return false;
        }
        if ($renombrar) {
            if (renombrarArchivoAdjunto($postId, $archivoId, $campo) === false) {
                error_log("Error en procesarArchivo: Fallo al renombrar el archivo adjunto para Post ID: $postId, Campo: $campo y Archivo ID: $archivoId");
                return false;
            }
        }
        return true;
    } else {
        $mensajeError = is_wp_error($archivoId) ? $archivoId->get_error_message() : "No se pudo obtener el ID del archivo.";
        error_log("Error en procesarArchivo: No se pudo procesar el archivo para Post ID: $postId, Campo: $campo. Error: " . $mensajeError);
    }
    return false;
}

#PASO 5.2 

function renombrarArchivoAdjunto($postId, $archivoId, $campo)
{
    if (!preg_match('/(\d+)$/', $campo, $matches)) {
        error_log("Error en renombrarArchivoAdjunto: No se pudo extraer el índice del campo {$campo}.");
        return;
    }
    $indice = intval($matches[1]);
    $idHashCampo = "idHash_audioId{$indice}";
    $idHash = get_post_meta($postId, $idHashCampo, true);
    if (empty($idHash)) {
        error_log("Error en renombrarArchivoAdjunto: No se encontró el meta {$idHashCampo} para postId {$postId}.");
        return;
    }
    $audioIdCampo = 'audioId' . $indice;
    $file_id = !empty($_POST[$audioIdCampo]) ? intval($_POST[$audioIdCampo]) : 0;
    if ($file_id !== $archivoId) {
        error_log("Error en renombrarArchivoAdjunto: El file_id ({$file_id}) no coincide con archivoId ({$archivoId}).");
    }
    $post = get_post($postId);
    $author = get_userdata($post->post_author);
    if (!$post || !$author) {
        error_log("Error en renombrarArchivoAdjunto: No se pudo obtener el post o el autor para postId: {$postId}.");
        return;
    }
    $file_path = get_attached_file($archivoId);
    if (!$file_path || !file_exists($file_path)) {
        error_log("Error en renombrarArchivoAdjunto: El archivo adjunto no existe para archivoId: {$archivoId}.");
        return;
    }
    $info = pathinfo($file_path);
    $random_id = rand(10000, 99999);
    $new_filename = sprintf(
        '2upra_%s_%s_%s.%s',
        sanitize_file_name(mb_substr($author->user_login, 0, 20)),
        sanitize_file_name(mb_substr($post->post_content, 0, 40)),
        $random_id,
        $info['extension']
    );
    $new_file_path = $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename;
    if (rename($file_path, $new_file_path)) {
        $upload_dir = wp_upload_dir();
        $public_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $new_file_path);
        actualizarUrlArchivo($idHash, $public_url);
        update_attached_file($archivoId, $new_file_path);
        update_post_meta($postId, 'sample', true);
        procesarAudioLigero($postId, $archivoId, $indice);
    } else {
        error_log("Error en renombrarArchivoAdjunto: No se pudo renombrar el archivo adjunto de {$file_path} a {$new_file_path}.");
    }
}

#Paso 5.3 #Busca la Id de Adjunto segun la URL
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
            if (is_wp_error($archivoId)) {
                error_log("Error en obtenerArchivoId: No se pudo crear el adjunto desde la URL {$url}. Error: " . $archivoId->get_error_message());
                return false;
            }
        } else {
            error_log("Error en obtenerArchivoId: El archivo no existe en la ruta {$file_path} para la URL {$url}.");
            return false;
        }
    }
    return $archivoId;
}

#Paso 5.4
function actualizarMetaConArchivo($postId, $campo, $archivoId)
{
    $meta_mapping = [
        'imagenUrl'   => 'imagenID',
        'audioUrl'    => 'post_audio',
        'archivoUrl'  => 'archivoID'
    ];
    $pattern = '/^(?<base>imagenUrl|audioUrl|archivoUrl)(?<index>\d*)$/';
    if (preg_match($pattern, $campo, $matches)) {
        $baseField = $matches['base'];
        $index     = $matches['index'];
        if (isset($meta_mapping[$baseField])) {
            $baseMetaKey = $meta_mapping[$baseField];
            $meta_key = $baseMetaKey . ($index !== '' ? $index : '');
            update_post_meta($postId, $meta_key, $archivoId);
            if ($baseField === 'imagenUrl' && $index === '1') {
                set_post_thumbnail($postId, $archivoId);
            }
        } else {
            update_post_meta($postId, $campo, $archivoId);
        }
    } else {
        update_post_meta($postId, $campo, $archivoId);
    }
    if (update_post_meta($postId, $campo, $archivoId) === false) {
        error_log("Error en actualizarMetaConArchivo: Fallo al actualizar meta {$campo} para el post ID {$postId}.");
        return false;
    }
    return true;
}



function procesarAudioLigero($post_id, $audio_id, $index)
{
    //guardarLog("INICIO procesarAudioLigero para Post ID: $post_id y Audio ID: $audio_id");

    // Obtener el archivo de audio original
    $audio_path = get_attached_file($audio_id);
    //guardarLog("Ruta del archivo de audio original: {$audio_path}");

    // Obtener las partes del camino del archivo
    $path_parts = pathinfo($audio_path);
    // $unique_id = uniqid('2upra_'); // Ya no es necesario generar un nuevo nombre
    $base_path = $path_parts['dirname'] . '/' . $path_parts['filename'];

    // Eliminar metadatos del archivo original usando ffmpeg
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c:v copy " . escapeshellarg($audio_path . '.tmp') . " && mv " . escapeshellarg($audio_path . '.tmp') . " " . escapeshellarg($audio_path);
    //guardarLog("Ejecutando comando para eliminar metadatos del archivo original: {$comando_strip_metadata}");
    exec($comando_strip_metadata, $output_strip, $return_strip);
    if ($return_strip !== 0) {
        //guardarLog("Error al eliminar metadatos del archivo original: " . implode("\n", $output_strip));
    } else {
        //guardarLog("Metadatos del archivo original eliminados correctamente.");
    }

    // Obtener el nombre de usuario del autor del post
    $post_author_id = get_post_field('post_author', $post_id);
    $author_info = get_userdata($post_author_id);
    if ($author_info) {
        $author_username = $author_info->user_login;
        //guardarLog("Nombre de usuario del autor obtenido: {$author_username}");
    } else {
        // Fallback en caso de no obtener la información del usuario
        $author_username = "Desconocido";
        //guardarLog("No se pudo obtener el nombre de usuario del autor. Se usará 'Desconocido'.");
    }

    $page_name = "2upra.com";

    // Procesar archivo de audio ligero (128 kbps) con metadatos adicionales
    $nuevo_archivo_path_lite = $base_path . '_128k.mp3';
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k -metadata author=" . escapeshellarg($author_username) . " -metadata comment=" . escapeshellarg($page_name) . " " . escapeshellarg($nuevo_archivo_path_lite);
    //guardarLog("Ejecutando comando para crear audio ligero con metadatos: {$comando_lite}");
    exec($comando_lite, $output_lite, $return_var_lite);
    if ($return_var_lite !== 0) {
        //guardarLog("Error al procesar audio ligero: " . implode("\n", $output_lite));
    } else {
        //guardarLog("Audio ligero creado exitosamente con metadatos.");
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
        //guardarLog("Error al insertar el adjunto ligero: " . $attach_id_lite->get_error_message());
        return;
    }
    //guardarLog("ID de adjunto ligero: {$attach_id_lite}");
    $attach_data_lite = wp_generate_attachment_metadata($attach_id_lite, $nuevo_archivo_path_lite);
    wp_update_attachment_metadata($attach_id_lite, $attach_data_lite);

    // Actualizar el nuevo post con el archivo ligero generado
    $meta_key = ($index == 1) ? "post_audio_lite" : "post_audio_lite_{$index}";
    update_post_meta($post_id, $meta_key, $attach_id_lite);

    // Extraer y guardar la duración del audio
    $duration_command = "/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($nuevo_archivo_path_lite);
    //guardarLog("Ejecutando comando para duración del audio: {$duration_command}");
    $duration_in_seconds = shell_exec($duration_command);
    //guardarLog("Salida de ffprobe: '{$duration_in_seconds}'");

    // Limpiar y validar la duración del audio
    $duration_in_seconds = trim($duration_in_seconds);
    if (is_numeric($duration_in_seconds)) {
        $duration_in_seconds = (float)$duration_in_seconds;
        $duration_formatted = floor($duration_in_seconds / 60) . ':' . str_pad(floor($duration_in_seconds % 60), 2, '0', STR_PAD_LEFT);
        update_post_meta($post_id, "audio_duration_{$index}", $duration_formatted);
        //guardarLog("Duración del audio (formateada): {$duration_formatted}");
    } else {
        //guardarLog("Duración del audio no válida para el archivo {$nuevo_archivo_path_lite}");
    }

    //guardarLog("datos para sacar meta post id: {$post_id} path_lite: {$nuevo_archivo_path_lite} index: {$index}");

    // Llamar a analizarYGuardarMetasAudio solo si el índice es 1
    if ($index === 1) {
        analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index);
        //guardarLog("Se ha llamado a analizarYGuardarMetasAudio para el índice 1.");
    } else {
        //guardarLog("No se llamó a analizarYGuardarMetasAudio ya que el índice no es 1.");
    }
}

#Paso 5.6
function analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index, $nombre_archivo = null, $carpeta = null, $carpeta_abuela = null)
{
    $python_command = escapeshellcmd("python3 /var/www/wordpress/wp-content/themes/2upra3v/app/python/audio.py \"{$nuevo_archivo_path_lite}\"");
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

    // Construir la parte del prompt que contiene la información del archivo y las carpetas solo si no están vacías
    $informacion_archivo = '';
    if ($nombre_archivo) {
        $informacion_archivo .= "Archivo: '{$nombre_archivo}'\n";
    }
    if ($carpeta) {
        $informacion_archivo .= "Carpeta: '{$carpeta}'\n";
    }
    if ($carpeta_abuela) {
        $informacion_archivo .= "Carpeta abuela: '{$carpeta_abuela}'\n";
    }

    $tags_usuario = get_post_meta($post_id, 'tagsUsuario', true);
    $tags_usuario_texto = $tags_usuario ? (is_array($tags_usuario) ? implode(', ', $tags_usuario) : $tags_usuario) : '';

    // Formar el prompt final con los valores ya filtrados
    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción o lo acaba de publicar ahora mismo. "
        . "Ten en cuenta la descripcion, puede ser relevante. descripción:\"{$post_content}\". {$tags_usuario_texto}"
        . "{$informacion_archivo}"
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, estos son datos de ejemplo!!: El 'nombre_corto' es un nuevo nombre para el archivo, y la 'descripción corta' es para entender rápidamente qué es el audio, por favor, que sea corta pero sin perder detalles importantes. Con los artistas posible siempre piensa en uno o varios que tengan la vibra de la descripción que la gente pueda relacionar con el audio. No uses palabras como 'Repetitive', 'Energetic', 'Powerful' en la descripcion corta. Te incluyo la estructura JSON con datos de ejemplo, que son irrelevantes en este caso: "
        . '{"descripcion_ia":{"es":"(aquí iría una descripción tuya del audio muy detallada)", "en":"(aquí en inglés)"},'
        . '"instrumentos_principal":{"es":["Piano"], "en":["Piano"]},'
        . '"nombre_corto":{"es":["(maximo 3 palabras)"], "en":["Kick Vitagen"]},'
        . '"descripcion_corta":{"es":["(entre 4 a 6 palabras)"], "en":["(en ingles)"]},'
        . '"estado_animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"genero_posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"artista_posible":{"es":["Freddie Dredd", "Flume"], "en":["Freddie Dredd", "Flume"]},'
        . '"tipo_audio":{"es":["determina si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"tags_posibles":{"es":["Naturaleza", "phonk", "memphis", "oscuro"], "en":["Nature"]},'
        . '"sugerencia_busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . "Te dejo una guía interesante de tags que puedes usar, por favor, usa solo los que realmente describan el audio: "
        . "Tipo y Formato: Acoustic, Chord, Down Sweep/Fall, Dry, Harmony, Loop, Melody, Mixed, Monophonic, One Shot, Polyphonic, Processed, Progression, Riser/Sweep, Short, Wet. "
        . "Timbre y Tono: Bassy, Boomy, Breathy, Bright, Buzzy, Clean, Coarse/Harsh, Cold, Dark, Delicate, Detuned, Dissonant, Distorted, Exotic, Fat, Full, Glitchy, Granular, Gloomy, Hard, High, Hollow, Low, Metallic, Muffled, Muted, Narrow, Noisy, Round, Sharp, Shimmering, Sizzling, Smooth, Soft, Piercing, Thin, Tinny, Warm, Wide, Wooden. "
        . "Género: Ambient, Breaks, Chillout, Chiptune, Cinematic, Classical, Acid House, Deep House, Disco, Drum & Bass, Dubstep, Ethnic/World, Electro House, Electro, Electro Swing, Folk/Country, Funk/Soul, Jazz, Jungle, House, Hip Hop, Latin/Afro Cuban, Minimal House, Nu Disco, R&B, Reggae/Dub, Reggaeton, Rock, Pop, Progressive House, Synthwave, Tech House, Techno, Trance, Trap, Vocals, Phonk, Memphis. "
        . "Estilo y Técnica: Arpeggiated, Decaying, Echoing, Long Release, Legato, Glissando/Glide, Pad, Percussive, Pitch Bend, Plucked, Pulsating, Punchy, Randomized, Slow Attack, Sweep/Filter Mod, Staccato/Stabs, Stuttered/Gated, Straight, Sustained, Syncopated, Uptempo, Wobble, Vibrato. "
        . "Calidad y Tecnología: Analog, Compressed, Digital, Dynamic, Loud, Range, Female, Funky, Jazzy, Lo Fi, Male, Quiet, Vintage, Vinyl. "
        . "Estado de Ánimo: Aggressive, Angry, Bouncy, Calming, Carefree, Cheerful, Climactic, Cool, Dramatic, Elegant, Epic, Excited, Energetic, Fun, Futuristic, Gentle, Groovy, Happy, Haunting, Hypnotic, Industrial, Manic, Melancholic, Mellow, Mystical, Nervous, Passionate, Peaceful, Playful, Powerful, Rebellious, Reflective, Relaxing, Romantic, Rowdy, Sad, Sentimental, Sexy, Soothing, Sophisticated, Spacey, Suspenseful, Uplifting, Urgent, Weird."
        . " Es crucial determinar si es un loop, un one shot o un sample. Usa tags de una palabra y optimiza el SEO con sugerencias de búsqueda relevantes. Sé muy detallado sin perder precisión. Aunque te pido en español y en ingles, hay algunas palabras que son mejor mantenerlas en ingles cuando en español son muy frecuentes, por ejemplo, kick, snare, cowbell, etc. Ignora '/home/asley01/MEGA/Waw/Kits' no es relevante, el resto de la ruta si.";

    $descripcion = generarDescripcionIA($nuevo_archivo_path_lite, $prompt);

    if ($descripcion) {
        // Procesar el JSON eliminando caracteres innecesarios
        $descripcion_procesada = json_decode(trim($descripcion, "```json \n"), true);

        if ($descripcion_procesada) {
            // Corregir la estructura de 'descripcion_ia' solo si está mal estructurada
            if (isset($descripcion_procesada['descripcion_ia']['descripcion_ia'])) {
                $descripcion_procesada['descripcion_ia'] = [
                    'es' => $descripcion_procesada['descripcion_ia']['descripcion_ia']['es'] ?? '',
                    'en' => $descripcion_procesada['descripcion_ia']['descripcion_ia']['en'] ?? ''
                ];
            }

            // Definir el sufijo para los datos, según el índice
            $suffix = ($index == 1) ? '' : "_{$index}";

            // Asegurarse de que la clave 'descripcion_ia' esté bien estructurada
            if (isset($descripcion_procesada['descripcion_ia']) && is_array($descripcion_procesada['descripcion_ia'])) {
                // Crear los nuevos datos con la estructura correcta
                $nuevos_datos = [
                    'descripcion_ia' => [
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
                iaLog("Error: 'descripcion_ia' no está presente o tiene una estructura incorrecta en el JSON procesado.");
            }
        } else {
            iaLog("Error al procesar el JSON de la descripción generada por IA.");
        }
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

    // Actualizar los nuevos datos correctamente sin anidar 'descripcion_ia'
    $nuevos_datos_algoritmo = [
        'bpm' => $resultados['bpm'] ?? '',
        'emotion' => $resultados['emotion'] ?? '',
        'key' => $resultados['key'] ?? '',
        'scale' => $resultados['scale'] ?? '',
        // Solo pasamos directamente $nuevos_datos['descripcion_ia'] sin volver a procesarlo
        'descripcion_ia' => $nuevos_datos['descripcion_ia'],
        'instrumentos_posibles' => $nuevos_datos['instrumentos_posibles'],
        'estado_animo' => $nuevos_datos['estado_animo'],
        'artista_posible' => $nuevos_datos['artista_posible'],
        'genero_posible' => $nuevos_datos['genero_posible'],
        'tipo_audio' => $nuevos_datos['tipo_audio'],
        'tags_posibles' => $nuevos_datos['tags_posibles'],
        'sugerencia_busqueda' => $nuevos_datos['sugerencia_busqueda']
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



#Paso 6
function asignarTags($postId)
{
    if (!empty($_POST['Tags'])) {
        $tags = sanitize_text_field($_POST['Tags']);
        $tags_array = explode(',', $tags);
        wp_set_post_tags($postId, $tags_array, false);
    }
}
