<?php // Refactor(Org): Removed regenerarLite(), intervalo_cada_seis_horas() and associated cron hooks. Moved to app/Services/AudioProcessingService.php
// Refactor(Org): Moved pre_delete_attachment hook to app/Services/AudioProcessingService.php


// Function to add a 55-minute interval
function minutos55($schedules) {
    // 55 minutes in seconds (55 * 60)
    $schedules['cada55'] = array(
        'interval' => 3300, // 55 minutes in seconds
        'display' => __('Cada 55 minutos')
    );
    return $schedules;
}

// Add the 55-minute schedule
add_filter('cron_schedules', 'minutos55'); // Use the correct function name

// Schedule the 55-minute event if not already scheduled
if (!wp_next_scheduled('minutos55_evento')) {
    wp_schedule_event(time(), 'cada55', 'minutos55_evento'); // Ensure event name consistency
}

// Hook the optimization function to the 55-minute event
add_action('minutos55_evento', 'optimizar64kAudios');


/**
 * Optimizes audio files for 'social_post' type posts to 64k MP3,
 * limited to 20 seconds with a fade-out.
 * Processes a limited number of posts per run.
 *
 * @param int $limite Maximum number of posts to process in one go.
 */
function optimizar64kAudios($limite = 10000) {
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
function optimizarAudioPost($post_id) {
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