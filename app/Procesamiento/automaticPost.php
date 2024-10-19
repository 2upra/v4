<?

add_action('init', 'iniciar_cron_procesamiento_audios');
function iniciar_cron_procesamiento_audios() {
    if (!wp_next_scheduled('procesar_audio1_cron_event')) {
        wp_schedule_event(time(), 'cadaDosMinutos', 'procesar_audio1_cron_event');
        guardarLog("Cron de procesamiento de audios programado para cada 2 minutos.");
    }
}

add_filter('cron_schedules', 'definir_cron_cada_dos_minutos');
function definir_cron_cada_dos_minutos($schedules) {
    if (!isset($schedules['cadaDosMinutos'])) {
        $schedules['cadaDosMinutos'] = array(
            'interval' => 120, // 120 segundos = 2 minutos
            'display'  => __('Cada 2 minutos')
        );
    }
    return $schedules;
}

add_action('procesar_audio1_cron_event', 'procesarAudios');

function procesarAudios() {
    $directorio_audios = '/home/asley01/MEGA/Waw/X';
    guardarLog("Iniciando procesamiento de audios en: {$directorio_audios}");
    
    $audios_para_procesar = buscarAudios($directorio_audios);

    if (!empty($audios_para_procesar)) {
        guardarLog("Cantidad de audios a procesar: " . count($audios_para_procesar));

        // Procesar solo el primer audio válido
        $audio_info = $audios_para_procesar[0];
        guardarLog("Iniciando procesamiento de audio: {$audio_info['ruta']}");
        
        // Asegúrate de que autRevisarAudio maneja correctamente errores y actualiza el estado del audio.
        $procesado = autRevisarAudio($audio_info['ruta'], $audio_info['hash']);
        
        if ($procesado) {
            guardarLog("Procesado correctamente el audio: {$audio_info['ruta']}");
        } else {
            guardarLog("Error al procesar el audio: {$audio_info['ruta']}");
        }

        // No es necesario programar otro evento aquí, ya que el cron recurrente se encarga de esto.
    } else {
        guardarLog("No se encontraron audios para procesar en: {$directorio_audios}");
    }
}

function buscarAudios($directorio) {
    // Solo mantenemos logs críticos
    if (!is_dir($directorio)) {
        guardarLog("Error: El directorio no existe o no es accesible: {$directorio}");
        return [];
    }

    if (!is_readable($directorio)) {
        guardarLog("Error: No se puede leer el directorio: {$directorio}");
        return [];
    }

    try {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio));
    } catch (Exception $e) {
        error_log("Excepción al crear el iterador de directorios: " . $e->getMessage());
        return [];
    }

    $audios_para_procesar = [];
    $extensiones_permitidas = ['wav', 'mp3'];

    foreach ($iterator as $archivo) {
        if ($archivo->isFile()) {
            $ruta_archivo = $archivo->getPathname();
            $extension = strtolower(pathinfo($ruta_archivo, PATHINFO_EXTENSION));
            
            // Verificar si la extensión está permitida
            if (!in_array($extension, $extensiones_permitidas)) {
                continue; // Saltar archivos que no sean wav o mp3
            }

            // Verificar si el archivo es legible
            if (!is_readable($ruta_archivo)) {
                error_log("Archivo no legible (sin permisos): {$ruta_archivo}");
                continue;
            }

            $file_hash = hash_file('sha256', $ruta_archivo);
            if (!$file_hash) {
                error_log("Error al generar el hash para el archivo: " . $ruta_archivo);
                continue;
            }

            if (debeProcesarse($ruta_archivo, $file_hash)) {
                $audios_para_procesar[] = [
                    'ruta' => $ruta_archivo,
                    'hash' => $file_hash
                ];

                // Solo añadimos el primer audio válido
                break;
            }
        }
    }

    return $audios_para_procesar;
}

function debeProcesarse($ruta_archivo, $file_hash) {
    try {
        // Verificación de existencia del archivo
        if (!file_exists($ruta_archivo)) {
            error_log("Error: El archivo no existe: {$ruta_archivo}");
            return false;
        }

        // Obtener URL del adjunto por la ruta del archivo
        $attachment_url = wp_get_attachment_url_by_path($ruta_archivo);
        if ($attachment_url) {
            // Buscar si ya existe el adjunto en WordPress
            $existing_attachment = attachment_url_to_postid($attachment_url);
            if ($existing_attachment) {
                return false;
            }
        }

        // Verificar si existe el hash
        if (!$file_hash) {
            error_log("Error: Hash inexistente para el archivo: " . $ruta_archivo);
            return false;
        }

        $hash_exists = obtenerHash($file_hash);
        if ($hash_exists) {
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("Excepción en debeProcesarse: " . $e->getMessage());
        return false;
    }
}

function wp_get_attachment_url_by_path($file_path) {
    global $wpdb;
    $sql = $wpdb->prepare("
        SELECT guid FROM $wpdb->posts 
        WHERE guid LIKE %s 
        AND post_type = 'attachment'
    ", '%' . ltrim($file_path, '/'));
    
    return $wpdb->get_var($sql);
}


function autRevisarAudio($audio, $file_hash) {
    // Verificar si el archivo existe
    if (!file_exists($audio)) {
        guardarLog("Archivo de audio no encontrado: {$audio}");
        error_log("El archivo de audio no existe: " . $audio);
        return;
    }
    guardarLog("Archivo de audio encontrado: {$audio}");
    
    // Obtener información del directorio de subidas
    $upload_dir = wp_upload_dir();
    $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $audio);
    guardarLog("URL del archivo de audio: {$file_url}");
    
    $user_id = 44; // ID del usuario que sube el archivo
    
    // Guardar el hash en la base de datos
    $hash_id = guardarHash($file_hash, $file_url, 'confirmed', $user_id);
    if (!$hash_id) {
        guardarLog("Error al guardar el hash en DB para: {$audio}");
        error_log("Error al guardar el hash en la base de datos para el archivo: " . $audio);
        return;
    }
    guardarLog("Hash guardado exitosamente para: {$audio} con ID: {$hash_id}");
    
    // Procesar el audio
    autProcesarAudio($audio);
    guardarLog("Procesamiento iniciado para: {$audio}");
}

function autProcesarAudio($audio_path) {
    // Verificar si el archivo existe
    if (!file_exists($audio_path)) {
        guardarLog("Archivo no encontrado: $audio_path");
        return;
    }

    // Obtener partes del path
    $path_parts = pathinfo($audio_path);
    $directory = realpath($path_parts['dirname']);
    if ($directory === false) {
        guardarLog("Directorio inválido: {$path_parts['dirname']}");
        return;
    }
    $extension = strtolower($path_parts['extension']);
    $basename = $path_parts['filename'];

    // Obtener ID del archivo por la ruta directa
    $file_id = obtenerFileIDPorURL($audio_path);
    if ($file_id === false) {
        guardarLog("File ID no encontrado para la ruta: $audio_path");
        return;
    } else {
        guardarLog("File ID obtenido: $file_id");
    }

    // Ruta temporal para eliminar metadatos
    $temp_path = "$directory/{$basename}_temp.$extension";

    // 1. Eliminar metadatos con ffmpeg
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c copy " . escapeshellarg($temp_path) . " -y";
    guardarLog("Strip metadata: $comando_strip_metadata");
    exec($comando_strip_metadata, $output_strip, $return_strip);
    if ($return_strip !== 0) {
        guardarLog("Error strip metadata: " . implode(" | ", $output_strip));
        return;
    }

    // Reemplazar archivo original
    if (!rename($temp_path, $audio_path)) {
        guardarLog("No se pudo reemplazar el archivo original.");
        return;
    }
    guardarLog("Metadatos eliminados.");

    // 2. Crear versión lite en MP3 a 128 kbps
    $lite_path = "$directory/{$basename}_lite.mp3";
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k " . escapeshellarg($lite_path) . " -y";
    guardarLog("Crear lite: $comando_lite");
    exec($comando_lite, $output_lite, $return_lite);
    if ($return_lite !== 0) {
        guardarLog("Error crear lite: " . implode(" | ", $output_lite));
        return;
    }
    guardarLog("Versión lite creada.");

    // 3. Obtener nombre limpio por IA
    $nombre_limpio = generarNombreAudio($lite_path);
    if (empty($nombre_limpio)) {
        guardarLog("Nombre limpio inválido.");
        return;
    }
    guardarLog("Nombre limpio: $nombre_limpio");

    // 4. Renombrar archivo original en su ubicación actual
    $nuevo_nombre_original = "$directory/$nombre_limpio.$extension";
    if (!rename($audio_path, $nuevo_nombre_original)) {
        guardarLog("No se pudo renombrar archivo original.");
        return;
    }
    guardarLog("Archivo renombrado: $nuevo_nombre_original");

    // 5. Renombrar archivo lite
    $nuevo_nombre_lite = "$directory/{$nombre_limpio}_lite.mp3";
    if (!rename($lite_path, $nuevo_nombre_lite)) {
        guardarLog("No se pudo renombrar archivo lite.");
        return;
    }
    guardarLog("Archivo lite renombrado: $nuevo_nombre_lite");

    // 6. Mover únicamente el archivo lite al directorio de uploads
    $uploads_dir = wp_upload_dir();
    $target_dir_audio = trailingslashit($uploads_dir['basedir']) . "audio/";

    // Crear directorio 'audio' si no existe
    if (!file_exists($target_dir_audio)) {
        if (!wp_mkdir_p($target_dir_audio)) {
            guardarLog("No se pudo crear el directorio de uploads/audio.");
            return;
        }
    }

    $target_path_lite = $target_dir_audio . "{$nombre_limpio}_lite.mp3";

    // Mover archivo lite
    if (!rename($nuevo_nombre_lite, $target_path_lite)) {
        guardarLog("No se pudo mover el archivo lite al directorio de uploads.");
        return;
    }
    guardarLog("Archivo lite movido a uploads: $target_path_lite");

    // 7. Actualizar la ruta del archivo original en la base de datos
    if ($file_id !== false) {
        // Obtener la nueva ruta del archivo original después de renombrar
        $new_file_url = dirname($audio_path) . "/$nombre_limpio.$extension";

        // Actualizar la ruta en la base de datos
        $actualizacion_exitosa = actualizarUrlArchivo($file_id, $new_file_url);
        if ($actualizacion_exitosa) {
            guardarLog("Ruta actualizada correctamente para File ID: $file_id a $new_file_url");
        } else {
            guardarLog("Error al actualizar ruta para File ID: $file_id");
        }
    }

    // 8. Enviar rutas a crearAutPost
    crearAutPost($nuevo_nombre_original, $target_path_lite);
    guardarLog("Archivos enviados a crearAutPost.");
}

function generarNombreAudio($audio_path_lite)
{
    // Verificar que el archivo de audio exista
    if (!file_exists($audio_path_lite)) {
        iaLog("El archivo de audio no existe en la ruta especificada: {$audio_path_lite}");
        return null;
    }

    $prompt = "Escucha este audio y por favor, genera un nombre corto que lo represente. Por lo general son samples, si es un kick, un snare, un sample vintage, fx, cosas así. Simplemente genera un nombre corto de audio (no agregues más información adicional ni comentes nada adicional, solo entrega un nombre corto de audio), te dare unos ejemplos, lo esencial es por ejemplo identificar el instrumento dominante, o si es un sample poner, sample melancolico, identificar cosas clave como una emocion dominante, un instrumento, un sonido, una vibra, etc.";
    $nombre_generado = generarDescripcionIA($audio_path_lite, $prompt);

    // Verificar si se obtuvo una respuesta
    if ($nombre_generado) {
        // Limpiar la respuesta obtenida (eliminar espacios en blanco al inicio y al final)
        $nombre_generado_limpio = trim($nombre_generado);
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- ]/', '', $nombre_generado_limpio);
        $nombre_generado_limpio = substr($nombre_generado_limpio, 0, 50); // Limitar a 50 caracteres

        return $nombre_generado_limpio;
    } else {
        iaLog("No se recibió una respuesta válida de la IA para el archivo de audio: {$audio_path_lite}");
        return null;
    }
}

function crearAutPost($nuevo_nombre_original, $nuevo_nombre_lite) {

    $autor_id = 44;
    $prompt = "Genera una descripción corta para el siguiente archivo de audio. Puede ser un sample, un fx, un loop, un sonido de un kick, puede ser cualquier cosa, el propósito es que la descripción sea corta (solo responde con la descripción, no digas nada adicional); te doy ejemplos: Sample oscuro phonk, Fx de explosión, kick de house, sonido de sintetizador, piano melodía, guitarra acústica sample.";
    
    $descripcion = generarDescripcionIA($nuevo_nombre_lite, $prompt);

    if (is_wp_error($descripcion) || empty($descripcion)) {
        return new WP_Error('descripcion_generacion_fallida', 'No se pudo generar una descripción para el audio.');
    }

    $titulo = mb_substr($descripcion, 0, 30);
    $contenido = $descripcion;
    $post_data = [
        'post_title'    => $titulo,
        'post_content'  => $contenido,
        'post_status'   => 'publish',
        'post_author'   => $autor_id,
        'post_type'     => 'social_post',
    ];

    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        return $post_id;
    }
    $index = 1;

    analizarYGuardarMetasAudio($post_id, $nuevo_nombre_lite, $index);

    $audio_original_id = adjuntarArchivoAut($nuevo_nombre_original, $post_id);
    if (is_wp_error($audio_original_id)) {
        wp_delete_post($post_id, true);
        return $audio_original_id;
    }

    $audio_lite_id = adjuntarArchivoAut($nuevo_nombre_lite, $post_id);
    if (is_wp_error($audio_lite_id)) {
        return $audio_lite_id;
    }

    update_post_meta($post_id, 'post_audio', $audio_original_id);
    update_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
    update_post_meta($post_id, 'postAut', 'true');

    return $post_id;
}


/**
 * Adjunta un archivo a un post de WordPress.
 *
 * Esta función soporta:
 * - Rutas absolutas de archivos en el servidor.
 * - URLs externas.
 * - Rutas dentro del directorio de uploads de WordPress.
 *
 * @param string $archivo Ruta o URL del archivo a adjuntar.
 * @param int    $post_id ID del post al que se adjuntará el archivo.
 *
 * @return int|WP_Error ID de adjunto en caso de éxito, WP_Error en caso de fallo.
 */
function adjuntarArchivoAut($archivo, $post_id) {
    
    // Variables para manejo de archivos temporales
    $es_url = filter_var($archivo, FILTER_VALIDATE_URL);
    $archivo_temp = '';
    
    if ($es_url) {
        // Descargar el archivo desde la URL a una ubicación temporal
        $temp_dir = sys_get_temp_dir();
        $nombre_archivo = basename(parse_url($archivo, PHP_URL_PATH));
        $archivo_temp = tempnam($temp_dir, 'upload_') . '_' . sanitize_file_name($nombre_archivo);
        
        $contenido = @file_get_contents($archivo);
        if ($contenido === false) {
            return new WP_Error('error_descarga', 'No se pudo descargar el archivo desde la URL: ' . esc_html($archivo));
        }
        
        // Guardar el contenido descargado en el archivo temporal
        if (file_put_contents($archivo_temp, $contenido) === false) {
            return new WP_Error('error_guardar_temporal', 'No se pudo guardar el archivo descargado en: ' . esc_html($archivo_temp));
        }
        
        $archivo_procesar = $archivo_temp;
    } else {
        // Asumir que es una ruta de archivo local
        $archivo_procesar = $archivo;
    }
    
    // Verificar si el archivo existe
    if (!file_exists($archivo_procesar)) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        return new WP_Error('archivo_no_encontrado', 'El archivo especificado no existe: ' . esc_html($archivo));
    }
    
    // Obtener información de la ruta de uploads
    $wp_upload_dir = wp_upload_dir();
    
    // Determinar si el archivo está dentro del directorio de uploads
    $ruta_relativa = '';
    if (strpos(realpath($archivo_procesar), realpath($wp_upload_dir['basedir'])) === 0) {
        // Archivo está dentro de uploads
        $ruta_relativa = str_replace(realpath($wp_upload_dir['basedir']) . DIRECTORY_SEPARATOR, '', realpath($archivo_procesar));
    }
    
    // Si el archivo no está en uploads, copiarlo al directorio de uploads
    if (empty($ruta_relativa)) {
        $filename = basename($archivo_procesar);
        $filename = sanitize_file_name($filename);
        $unique_filename = wp_unique_filename($wp_upload_dir['path'], $filename);
        $destino = trailingslashit($wp_upload_dir['path']) . $unique_filename;
        
        if (!copy($archivo_procesar, $destino)) {
            // Eliminar el archivo temporal si existe
            if ($es_url && file_exists($archivo_temp)) {
                @unlink($archivo_temp);
            }
            return new WP_Error('error_copia_archivo', 'No se pudo copiar el archivo al directorio de cargas.');
        }
    } else {
        // El archivo ya está en uploads
        $unique_filename = basename($archivo_procesar);
        $destino = $archivo_procesar;
    }
    
    // Obtener el tipo de archivo
    $filetype = wp_check_filetype($unique_filename, null);
    if (!$filetype['type']) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        // Si el archivo fue copiado a uploads, eliminarlo
        if (!empty($ruta_relativa)) {
            @unlink($destino);
        }
        return new WP_Error('tipo_archivo_no_soportado', 'El tipo de archivo no es soportado: ' . esc_html($unique_filename));
    }
    
    // Preparar los datos del adjunto
    $attachment = [
        'guid'           => $wp_upload_dir['url'] . '/' . $unique_filename,
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($unique_filename, PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];
    
    // Insertar el adjunto en la base de datos
    $attach_id = wp_insert_attachment($attachment, $destino, $post_id);
    
    if (is_wp_error($attach_id)) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        // Si el archivo fue copiado a uploads, eliminarlo
        if (!empty($ruta_relativa)) {
            @unlink($destino);
        }
        return $attach_id;
    }
    
    // Generar y actualizar los metadatos del adjunto
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $destino);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // Eliminar el archivo temporal si existe
    if ($es_url && file_exists($archivo_temp)) {
        @unlink($archivo_temp);
    }
    
    return $attach_id;
}


