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
            'interval' => 180, // 120 segundos = 2 minutos
            'display'  => __('Cada 2 minutos')
        );
    }
    return $schedules;
}

add_action('procesar_audio1_cron_event', 'procesarAudios');


/*
2024-10-19 05:10:38 - [procesarAudios] Iniciando procesamiento de audios en: /home/asley01/MEGA/Waw/X

2024-10-19 05:10:38 - Iniciando verificación de carga para File ID: 8236 con URL: /home/asley01/MEGA/Waw/X/♥️ Especial/Drum/Distorted Kick_lite.mp3
2024-10-19 05:10:38 - Error al cargar el archivo con File ID: 8236. Código HTTP: 0

2024-10-19 05:10:38 - [debeProcesarse] Resumen: Archivo encontrado: /home/asley01/MEGA/Waw/X/♥️ Especial/Drum/Kick Dusty.mp3; Hash recibido: 9f80f5a5c4a05875402eb0689b7e0e9855121eb397899cd6af86f3a9400d2b46; El hash ya existe en la base de datos.; El archivo no ha sido cargado correctamente, continuar con el procesamiento.; El archivo y hash son válidos para el procesamiento.

2024-10-19 05:10:38 - [buscarAudios] Audio válido encontrado: /home/asley01/MEGA/Waw/X/♥️ Especial/Drum/Kick Dusty.mp3 con hash: 9f80f5a5c4a05875402eb0689b7e0e9855121eb397899cd6af86f3a9400d2b46

2024-10-19 05:10:38 - [buscarAudios] Total de audios encontrados para procesar: 1
2024-10-19 05:10:38 - [procesarAudios] Cantidad de audios a procesar: 1
2024-10-19 05:10:38 - [procesarAudios] Iniciando procesamiento de audio: /home/asley01/MEGA/Waw/X/♥️ Especial/Drum/Kick Dusty.mp3

2024-10-19 05:10:38 - [autRevisarAudio] Archivo de audio encontrado: /home/asley01/MEGA/Waw/X/♥️ Especial/Drum/Kick Dusty.mp3
2024-10-19 05:10:38 - [autRevisarAudio] URL del archivo de audio generado: /home/asley01/MEGA/Waw/X/♥️ Especial/Drum/Kick Dusty.mp3
2024-10-19 05:10:38 - [autRevisarAudio] ID del usuario que sube el archivo: 44
2024-10-19 05:10:38 - [autRevisarAudio] Error: No se pudo guardar el hash en la base de datos para el archivo: /home/asley01/MEGA/Waw/X/♥️ Especial/Drum/Kick Dusty.mp3

*/

// ETAPA 1 - BUSCAR AUDIO 
//////////////////////////////////////////////////////////////////////////////

function procesarAudios() {
    $func_name = __FUNCTION__; // Nombre de la función para los logs
    $directorio_audios = '/home/asley01/MEGA/Waw/X';
    guardarLog("[$func_name] Iniciando procesamiento de audios en: {$directorio_audios}");

    // Implementar bloqueo para prevenir ejecuciones simultáneas
    $lock_file = '/tmp/procesar_audios.lock';
    $fp = fopen($lock_file, 'c');
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        guardarLog("[$func_name] Otro proceso de procesarAudios está en ejecución.");
        return;
    }

    $audios_para_procesar = buscarAudios($directorio_audios);

    if (!empty($audios_para_procesar)) {
        guardarLog("[$func_name] Cantidad de audios a procesar: " . count($audios_para_procesar));

        // Procesar solo el primer audio válido
        $audio_info = $audios_para_procesar[0];
        guardarLog("[$func_name] Iniciando procesamiento de audio: {$audio_info['ruta']}");
        // Procesar el audio
        autRevisarAudio($audio_info['ruta'], $audio_info['hash']);

    } else {
        guardarLog("[$func_name] No se encontraron audios para procesar en: {$directorio_audios}");
    }

    // Liberar el bloqueo
    flock($fp, LOCK_UN);
    fclose($fp);
    unlink($lock_file);
}

function buscarAudios($directorio) {
    $func_name = __FUNCTION__; // Nombre de la función para los logs

    // Solo mantenemos logs críticos
    if (!is_dir($directorio)) {
        guardarLog("[$func_name] Error: El directorio no existe o no es accesible: {$directorio}");
        return [];
    }

    if (!is_readable($directorio)) {
        guardarLog("[$func_name] Error: No se puede leer el directorio: {$directorio}");
        return [];
    }

    try {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio));
    } catch (Exception $e) {
        guardarLog("[$func_name] Excepción al crear el iterador de directorios: " . $e->getMessage());
        error_log("[$func_name] Excepción al crear el iterador de directorios: " . $e->getMessage());
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
                guardarLog("[$func_name] Archivo ignorado (extensión no permitida): {$ruta_archivo}");
                continue; // Saltar archivos que no sean wav o mp3
            }

            // Verificar si el archivo es legible
            if (!is_readable($ruta_archivo)) {
                guardarLog("[$func_name] Archivo no legible (sin permisos): {$ruta_archivo}");
                error_log("[$func_name] Archivo no legible (sin permisos): {$ruta_archivo}");
                continue;
            }

            $file_hash = hash_file('sha256', $ruta_archivo);
            if (!$file_hash) {
                guardarLog("[$func_name] Error al generar el hash para el archivo: {$ruta_archivo}");
                error_log("[$func_name] Error al generar el hash para el archivo: {$ruta_archivo}");
                continue;
            }

            if (debeProcesarse($ruta_archivo, $file_hash)) {
                guardarLog("[$func_name] Audio válido encontrado: {$ruta_archivo} con hash: {$file_hash}");
                $audios_para_procesar[] = [
                    'ruta' => $ruta_archivo,
                    'hash' => $file_hash
                ];

                // Solo añadimos el primer audio válido
                break;
            } else {
                guardarLog("[$func_name] Audio no válido para procesamiento: {$ruta_archivo}");
            }
        }
    }

    guardarLog("[$func_name] Total de audios encontrados para procesar: " . count($audios_para_procesar));
    return $audios_para_procesar;
}


function debeProcesarse($ruta_archivo, $file_hash) {
    $func_name = __FUNCTION__; // Nombre de la función para los logs
    $resumen_logs = []; // Arreglo para acumular los mensajes del log
    
    try {
        // Verificación de existencia del archivo
        if (!file_exists($ruta_archivo)) {
            $resumen_logs[] = "Error: El archivo no existe: {$ruta_archivo}";
            error_log("[$func_name] Error: El archivo no existe: {$ruta_archivo}");
            guardarLogResumen($func_name, $resumen_logs); // Guardar resumen
            return false;
        }
        $resumen_logs[] = "Archivo encontrado: {$ruta_archivo}";
        
        // Verificar si existe el hash
        if (!$file_hash) {
            $resumen_logs[] = "Error: Hash inexistente para el archivo: {$ruta_archivo}";
            error_log("[$func_name] Error: Hash inexistente para el archivo: {$ruta_archivo}");
            guardarLogResumen($func_name, $resumen_logs); // Guardar resumen
            return false;
        }
        $resumen_logs[] = "Hash recibido: {$file_hash}";

        $hash_exists = obtenerHash($file_hash);
        if ($hash_exists) {
            $resumen_logs[] = "El hash ya existe en la base de datos.";

            // Verificar si el archivo ya ha sido cargado
            if (verificarCargaArchivoPorHash($file_hash)) {
                $resumen_logs[] = "El archivo con hash {$file_hash} ya ha sido cargado, no es necesario procesarlo.";
                guardarLogResumen($func_name, $resumen_logs); // Guardar resumen
                return false; // Detener el procesamiento
            }
            $resumen_logs[] = "El archivo no ha sido cargado correctamente, continuar con el procesamiento.";
        }

        $resumen_logs[] = "El archivo y hash son válidos para el procesamiento.";
        guardarLogResumen($func_name, $resumen_logs); // Guardar resumen
        return true;
    } catch (Exception $e) {
        $resumen_logs[] = "Excepción capturada: " . $e->getMessage();
        error_log("[$func_name] Excepción capturada: " . $e->getMessage());
        guardarLogResumen($func_name, $resumen_logs); // Guardar resumen
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



function guardarLogResumen($func_name, $resumen_logs) {
    $resumen = implode("; ", $resumen_logs);
    guardarLog("[$func_name] Resumen: " . $resumen);
}



function autRevisarAudio($audio, $file_hash) {
    $func_name = __FUNCTION__; // Nombre de la función actual para los logs
    
    // Verificar si el archivo existe
    if (!file_exists($audio)) {
        guardarLog("[$func_name] Error: Archivo de audio no encontrado: {$audio}");
        error_log("[$func_name] Error: El archivo de audio no existe: " . $audio);
        return;
    }
    guardarLog("[$func_name] Archivo de audio encontrado: {$audio}");
    
    // Obtener información del directorio de subidas
    $upload_dir = wp_upload_dir();
    $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $audio);
    guardarLog("[$func_name] URL del archivo de audio generado: {$file_url}");
    
    $user_id = 44; // ID del usuario que sube el archivo
    guardarLog("[$func_name] ID del usuario que sube el archivo: {$user_id}");
    
    // Guardar el hash en la base de datos
    $hash_id = guardarHash($file_hash, $file_url, 'confirmed', $user_id);
    if (!$hash_id) {
        guardarLog("[$func_name] Error: No se pudo guardar el hash en la base de datos para el archivo: {$audio}");
        error_log("[$func_name] Error: No se pudo guardar el hash en la base de datos para el archivo: " . $audio);
        return;
    }
    guardarLog("[$func_name] Hash guardado exitosamente para: {$audio} con ID de hash: {$hash_id}");
    
    // Procesar el audio
    guardarLog("[$func_name] Iniciando procesamiento del audio: {$audio}");
    $resultado = autProcesarAudio($audio);
    
    // Verificar si el procesamiento fue exitoso
    if ($resultado === false) {
        guardarLog("[$func_name] Error: Fallo en el procesamiento del audio: {$audio}");
        error_log("[$func_name] Error: El procesamiento del audio ha fallado para: " . $audio);
    } else {
        guardarLog("[$func_name] Procesamiento completado exitosamente para: {$audio}");
    }
}

// ETAPA 2 - PROCESAR EL AUDIO ENCONTRADO
//////////////////////////////////////////////////////////////////////////////

function autProcesarAudio($audio_path) {
    guardarLog("--Inicio de la función autProcesarAudio.--");

    // Verificar si el archivo existe
    if (!file_exists($audio_path)) {
        guardarLog("Archivo no encontrado: $audio_path");
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }

    // Obtener partes del path
    $path_parts = pathinfo($audio_path);
    $directory = realpath($path_parts['dirname']);
    if ($directory === false) {
        guardarLog("Directorio inválido: {$path_parts['dirname']}");
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }
    $extension = strtolower($path_parts['extension']);
    $basename = $path_parts['filename'];

    guardarLog("Ruta inicial: $audio_path, Directorio: $directory, Basename: $basename, Extensión: $extension");

    // Obtener ID del archivo por la ruta directa
    $file_id = obtenerFileIDPorURL($audio_path);
    if ($file_id === false) {
        guardarLog("File ID no encontrado para la ruta: $audio_path");
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    } else {
        guardarLog("File ID obtenido: $file_id");
    }

    // Ruta temporal para eliminar metadatos
    $temp_path = "$directory/{$basename}_temp.$extension";

    // 1. Eliminar metadatos con ffmpeg
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c copy " . escapeshellarg($temp_path) . " -y";
    guardarLog("Comando para eliminar metadatos: $comando_strip_metadata");
    exec($comando_strip_metadata, $output_strip, $return_strip);
    if ($return_strip !== 0) {
        guardarLog("Error al eliminar metadatos: " . implode(" | ", $output_strip));
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }

    // Reemplazar archivo original
    if (!rename($temp_path, $audio_path)) {
        guardarLog("No se pudo reemplazar el archivo original.");
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }
    guardarLog("Metadatos eliminados del archivo: $audio_path");

    // 2. Crear versión lite en MP3 a 128 kbps
    $lite_path = "$directory/{$basename}_lite.mp3";
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k " . escapeshellarg($lite_path) . " -y";
    guardarLog("Comando para crear versión lite: $comando_lite");
    exec($comando_lite, $output_lite, $return_lite);
    if ($return_lite !== 0) {
        guardarLog("Error al crear versión lite: " . implode(" | ", $output_lite));
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }
    guardarLog("Versión lite creada: $lite_path");

    // 3. Obtener nombre limpio por IA
    $nombre_limpio = generarNombreAudio($lite_path);
    if (empty($nombre_limpio)) {
        guardarLog("Nombre limpio inválido.");
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }
    guardarLog("Nombre limpio generado: $nombre_limpio");

    // 4. Renombrar archivo original
    $nuevo_nombre_original = "$directory/$nombre_limpio.$extension";
    if (!rename($audio_path, $nuevo_nombre_original)) {
        guardarLog("No se pudo renombrar el archivo original.");
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }
    guardarLog("Archivo original renombrado: $nuevo_nombre_original");

    // 5. Renombrar archivo lite
    $nuevo_nombre_lite = "$directory/{$nombre_limpio}_lite.mp3";
    if (!rename($lite_path, $nuevo_nombre_lite)) {
        guardarLog("No se pudo renombrar el archivo lite.");
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }
    guardarLog("Archivo lite renombrado: $nuevo_nombre_lite");

    // 6. Mover el archivo lite al directorio de uploads
    $uploads_dir = wp_upload_dir();
    $target_dir_audio = trailingslashit($uploads_dir['basedir']) . "audio/";

    // Crear directorio 'audio' si no existe
    if (!file_exists($target_dir_audio)) {
        if (!wp_mkdir_p($target_dir_audio)) {
            guardarLog("No se pudo crear el directorio de uploads/audio.");
            guardarLog("Fin de la función autProcesarAudio.");
            return;
        }
    }

    $target_path_lite = $target_dir_audio . "{$nombre_limpio}_lite.mp3";

    // Mover archivo lite
    if (!rename($nuevo_nombre_lite, $target_path_lite)) {
        guardarLog("No se pudo mover el archivo lite al directorio de uploads.");
        guardarLog("Fin de la función autProcesarAudio.");
        return;
    }
    guardarLog("Archivo lite movido al directorio de uploads: $target_path_lite");


    // 7. Enviar rutas a crearAutPost
    guardarLog("Enviando rutas a crearAutPost: Original - $nuevo_nombre_original, Lite - $target_path_lite");
    crearAutPost($nuevo_nombre_original, $target_path_lite, $file_id);
    guardarLog("Archivos enviados a crearAutPost.");

    guardarLog("--Fin de la función autProcesarAudio.--");
}

/*

Error: Respuesta inesperada de la API. Detalles: {«candidates»:[{«finishReason»:»SAFETY»,»index»:0,»safetyRatings»:[{«category»:»HARM_CATEGORY_SEXUALLY_EXPLICIT»,»probability»:»NEGLIGIBLE»},{«category»:»HARM_CATEGORY_HATE_SPEECH»,»probability»:»NEGLIGIBLE»},{«category»:»HARM_CATEGORY_HARASSMENT»,»probability»:»NEGLIGIBLE»},{«category»:»HARM_CATEGORY_DANGEROUS_CONTENT»,»probability»:»MEDIUM»}]}],»usageMetadata»:{«promptTokenCount»:151,»totalTokenCount»:151}}

*/


function generarNombreAudio($audio_path_lite)
{
    // Verificar que el archivo de audio exista
    if (!file_exists($audio_path_lite)) {
        iaLog("El archivo de audio no existe en la ruta especificada: {$audio_path_lite}");
        return null;
    }

    // Obtener el nombre del archivo a partir de la ruta
    $nombre_archivo = pathinfo($audio_path_lite, PATHINFO_FILENAME);

    // Prompt para la IA con el nombre del archivo incluido
    $prompt = "El archivo se llama '{$nombre_archivo}' te lo enseño para lo tomes en cuenta, a veces tendra sentido el nombre a veces no, pero es importante tenerlo en cuenta, a veces vienen con nombres de marcas, paginas, etc, hay que ignorar eso. Escucha este audio y por favor, genera un nombre corto que lo represente. Por lo general son samples, como un kick, snare, sample vintage, o efectos (FX). Identifica el instrumento dominante o la emoción clave, por ejemplo, 'sample melancólico' o 'snare agresivo'. Entrega solo un nombre corto y descriptivo que represente el audio.";

    // Generar el nombre usando la IA
    $nombre_generado = generarDescripcionIA($audio_path_lite, $prompt);

    // Verificar si se obtuvo una respuesta
    if ($nombre_generado) {
        // Limpiar la respuesta obtenida (eliminar espacios en blanco al inicio y al final)
        $nombre_generado_limpio = trim($nombre_generado);
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- ]/', '', $nombre_generado_limpio);
        
        // Limitar el nombre a 35 caracteres
        $nombre_generado_limpio = substr($nombre_generado_limpio, 0, 35);
        
        // Añadir el identificador único '2upra_' al inicio
        $nombre_final = '2upra_' . $nombre_generado_limpio;

        // Asegurarse de que el nombre completo no exceda los 35 caracteres
        $nombre_final = substr($nombre_final, 0, 35);

        return $nombre_final;
    } else {
        iaLog("No se recibió una respuesta válida de la IA para el archivo de audio: {$audio_path_lite}");
        return null;
    }
}



function crearAutPost($nuevo_nombre_original, $nuevo_nombre_lite, $file_id) {

    $autor_id = 44;
    $prompt = "Genera una descripción corta para el siguiente archivo de audio. Puede ser un sample, un fx, un loop, un sonido de un kick, puede ser cualquier cosa, el propósito es que la descripción sea corta (solo responde con la descripción, no digas nada adicional); te doy ejemplos: Sample oscuro phonk, Fx de explosión, kick de house, sonido de sintetizador, piano melodía, guitarra acústica sample.";
    
    $descripcion = generarDescripcionIA($nuevo_nombre_lite, $prompt);

    if (is_wp_error($descripcion) || empty($descripcion)) {
        return new WP_Error('descripcion_generacion_fallida', 'No se pudo generar una descripción para el audio.');
    }

    $titulo = mb_substr($descripcion, 0, 60);
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

    $audio_original_id = adjuntarArchivoAut($nuevo_nombre_original, $post_id, $file_id);
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
    update_post_meta($post_id, 'paraDescarga', '1');
    update_post_meta($post_id, 'postAut', '1');

    return $post_id;
}

function adjuntarArchivoAut($archivo, $post_id, $file_id = null) {
    
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
    
    // Obtener la URL del adjunto
    $adjunto_url = wp_get_attachment_url($attach_id);
    
    // Si se proporcionó un file_id, actualizar la URL usando actualizarUrlArchivo
    if ( ! is_null($file_id) ) {
        // Asegúrate de que la función actualizarUrlArchivo esté definida
        if ( function_exists( 'actualizarUrlArchivo' ) ) {
            $resultado_actualizacion = actualizarUrlArchivo( $file_id, $adjunto_url );
            
            // Verificar si la actualización fue exitosa
            if ( is_wp_error( $resultado_actualizacion ) ) {

                wp_delete_attachment( $attach_id, true );
                return new WP_Error( 'actualizacion_url_fallida', 'No se pudo actualizar la URL del archivo: ' . $resultado_actualizacion->get_error_message() );
            }
        } else {
            // Manejar el caso donde actualizarUrlArchivo no está definida
            wp_delete_attachment( $attach_id, true );
            return new WP_Error( 'funcion_no_definida', 'La función actualizarUrlArchivo no está definida.' );
        }
    }
    
    // Eliminar el archivo temporal si existe
    if ($es_url && file_exists($archivo_temp)) {
        @unlink($archivo_temp);
    }
    
    return $attach_id;
}


