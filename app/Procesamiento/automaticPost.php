<?


function procesarAudios() {
    $directorio_audios = '/home/asley01/MEGA/Waw/X';
    guardarLog("Iniciando procesamiento de audios en: {$directorio_audios}");
    
    $audios_para_procesar = buscarAudios($directorio_audios);

    if (!empty($audios_para_procesar)) {
        guardarLog("Cantidad de audios a procesar: " . count($audios_para_procesar));

        // Procesar solo el primer audio válido
        $audio_info = $audios_para_procesar[0];
        guardarLog("Iniciando procesamiento de audio: {$audio_info['ruta']}");
        autRevisarAudio($audio_info['ruta'], $audio_info['hash']);
        guardarLog("Procesado audio: {$audio_info['ruta']}");

        // Programar la siguiente ejecución solo si hay más audios
        // Eliminamos el procesamiento de múltiples audios para evitar sobrecarga
        wp_schedule_single_event(time() + 300, 'procesar_audio_cron_event');
        guardarLog("Evento programado para procesar audios en 5 minutos.");
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

function wp_get_attachment_url_by_path($file_path) {
    global $wpdb;
    $sql = $wpdb->prepare("
        SELECT guid FROM $wpdb->posts 
        WHERE guid LIKE %s 
        AND post_type = 'attachment'
    ", '%' . ltrim($file_path, '/'));
    
    return $wpdb->get_var($sql);
}

function debeProcesarse($ruta_archivo, $file_hash) {
    try {
        // Verificación de existencia del archivo
        if (!file_exists($ruta_archivo)) {
            error_log("Error: El archivo no existe: {$ruta_archivo}");
            return false;
        }

        // Obtener URL del adjunto por la ruta del archivo
        if (!function_exists('wp_get_attachment_url_by_path')) {
            throw new Exception("Función wp_get_attachment_url_by_path no está definida.");
        }

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




add_action('init', 'iniciar_cron_procesamiento_audios');
function iniciar_cron_procesamiento_audios() {
    if (!wp_next_scheduled('procesar_audio_cron_event')) {
        wp_schedule_event(time(), 'cada_cinco_minutos', 'procesar_audio_cron_event');
    }
}

add_filter('cron_schedules', 'cincoMinutos');
function cincoMinutos($schedules) {
    if (!isset($schedules['cada_cinco_minutos'])) {
        $schedules['cada_cinco_minutos'] = array(
            'interval' => 600, // 5 minutos en segundos
            'display'  => __('Cada 5 minutos')
        );
    }
    return $schedules;
}

add_action('procesar_audio_cron_event', 'procesarAudios');



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

function obtenerUrlPublica($file_path) {
    // Obtener información de los directorios de uploads de WordPress
    $uploads_dir = wp_upload_dir();
    $base_path = realpath($uploads_dir['basedir']);
    $file_path = realpath($file_path);

    // Verificar si el archivo está dentro del directorio de uploads
    if ($base_path === false || $file_path === false || strpos($file_path, $base_path) !== 0) {
        return false; // La ruta no está dentro del directorio de uploads
    }

    // Obtener la ruta relativa del archivo dentro de uploads
    $relative_path = ltrim(str_replace($base_path, '', $file_path), '/\\');

    // Construir y retornar la URL pública
    return trailingslashit($uploads_dir['baseurl']) . str_replace(DIRECTORY_SEPARATOR, '/', $relative_path);
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

    // 4. Renombrar archivo original
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

    // Obtener URL pública del archivo original
    $public_url_original = obtenerUrlPublica($nuevo_nombre_original); 
    if ($public_url_original === false) {
        guardarLog("No se pudo obtener URL pública para: $nuevo_nombre_original");
    }

    // Obtener ID del archivo por URL original
    $file_id = obtenerFileIDPorURL(obtenerUrlPublica($audio_path)); 

    if ($file_id !== false) {
        $actualizacion_exitosa = actualizarUrlArchivo($file_id, $public_url_original);
        if (!$actualizacion_exitosa) {
            guardarLog("Error al actualizar URL para File ID: $file_id");
        }
    } else {
        guardarLog("File ID no encontrado para el archivo original.");
    }

    // Enviar rutas a crearAutPost
    crearAutPost($nuevo_nombre_original, $nuevo_nombre_lite);
    guardarLog("Archivos enviados a crearAutPost.");
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

function adjuntarArchivoAut($archivo, $post_id) {

    if (!file_exists($archivo)) {
        return new WP_Error('archivo_no_encontrado', 'El archivo especificado no existe: ' . esc_html($archivo));
    }


    $wp_upload_dir = wp_upload_dir();
    $upload_path = $wp_upload_dir['path'];


    $filename = basename($archivo);
    $filename = sanitize_file_name($filename);
    $unique_filename = wp_unique_filename($wp_upload_dir['path'], $filename);
    $destino = trailingslashit($wp_upload_dir['path']) . $unique_filename;
    if (!copy($archivo, $destino)) {
        return new WP_Error('error_copia_archivo', 'No se pudo copiar el archivo al directorio de cargas.');
    }
    $filetype = wp_check_filetype($unique_filename, null);
    if (!$filetype['type']) {
        @unlink($destino);
        return new WP_Error('tipo_archivo_no_soportado', 'El tipo de archivo no es soportado: ' . esc_html($unique_filename));
    }

    $attachment = [
        'guid'           => trailingslashit($wp_upload_dir['url']) . $unique_filename,
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($unique_filename, PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment($attachment, $destino, $post_id);

    if (is_wp_error($attach_id)) {
        @unlink($destino);
        return $attach_id;
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $destino);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}





