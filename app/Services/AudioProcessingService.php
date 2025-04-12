<?php

// Refactor(Org): Moved from app/Functions/protegerAudio.php
// Contains logic for regenerating 'lite' audio files and associated cron tasks.

/**
 * Regenerates 'lite' (preview) MP3 files for posts that are missing them
 * or where the lite file is missing.
 */
function regenerarLite()
{
    global $wpdb;

    $posts_con_audio = $wpdb->get_results("
        SELECT post_id, meta_value as audio_id 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'post_audio'
    ");

    if (empty($posts_con_audio)) {
        // Assuming logAudio function is available globally or included elsewhere
        if (function_exists('logAudio')) {
            logAudio("regenerarLite: No se encontraron posts con post_audio.");
        }
        return;
    }

    $uploads_dir = wp_upload_dir();
    $audio_dir = trailingslashit($uploads_dir['basedir']) . "audio/";

    if (!file_exists($audio_dir)) {
        wp_mkdir_p($audio_dir);
    }

    foreach ($posts_con_audio as $post) {
        $post_id = $post->post_id;
        $audio_id = $post->audio_id;

        $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);
        $wav_file = get_attached_file($audio_id);

        if (!$wav_file || !file_exists($wav_file)) {
            if (function_exists('logAudio')) {
                logAudio("regenerarLite: Archivo WAV no encontrado para post_id: $post_id, audio_id: $audio_id");
            }
            continue;
        }

        $wav_info = pathinfo($wav_file);
        $mp3_filename = $wav_info['filename'] . '_lite.mp3';
        $mp3_path = $audio_dir . $mp3_filename;

        $regenerar = false;

        if (!$audio_lite_id) {
            if (function_exists('logAudio')) {
                logAudio("regenerarLite: No existe post_audio_lite para post_id: $post_id. Se regenerará.");
            }
            $regenerar = true;
        } else {
            $lite_file = get_attached_file($audio_lite_id);
            if (!$lite_file || !file_exists($lite_file)) {
                if (function_exists('logAudio')) {
                    logAudio("regenerarLite: Archivo lite no encontrado para post_id: $post_id, audio_lite_id: $audio_lite_id. Se regenerará.");
                }
                $regenerar = true;
            }
        }

        if ($regenerar) {
            // Ensure logAudio is available
            $log_func = function_exists('logAudio') ? 'logAudio' : function ($msg) {
                error_log($msg);
            };

            // Ensure ffmpeg path is correct
            $ffmpeg_path = '/usr/bin/ffmpeg'; // Consider making this configurable
            $comando_lite = $ffmpeg_path . " -i " . escapeshellarg($wav_file) . " -vn -b:a 64k -ar 44100 -t 20 -af 'afade=t=out:st=15:d=5' " . escapeshellarg($mp3_path) . " -y";
            exec($comando_lite, $output_lite, $return_lite);

            if ($return_lite !== 0 || !file_exists($mp3_path) || filesize($mp3_path) == 0) {
                $log_func("regenerarLite: Error al generar MP3 para post_id: $post_id - Código: $return_lite, Salida: " . implode(" | ", $output_lite));
                // Clean up failed file
                if (file_exists($mp3_path)) unlink($mp3_path);
                continue;
            }

            $filetype = wp_check_filetype(basename($mp3_path), null);
            $attachment = array(
                'post_mime_type' => $filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($mp3_path)),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            // Ensure media functions are available
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $attach_id = wp_insert_attachment($attachment, $mp3_path, $post_id);

            if (is_wp_error($attach_id)) {
                $log_func("regenerarLite: Error al crear attachment para post_id: $post_id - " . $attach_id->get_error_message());
                // Clean up generated file if attachment failed
                unlink($mp3_path);
                continue;
            }

            $attach_data = wp_generate_attachment_metadata($attach_id, $mp3_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
            update_post_meta($post_id, 'post_audio_lite', $attach_id);

            $log_func("regenerarLite: Regenerado exitosamente audio lite para post_id: $post_id, nuevo audio_lite_id: $attach_id");
        }
    }
}

/**
 * Adds a custom cron schedule interval of 6 hours.
 *
 * @param array $schedules Existing cron schedules.
 * @return array Modified cron schedules.
 */
function intervalo_cada_seis_horas($schedules)
{
    $schedules['cada_seis_horas'] = array(
        'interval' => 21600, // 6 horas en segundos (6 * 60 * 60)
        'display' => __('Cada 6 Horas')
    );
    return $schedules;
}

// Add the custom cron schedule
add_filter('cron_schedules', 'intervalo_cada_seis_horas');

// Schedule the event if it's not already scheduled
if (!wp_next_scheduled('regenerar_audio_lite_evento')) {
    wp_schedule_event(time(), 'cada_seis_horas', 'regenerar_audio_lite_evento');
}

// Hook the regeneration function to the scheduled event
add_action('regenerar_audio_lite_evento', 'regenerarLite');

// Refactor(Org): Moved from app/Functions/protegerAudio.php - Hook to prevent audio file deletion.
add_filter('pre_delete_attachment', function ($delete, $post) {
    // Check if the file being deleted is within the '/audio/' directory
    $file_path = get_attached_file($post->ID); // Use $post->ID to get the attachment ID
    if ($file_path && strpos($file_path, '/audio/') !== false) {
        // Log the attempt and prevent deletion
        // Assuming logAudio function is available globally
        logAudio("Intento de eliminación PREVENIDO de archivo de audio protegido: " . $file_path . " (Post ID: " . $post->ID . ")");
        return false; // Prevent deletion
    }
    // Allow deletion for other attachments
    return $delete;
}, 10, 2);

// Refactor(Org): Moved audio optimization logic (cron, functions) from protegerAudio.php

// Function to add a 55-minute interval
function minutos55($schedules)
{
    // 55 minutes in seconds (55 * 60)
    $schedules['cada55'] = array(
        'interval' => 3300, // 55 minutes in seconds
        'display' => __('Cada 55 minutos')
    );
    return $schedules;
}

/**
 * Optimizes audio files for 'social_post' type posts to 64k MP3,
 * limited to 20 seconds with a fade-out.
 * Processes a limited number of posts per run.
 *
 * @param int $limite Maximum number of posts to process in one go.
 */
function optimizar64kAudios($limite = 10000)
{
    // Get 'social_post' posts that haven't been optimized and don't have 'rola' meta set to 1
    $query = new WP_Query(array(
        'post_type' => 'social_post',
        'meta_query' => array(
            'relation' => 'AND', // Ensure all conditions are met
            array(
                'key' => 'audio_optimizado',
                'compare' => 'NOT EXISTS' // Only those without the 'audio_optimizado' meta
            ),
            array(
                'key' => 'rola',
                'value' => '1',
                'compare' => '!=' // Exclude posts where 'rola' is 1
                // If 'rola' might not exist, use a nested query or adjust logic
                // For simplicity, assuming 'rola' != 1 covers non-existence too for this check's purpose.
                // A more robust check might be needed depending on exact requirements.
            )
        ),
        'posts_per_page' => $limite, // Limit posts per cycle
        'fields' => 'ids', // Only get post IDs
        'no_found_rows' => true, // Optimize query performance
        'update_post_term_cache' => false, // Further optimization
        'update_post_meta_cache' => false // Further optimization
    ));

    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            optimizarAudioPost($post_id);
        }
    }

    wp_reset_postdata(); // Important after custom WP_Query loops
}

/**
 * Optimizes the audio for a single post.
 * Converts the original audio to a 64k MP3, limited to 20s with fade-out.
 * Updates post meta accordingly.
 *
 * @param int $post_id The ID of the post to process.
 */
function optimizarAudioPost($post_id)
{
    // Retrieve relevant post meta data
    $audio_id = get_post_meta($post_id, 'post_audio', true);
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true); // Current 'lite' audio ID
    $wave_cargada = get_post_meta($post_id, 'waveCargada', true);
    $waveform_image_id = get_post_meta($post_id, 'waveform_image_id', true);
    $waveform_image_url = get_post_meta($post_id, 'waveform_image_url', true);
    $audio_optimizado_meta = get_post_meta($post_id, 'audio_optimizado', true);

    // Exit if already optimized
    if ($audio_optimizado_meta) {
        // Assuming logAudio function is available globally
        logAudio("Audio ya optimizado para post ID $post_id. Saltando.");
        return;
    }

    // Proceed only if there's an original audio ID
    if ($audio_id) {
        $archivo_original = get_attached_file($audio_id);

        // Check if the original file exists
        if (!$archivo_original || !file_exists($archivo_original)) {
            logAudio("Archivo original no encontrado para audio ID: $audio_id (Post ID: $post_id)");
            // Optionally mark as failed or handle error
            update_post_meta($post_id, 'audio_optimizado_error', 'Archivo original no encontrado');
            return;
        }

        // Preserve the existing 'lite' audio ID by moving it if it exists
        if ($audio_lite_id) {
            // Check if the file for audio_lite_id actually exists before moving
            $lite_file_path = get_attached_file($audio_lite_id);
            if ($lite_file_path && file_exists($lite_file_path)) {
                update_post_meta($post_id, 'post_audio_lite_128k', $audio_lite_id);
                logAudio("Movido ID $audio_lite_id a post_audio_lite_128k para post ID $post_id.");
            } else {
                logAudio("Archivo para post_audio_lite ID $audio_lite_id no encontrado. No se movió a 128k. (Post ID: $post_id)");
                // Decide if you want to delete the meta if the file doesn't exist
                // delete_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
            }
        }

        // Get original audio duration using ffprobe
        // Ensure ffprobe path is correct and executable by the web server user
        $ffprobe_path = '/usr/bin/ffprobe'; // Consider making this configurable
        $comando_duracion = $ffprobe_path . " -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($archivo_original);
        $duracion_original = shell_exec($comando_duracion);
        $duracion_original = trim($duracion_original); // Clean up output

        // Validate duration - should be a number
        if (!is_numeric($duracion_original) || $duracion_original <= 0) {
            logAudio("No se pudo obtener la duración válida del audio original para post ID $post_id. Salida ffprobe: " . $duracion_original);
            // Decide how to handle: skip, set default, mark error?
            // Setting a flag might be useful
            update_post_meta($post_id, 'audio_optimizado_error', 'No se pudo obtener duración');
            // For now, let's try to proceed but log the issue. The comparison later might fail.
            $duracion_original = 0; // Set to 0 to avoid PHP warnings, but indicates an issue
        } else {
            $duracion_original = floatval($duracion_original); // Convert to float for comparison
            update_post_meta($post_id, 'duracionAudio', $duracion_original);
            logAudio("Duración original para post ID $post_id: $duracion_original segundos.");
        }


        // Define path for the new optimized MP3 file
        $ruta_info = pathinfo($archivo_original);
        // Ensure directory exists and is writable
        $output_dir = $ruta_info['dirname'];
        if (!is_writable($output_dir)) {
            logAudio("Directorio de salida no escribible: $output_dir para post ID $post_id");
            update_post_meta($post_id, 'audio_optimizado_error', 'Directorio no escribible');
            return;
        }
        $ruta_optimizada = $output_dir . '/' . $ruta_info['filename'] . '_optimizado_64k_20s.mp3'; // More descriptive name

        // FFmpeg command for optimization: 64kbps, 44.1kHz, max 20s, 5s fade-out starting at 15s
        // Ensure ffmpeg path is correct
        $ffmpeg_path = '/usr/bin/ffmpeg'; // Consider making this configurable
        $comando = $ffmpeg_path . " -i " . escapeshellarg($archivo_original) .
            " -vn" . // No video stream
            " -ar 44100" . // Audio sample rate
            " -ac 2" . // Audio channels (stereo) - adjust if needed
            " -b:a 64k" . // Audio bitrate
            " -t 20" . // Limit duration to 20 seconds
            " -af 'afade=t=out:st=15:d=5'" . // Apply fade-out: type=out, start_time=15s, duration=5s
            " " . escapeshellarg($ruta_optimizada) . " -y"; // Output path, -y overwrites without asking
        exec($comando, $output, $return_var);

        // Check FFmpeg execution result
        if ($return_var === 0 && file_exists($ruta_optimizada) && filesize($ruta_optimizada) > 0) {
            logAudio("Optimización FFmpeg exitosa para post ID $post_id. Archivo: $ruta_optimizada");

            // Insert the optimized file as a new WordPress attachment
            $nuevo_audio_id = wp_insert_attachment(array(
                'post_mime_type' => 'audio/mpeg',
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($ruta_optimizada)), // Title from filename without extension
                'post_content' => '',
                'post_status' => 'inherit'
            ), $ruta_optimizada, $post_id); // Associate with the original post

            if (!is_wp_error($nuevo_audio_id) && $nuevo_audio_id) {
                // Generate metadata for the new attachment (like duration, etc., if possible via WP)
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                wp_update_attachment_metadata($nuevo_audio_id, wp_generate_attachment_metadata($nuevo_audio_id, $ruta_optimizada));

                // Update the 'post_audio_lite' meta key to point to the new optimized audio ID
                update_post_meta($post_id, 'post_audio_lite', $nuevo_audio_id);
                logAudio("Nuevo adjunto optimizado creado (ID: $nuevo_audio_id) y asignado a post_audio_lite para post ID $post_id.");

                // Handle waveform removal if original audio was longer than 20s
                if ($duracion_original > 20) {
                    // Mark the post as having its audio trimmed
                    update_post_meta($post_id, 'recortado', '1'); // Use '1' or true consistently
                    logAudio("Marcado como recortado para post ID $post_id (duración original: $duracion_original > 20s).");

                    // Check if waveform data exists before attempting deletion
                    if ($wave_cargada == 1 && $waveform_image_id && $waveform_image_url) {
                        $delete_result = wp_delete_attachment($waveform_image_id, true); // Force delete the attachment file
                        if ($delete_result) {
                            logAudio("Waveform (ID: $waveform_image_id) eliminada para post ID $post_id.");
                        } else {
                            logAudio("Error al eliminar waveform (ID: $waveform_image_id) para post ID $post_id.");
                            // Log the WP_Error if possible, or check server logs
                        }
                        // Delete associated meta keys regardless of attachment deletion success
                        delete_post_meta($post_id, 'waveCargada');
                        delete_post_meta($post_id, 'waveform_image_id');
                        delete_post_meta($post_id, 'waveform_image_url');
                    } else {
                        logAudio("No se encontró waveform o meta asociada para eliminar en post ID $post_id (recortado).");
                    }
                } else {
                    // If duration <= 20, ensure 'recortado' meta is not present or is false
                    delete_post_meta($post_id, 'recortado');
                    logAudio("Audio no recortado para post ID $post_id (duración original: $duracion_original <= 20s).");
                }

                // Mark this post as successfully optimized to prevent re-processing
                update_post_meta($post_id, 'audio_optimizado', '1'); // Use '1' or true consistently
                // Clear any previous error flags
                delete_post_meta($post_id, 'audio_optimizado_error');
                logAudio("Optimización completada y marcada para post ID $post_id.");
            } else {
                // Handle error during attachment insertion
                $error_message = is_wp_error($nuevo_audio_id) ? $nuevo_audio_id->get_error_message() : 'ID de adjunto inválido';
                logAudio("Error al insertar adjunto optimizado para post ID $post_id: $error_message");
                update_post_meta($post_id, 'audio_optimizado_error', 'Error al insertar adjunto: ' . $error_message);
                // Clean up the generated file if attachment failed?
                // unlink($ruta_optimizada);
            }
        } else {
            // Handle FFmpeg command failure
            $error_details = "Código de retorno: $return_var.";
            if (!file_exists($ruta_optimizada)) {
                $error_details .= " Archivo de salida no encontrado.";
            } elseif (filesize($ruta_optimizada) == 0) {
                $error_details .= " Archivo de salida vacío.";
            }
            $ffmpeg_output = implode("\n", $output); // Capture ffmpeg output for debugging
            logAudio("Error en FFmpeg al optimizar para post ID $post_id. $error_details Comando: $comando. Salida: $ffmpeg_output");
            update_post_meta($post_id, 'audio_optimizado_error', 'Error FFmpeg: ' . $error_details);
            // Clean up potentially failed/empty output file
            if (file_exists($ruta_optimizada)) {
                unlink($ruta_optimizada);
            }
        }
    } else {
        // Log if no 'post_audio' meta key was found
        logAudio("No se encontró meta 'post_audio' para post ID $post_id. Saltando optimización.");
        // Optionally mark this state
        // update_post_meta($post_id, 'audio_optimizado_error', 'No se encontró post_audio meta');
    }
}

// Add the 55-minute schedule
add_filter('cron_schedules', 'minutos55'); // Use the correct function name

// Schedule the 55-minute event if not already scheduled
if (!wp_next_scheduled('minutos55_evento')) {
    wp_schedule_event(time(), 'cada55', 'minutos55_evento'); // Ensure event name consistency
}

// Hook the optimization function to the 55-minute event
add_action('minutos55_evento', 'optimizar64kAudios');

// Refactor(Org): Moved function save_waveform_image() from app/Logic/waveform.php
function save_waveform_image()
{
    if (!isset($_FILES['image']) || !isset($_POST['post_id'])) {
        wp_send_json_error('Datos incompletos');
        return;
    }

    $file = $_FILES['image'];
    $post_id = intval($_POST['post_id']);

    // Eliminar la imagen anterior si waveCargada es false.
    if (get_post_meta($post_id, 'waveCargada', true) === 'false') {
        $existing_attachment_id = get_post_meta($post_id, 'waveform_image_id', true);
        if ($existing_attachment_id) {
            wp_delete_attachment($existing_attachment_id, true);
        }
    }

    // Agregar el ID del post al nombre del archivo para evitar duplicados.
    add_filter('wp_handle_upload_prefilter', function ($file) use ($post_id) {
        $file['name'] = $post_id . '_' . $file['name'];
        return $file;
    });

    // Subir la imagen.
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Obtener el autor del post y asignar la imagen a él.
    $author_id = get_post_field('post_author', $post_id);
    $attachment_id = media_handle_upload('image', $post_id, array('post_author' => $author_id));

    // Remover el filtro.
    remove_filter('wp_handle_upload_prefilter', function ($file) use ($post_id) {
        $file['name'] = $post_id . '_' . $file['name'];
        return $file;
    });

    // Manejar errores de subida.
    if (is_wp_error($attachment_id)) {
        wp_send_json_error('Error al subir la imagen');
        return;
    }

    // Obtener la URL y el tamaño de la imagen.
    $image_url = wp_get_attachment_url($attachment_id);
    $file_path = get_attached_file($attachment_id);
    $file_size = size_format(filesize($file_path), 2);

    // Actualizar los metadatos del post.
    update_post_meta($post_id, 'waveform_image_id', $attachment_id);
    update_post_meta($post_id, 'waveform_image_url', $image_url);
    update_post_meta($post_id, 'waveCargada', true);

    wp_send_json_success(array(
        'message' => 'Imagen guardada correctamente',
        'url' => $image_url,
        'size' => $file_size
    ));
}

// Refactor(Org): Moved AJAX hooks for save_waveform_image() from app/Logic/waveform.php
add_action('wp_ajax_save_waveform_image', 'save_waveform_image');
add_action('wp_ajax_nopriv_save_waveform_image', 'save_waveform_image');

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

