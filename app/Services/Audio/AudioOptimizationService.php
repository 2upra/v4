<?php

function optimizar64kAudios($limite = 10000)
{
    $query = new WP_Query(array(
        'post_type' => 'social_post',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'audio_optimizado',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'rola',
                'value' => '1',
                'compare' => '!='
            )
        ),
        'posts_per_page' => $limite,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false
    ));

    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            optimizarAudioPost($post_id);
        }
    }

    wp_reset_postdata();
}

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
