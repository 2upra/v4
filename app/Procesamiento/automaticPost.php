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
            'interval' => 300, // 5 minutos en segundos
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

function autProcesarAudio($audio_path) {
    // Verificar si el archivo existe
    if (!file_exists($audio_path)) {
        //guardarLog("El archivo de audio no existe en la ruta proporcionada: {$audio_path}");
        return;
    }

    // Obtener las partes del camino del archivo
    $path_parts = pathinfo($audio_path);
    $directory = realpath($path_parts['dirname']);
    if ($directory === false) {
        //guardarLog("Ruta inválida del directorio: {$path_parts['dirname']}");
        return;
    }
    $extension = strtolower($path_parts['extension']);
    $basename = $path_parts['filename'];

    // Definir la ruta temporal para eliminar metadatos
    $temp_path = $directory . '/' . $basename . '_temp.' . $extension;

    // 1. Eliminar metadatos del archivo original usando ffmpeg
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c copy " . escapeshellarg($temp_path) . " -y";
    //guardarLog("Ejecutando comando para eliminar metadatos: {$comando_strip_metadata}");
    exec($comando_strip_metadata, $output_strip, $return_strip);
    if ($return_strip !== 0) {
        //guardarLog("Error al eliminar metadatos: " . implode("\n", $output_strip));
        return;
    }

    // Reemplazar el archivo original con el archivo sin metadatos
    if (!rename($temp_path, $audio_path)) {
        //guardarLog("Error al reemplazar el archivo original con la versión sin metadatos.");
        return;
    }
    //guardarLog("Metadatos eliminados correctamente del archivo original.");

    // 2. Crear una versión lite del audio en MP3 a 128 kbps
    $lite_path = $directory . '/' . $basename . '_lite.mp3';
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k " . escapeshellarg($lite_path) . " -y";
    //guardarLog("Ejecutando comando para crear versión lite: {$comando_lite}");
    exec($comando_lite, $output_lite, $return_lite);
    if ($return_lite !== 0) {
        //guardarLog("Error al crear la versión lite: " . implode("\n", $output_lite));
        return;
    }
    //guardarLog("Versión lite creada exitosamente: {$lite_path}");

    // 3. Enviar la versión lite a la IA para obtener un nombre limpio
    $nombre_limpio = generarNombreAudio($lite_path);
    if (empty($nombre_limpio)) {
        //guardarLog("La IA no retornó un nombre válido.");
        return;
    }
    //guardarLog("Nombre limpio generado por la IA: {$nombre_limpio}");

    // 4. Renombrar el archivo original
    $nuevo_nombre_original = $directory . '/' . $nombre_limpio . '.' . $extension;
    if (!rename($audio_path, $nuevo_nombre_original)) {
        //guardarLog("Error al renombrar el archivo original a: {$nuevo_nombre_original}");
        return;
    }
    //guardarLog("Archivo original renombrado a: {$nuevo_nombre_original}");

    // 5. Renombrar el archivo lite
    $nuevo_nombre_lite = $directory . '/' . $nombre_limpio . '_lite.mp3';
    if (!rename($lite_path, $nuevo_nombre_lite)) {
        //guardarLog("Error al renombrar el archivo lite a: {$nuevo_nombre_lite}");
        return;
    }
    //guardarLog("Archivo lite renombrado a: {$nuevo_nombre_lite}");

    $public_url_original = obtenerUrlPublica($nuevo_nombre_original); 
    $file_id = obtenerFileIDPorURL(obtenerUrlPublica($audio_path)); 

    if ($file_id !== false) {
        $actualizacion_exitosa = actualizarUrlArchivo($file_id, $public_url_original);
        if (!$actualizacion_exitosa) {
            //guardarLog("No se pudo actualizar la URL en la base de datos para File ID: $file_id");
        }
    } else {
        //guardarLog("No se encontró File ID para el archivo original. No se actualizará la URL.");
    }

    // Pasar las rutas absolutas a crearAutPost
    crearAutPost($nuevo_nombre_original, $nuevo_nombre_lite);
    //guardarLog("Archivos enviados a crearAutPost: {$nuevo_nombre_original}, {$nuevo_nombre_lite}");
}
function crearAutPost($nuevo_nombre_original, $nuevo_nombre_lite) {
    // ID del usuario autor
    $autor_id = 44;

    // Verificar si el usuario existe
    if (!get_userdata($autor_id)) {
        return new WP_Error('autor_invalido', 'El usuario con ID 44 no existe.');
    }

    $prompt = "Genera una descripción corta para el siguiente archivo de audio. Puede ser un sample, un fx, un loop, un sonido de un kick, puede ser cualquier cosa, el proposito es la descriciopn sea corta (solo responde con la descripcion no digas nada adiciona); te doy ejemplos Sample oscuro phonk, Fx de explosion, kick de house, sonido de sintentizador, piano melodía, guitarra acustica sample";
    $descripcion = generarDescripcionIA($nuevo_nombre_lite, $prompt);

    // Verificar si se obtuvo una descripción válida
    if (is_wp_error($descripcion) || empty($descripcion)) {
        return new WP_Error('descripcion_generacion_fallida', 'No se pudo generar una descripción para el audio.');
    }

    $titulo = mb_substr($descripcion, 0, 15);

    // Contenido completo de la descripción
    $contenido = $descripcion;

    // Datos del post
    $post_data = [
        'post_title'    => $titulo,
        'post_content'  => $contenido,
        'post_status'   => 'publish',
        'post_author'   => $autor_id,
        'post_type'     => 'social_post',
    ];

    // Insertar el post en la base de datos
    $post_id = wp_insert_post($post_data);

    // Verificar si hubo un error al insertar el post
    if (is_wp_error($post_id)) {
        return $post_id; // Retornar el error para manejarlo externamente
    }

    $index = 1;
    analizarYGuardarMetasAudio($post_id, $nuevo_nombre_lite, $index);
    return $post_id;
}





