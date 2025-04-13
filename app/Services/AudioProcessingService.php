<?php


// Refactor(Org): Moved function reset_waveform_metas() from app/Logic/waveform.php
function reset_waveform_metas()
{
    guardarLog("Iniciando la función reset_waveform_metas.");

    $args = array(
        'post_type' => 'social_post',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'waveCargada',
                'value' => '1',
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);
    guardarLog("WP_Query ejecutado. Número de posts encontrados: " . $query->found_posts);

    if ($query->have_posts()) {
        guardarLog("Entrando en el bucle de posts.");
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            guardarLog("Procesando el post ID $post_id.");

            // Resetear waveCargada a false.
            update_post_meta($post_id, 'waveCargada', false);

            // Eliminar la imagen de waveform existente.
            $existing_attachment_id = get_post_meta($post_id, 'waveform_image_id', true);
            if ($existing_attachment_id) {
                wp_delete_attachment($existing_attachment_id, true);
            }

            // Eliminar los metadatos relacionados con la waveform.
            delete_post_meta($post_id, 'waveform_image_id');
            delete_post_meta($post_id, 'waveform_image_url');
        }
    } else {
        guardarLog("No se encontraron posts con el metadato 'waveCargada' igual a true.");
    }

    wp_reset_postdata();
    guardarLog("Finalizando la función reset_waveform_metas.");
}

// Refactor(Org): Moved function procesarArchivoAudioPython() from app/Auto/python.php
function procesarArchivoAudioPython($rutaArchivo)
{
    // Comando para ejecutar el script de Python
    $python_command = escapeshellcmd("python3 /var/www/wordpress/wp-content/themes/2upra3v/app/python/audio.py \"{$rutaArchivo}\"");

    // Log de la ejecución
    iaLog("Ejecutando comando de Python: {$python_command}");

    // Ejecutar el comando
    exec($python_command, $output, $return_var);

    // Verificar si hubo un error al ejecutar el comando
    if ($return_var !== 0) {
        iaLog("Error al ejecutar el script de Python. Código de retorno: {$return_var}. Salida: " . implode("\n", $output));
        return null;
    }

    // Ruta del archivo de resultados
    $resultados_path = "{$rutaArchivo}_resultados.json";
    $campos_esperados = ['bpm', 'pitch', 'emotion', 'key', 'scale', 'strength'];
    $resultados_data = [];

    // Verificar si el archivo de resultados existe
    if (file_exists($resultados_path)) {
        $resultados = json_decode(file_get_contents($resultados_path), true);

        // Validar que el contenido sea un array válido
        if ($resultados && is_array($resultados)) {
            foreach ($campos_esperados as $campo) {
                if (isset($resultados[$campo])) {
                    $resultados_data[$campo] = $resultados[$campo];
                } else {
                    iaLog("Campo '{$campo}' no encontrado en JSON.");
                }
            }
        } else {
            iaLog("El archivo de resultados JSON no contiene datos válidos.");
        }
    } else {
        iaLog("No se encontró el archivo de resultados en {$resultados_path}");
    }

    // Retornar los resultados procesados
    return [
        'bpm' => $resultados_data['bpm'] ?? null,
        'pitch' => $resultados_data['pitch'] ?? null,
        'emotion' => $resultados_data['emotion'] ?? null,
        'key' => $resultados_data['key'] ?? null,
        'scale' => $resultados_data['scale'] ?? null,
        'strength' => $resultados_data['strength'] ?? null
    ];
}

// Refactor(Move): Función procesarAudioLigero movida desde app/Services/Post/PostAttachmentService.php
#Paso 5.5 (Renumerado)
function procesarAudioLigero($post_id, $audio_id, $index)
{
    guardarLog("INICIO procesarAudioLigero para Post ID: $post_id, Audio ID: $audio_id, Index: $index");

    // Validar IDs
    if (!$post_id || !$audio_id || get_post_type($audio_id) !== 'attachment') {
        guardarLog("Error: IDs inválidos en procesarAudioLigero. PostID: $post_id, AudioID: $audio_id");
        return;
    }

    $audio_path = get_attached_file($audio_id);
    if (!$audio_path || !file_exists($audio_path)) {
        guardarLog("Error: No se encontró el archivo de audio original en {$audio_path} para Audio ID: {$audio_id}");
        return;
    }
    guardarLog("Ruta del archivo de audio original: {$audio_path}");

    $path_parts = pathinfo($audio_path);
    $output_dir = $path_parts['dirname'];
    // Usar el nombre base del archivo original para las versiones ligeras
    $original_filename_base = $path_parts['filename'];

    // --- Eliminar metadatos del archivo original ---
    $tmp_output_path = $output_dir . '/' . $original_filename_base . '_temp_stripped.mp3';
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c copy " . escapeshellarg($tmp_output_path);
    guardarLog("Ejecutando comando para eliminar metadatos: {$comando_strip_metadata}");
    exec($comando_strip_metadata . " 2>&1", $output_strip, $return_strip); // Capturar stderr también

    if ($return_strip !== 0) {
        // Unir la salida con " | " para loguear en una línea
        $log_output = implode(" | ", $output_strip);
        guardarLog("Error al eliminar metadatos del archivo original ({$return_strip}): " . $log_output);
        // Continuar de todos modos, pero loguear el error
    } else {
        // Reemplazar el original con la versión sin metadatos
        if (rename($tmp_output_path, $audio_path)) {
            guardarLog("Metadatos del archivo original eliminados y archivo reemplazado.");
        } else {
            guardarLog("Error al reemplazar el archivo original con la versión sin metadatos.");
            @unlink($tmp_output_path); // Limpiar archivo temporal si falla el rename
        }
    }

    // --- Obtener información del autor ---
    $post_author_id = get_post_field('post_author', $post_id);
    $author_info = get_userdata($post_author_id);
    $author_username = $author_info ? $author_info->user_login : "Desconocido";
    $page_name = "2upra.com"; // O obtener de una opción de WP
    guardarLog("Autor: {$author_username}, Sitio: {$page_name}");

    // --- Procesar archivo de audio ligero (128 kbps) ---
    $nuevo_archivo_path_lite = $output_dir . '/' . $original_filename_base . '_128k.mp3';
    // Crear metadatos correctamente escapados
    $metadata_args = sprintf(
        '-metadata artist=%s -metadata comment=%s',
        escapeshellarg($author_username),
        escapeshellarg($page_name)
    );
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k {$metadata_args} " . escapeshellarg($nuevo_archivo_path_lite);
    guardarLog("Ejecutando comando para crear audio ligero: {$comando_lite}");
    exec($comando_lite . " 2>&1", $output_lite, $return_var_lite); // Capturar stderr

    if ($return_var_lite !== 0) {
        // Unir la salida con " | "
        $log_output = implode(" | ", $output_lite);
        guardarLog("Error al procesar audio ligero ({$return_var_lite}): " . $log_output);
        return; // No continuar si falla la creación del archivo ligero
    } else {
        guardarLog("Audio ligero creado exitosamente en: {$nuevo_archivo_path_lite}");
    }

    // --- Insertar archivo ligero en la biblioteca de medios ---
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $filetype_lite = wp_check_filetype(basename($nuevo_archivo_path_lite), null);
    $attachment_lite = array(
        'guid'           => $nuevo_archivo_path_lite, // Usar la ruta como GUID inicial (WP lo ajustará)
        'post_mime_type' => $filetype_lite['type'],
        'post_title'     => $original_filename_base . '_128k', // Título descriptivo
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Insertar el adjunto asociado al post original
    $attach_id_lite = wp_insert_attachment($attachment_lite, $nuevo_archivo_path_lite, $post_id);

    if (is_wp_error($attach_id_lite)) {
        $error_message = str_replace("\n", " | ", $attach_id_lite->get_error_message());
        guardarLog("Error al insertar el adjunto ligero: " . $error_message);
        @unlink($nuevo_archivo_path_lite); // Limpiar archivo si falla la inserción
        return;
    }
    guardarLog("ID de adjunto ligero insertado: {$attach_id_lite}");

    // Generar metadatos del adjunto (importante para que WP lo reconozca correctamente)
    $attach_data_lite = wp_generate_attachment_metadata($attach_id_lite, $nuevo_archivo_path_lite);
    if (is_wp_error($attach_data_lite)) {
         $error_message = str_replace("\n", " | ", $attach_data_lite->get_error_message());
         guardarLog("Error al generar metadata para adjunto ligero {$attach_id_lite}: " . $error_message);
         // Continuar, pero el archivo puede no funcionar correctamente en WP
    } else {
        wp_update_attachment_metadata($attach_id_lite, $attach_data_lite);
        guardarLog("Metadatos generados para adjunto ligero {$attach_id_lite}.");
    }


    // --- Actualizar la meta del post con el ID del archivo ligero ---
    // Ajustar la clave de la meta según el índice
    $meta_key = ($index == 1) ? "post_audio_lite" : "post_audio_lite_{$index}";
    if (update_post_meta($post_id, $meta_key, $attach_id_lite)) {
        guardarLog("Meta '{$meta_key}' actualizada en Post ID {$post_id} con Attach ID {$attach_id_lite}");
    } else {
        guardarLog("Error al actualizar la meta '{$meta_key}' en Post ID {$post_id}");
    }


    // --- Extraer y guardar la duración del audio ---
    $duration_command = "/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($nuevo_archivo_path_lite);
    guardarLog("Ejecutando comando para obtener duración: {$duration_command}");
    $duration_in_seconds = shell_exec($duration_command);
    guardarLog("Salida de ffprobe (duración): '{$duration_in_seconds}'");

    $duration_in_seconds = trim($duration_in_seconds);
    if (is_numeric($duration_in_seconds) && $duration_in_seconds > 0) {
        $duration_in_seconds_float = (float)$duration_in_seconds;
        $minutes = floor($duration_in_seconds_float / 60);
        $seconds = floor($duration_in_seconds_float % 60);
        $duration_formatted = $minutes . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);

        $duration_meta_key = "audio_duration_{$index}";
        if (update_post_meta($post_id, $duration_meta_key, $duration_formatted)) {
            guardarLog("Duración del audio ({$duration_formatted}) guardada en meta '{$duration_meta_key}' para Post ID {$post_id}");
        } else {
            guardarLog("Error al guardar duración del audio en meta '{$duration_meta_key}' para Post ID {$post_id}");
        }
    } else {
        guardarLog("Duración del audio no válida o cero para el archivo {$nuevo_archivo_path_lite}. Salida: {$duration_in_seconds}");
    }

    guardarLog("Llamando a analizarYGuardarMetasAudio para Post ID: {$post_id}, Path Lite: {$nuevo_archivo_path_lite}, Index: {$index}");
    // Llamar a la función de análisis de IA (anteriormente solo si index === 1, ahora siempre según tu código)
    // Pasar la ruta del archivo ligero que acabamos de crear y verificar
    if (file_exists($nuevo_archivo_path_lite)) {
        analizarYGuardarMetasAudio($post_id, $nuevo_archivo_path_lite, $index);
    } else {
         guardarLog("Error: El archivo ligero {$nuevo_archivo_path_lite} no existe antes de llamar a analizarYGuardarMetasAudio.");
    }

    guardarLog("FIN procesarAudioLigero para Post ID: $post_id, Audio ID: $audio_id, Index: $index");
}


// Refactor(Org): Mueve función automaticAudio() de app/Auto/automaticPost.php
function automaticAudio($rutaArchivo, $nombre_archivo = null, $carpeta = null, $carpeta_abuela = null)
{
    error_log("automaticAudio start");
    $resultados = procesarArchivoAudioPython($rutaArchivo);

    if ($resultados) {
        echo "BPM: " . ($resultados['bpm'] ?? '') . "\n";
        echo "Emotion: " . ($resultados['emotion'] ?? '') . "\n";
        echo "Key: " . ($resultados['key'] ?? '') . "\n";
        echo "Scale: " . ($resultados['scale'] ?? '') . "\n";
        echo "Pitch: " . ($resultados['pitch'] ?? '') . "\n";
    } else {
        echo "Error procesando el archivo de audio.";
    }


    $informacion_archivo = '';
    if ($nombre_archivo) {
        $informacion_archivo .= "Archivo (IMPORTANCIA ALTA): '{$nombre_archivo}'\n";
    }
    if ($carpeta) {
        $informacion_archivo .= "Carpeta (IMPORTANCIA MEDIA): '{$carpeta}'\n";
    }
    if ($carpeta_abuela) {
        $informacion_archivo .= "Carpeta abuela (IMPORTANCIA BAJA): '{$carpeta_abuela}'\n";
    }
    if ($rutaArchivo) {
        $informacion_archivo .= "Ruta completa (PUEDE AYUDAR SI EL RESTO DE INFORMACIONES NO ES CLARA): '{$rutaArchivo}'\n";
    }

    $prompt = "Este audio fue subido automáticamente. Información:"
        . "{$informacion_archivo}"
        . "Por favor, determina una descripción precisa del audio utilizando el siguiente formato JSON. La información como el nombre y las carpetas son información super relevante para completar el JSON. Por favor, ignora cualquier nombre comercial, dominio, redes sociales o información no relevante que pueda contener el nombre o las carpetas. También ignora la palabra 'lite' o '2upra'. El 'nombre_corto' es un nuevo nombre para el archivo, y la 'descripción corta' es para entender rápidamente qué es el audio, por favor, que sea corta pero sin perder detalles importantes. Importante por no digas nada sobre las carpetas o donde esta ubicado el archivo, solo es una guia para entender de que trata el audio no hay que comentarlo, si archivo tiene un nombre claro, hay que tenerlo en cuenta, y luego el resto. Con los artistas posible siempre piensa en uno o varios que tengan la vibra de la descripción que la gente pueda relacionar con el audio. No uses palabras como 'Repetitive', 'Energetic', 'Powerful' en la descripcion corta. Te incluyo la estructura JSON con datos de ejemplo, que son irrelevantes en este caso: "
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

    $descripcion = generarDescripcionIA($rutaArchivo, $prompt);
    error_log("Descripcion generada");
    if ($descripcion) {
        // Convertir a UTF-8
        $descripcion_utf8 = mb_convert_encoding($descripcion, 'UTF-8', 'auto');
        $descripcion_procesada = json_decode(trim($descripcion_utf8, "```json \n"), true, 512, JSON_UNESCAPED_UNICODE);

        // Comprobar que la decodificación JSON fue exitosa y que el campo 'descripcion_ia' existe
        if (!$descripcion_procesada || !isset($descripcion_procesada['descripcion_ia']) || !is_array($descripcion_procesada['descripcion_ia'])) {
            iaLog("Error: La descripción procesada no tiene el formato esperado.");
            return false; // Retornar false en caso de error de formato
        }

        // Crear los nuevos datos con la estructura correcta
        $nuevos_datos = [
            'descripcion_ia' => [
                'es' => $descripcion_procesada['descripcion_ia']['es'] ?? '',
                'en' => $descripcion_procesada['descripcion_ia']['en'] ?? ''
            ],
            'instrumentos_principal' => [
                'es' => $descripcion_procesada['instrumentos_principal']['es'] ?? [],
                'en' => $descripcion_procesada['instrumentos_principal']['en'] ?? []
            ],
            'nombre_corto' => [
                'es' => $descripcion_procesada['nombre_corto']['es'] ?? '',
                'en' => $descripcion_procesada['nombre_corto']['en'] ?? ''
            ],
            'descripcion_corta' => [
                'es' => $descripcion_procesada['descripcion_corta']['es'] ?? '',
                'en' => $descripcion_procesada['descripcion_corta']['en'] ?? ''
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

        //autLog("Descripción del audio guardada para el post ID: {$nombre_archivo}");
    } else {
        // Si no se generó ninguna descripción, retornar false
        error_log("Error: No se pudo generar la descripción.");
        return false;
    }

    $nuevos_datos_algoritmo = isset($nuevos_datos) ? [
        'bpm' => $resultados['bpm'] ?? '',
        'emotion' => $resultados['emotion'] ?? '',
        'key' => $resultados['key'] ?? '',
        'scale' => $resultados['scale'] ?? '',

        'descripcion_ia' => $nuevos_datos['descripcion_ia'],
        'instrumentos_principal' => $nuevos_datos['instrumentos_principal'],
        'nombre_corto' => $nuevos_datos['nombre_corto'],
        'descripcion_corta' => $nuevos_datos['descripcion_corta'],
        'estado_animo' => $nuevos_datos['estado_animo'],
        'artista_posible' => $nuevos_datos['artista_posible'],
        'genero_posible' => $nuevos_datos['genero_posible'],
        'tipo_audio' => $nuevos_datos['tipo_audio'],
        'tags_posibles' => $nuevos_datos['tags_posibles'],
        'sugerencia_busqueda' => $nuevos_datos['sugerencia_busqueda']
    ] : [];
    error_log("automaticAudio end");
    return $nuevos_datos_algoritmo;
}

