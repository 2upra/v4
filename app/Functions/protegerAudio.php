<?

add_filter('pre_delete_attachment', function($delete, $post) {
    if (strpos(get_attached_file($post), '/audio/') !== false) {
        logAudio("Intento de eliminación de archivo de audio: " . get_attached_file($post));
        return false;
    }
    return $delete;
}, 10, 2);


function regenerarLite() {
    global $wpdb;
    
    $posts_con_audio = $wpdb->get_results("
        SELECT post_id, meta_value as audio_id 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'post_audio'
    ");

    if (empty($posts_con_audio)) {
        logAudio("No se encontraron posts con post_audio");
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
            logAudio("Archivo WAV no encontrado para post_id: $post_id, audio_id: $audio_id");
            continue;
        }

        $wav_info = pathinfo($wav_file);
        $mp3_filename = $wav_info['filename'] . '_lite.mp3';
        $mp3_path = $audio_dir . $mp3_filename;

        $regenerar = false;
        
        if (!$audio_lite_id) {
            logAudio("No existe post_audio_lite para post_id: $post_id");
            $regenerar = true;
        } else {
            $lite_file = get_attached_file($audio_lite_id);
            if (!$lite_file || !file_exists($lite_file)) {
                logAudio("Archivo lite no encontrado para post_id: $post_id, audio_lite_id: $audio_lite_id");
                $regenerar = true;
            }
        }

        if ($regenerar) {
            $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($wav_file) . " -b:a 64k -ar 44100 -t 20 -af 'afade=t=out:st=15:d=5' " . escapeshellarg($mp3_path) . " -y";
            exec($comando_lite, $output_lite, $return_lite);

            if ($return_lite !== 0 || !file_exists($mp3_path)) {
                logAudio("Error al generar MP3 para post_id: $post_id - " . implode(" | ", $output_lite));
                continue;
            }

            $filetype = wp_check_filetype($mp3_filename, null);
            $attachment = array(
                'post_mime_type' => $filetype['type'],
                'post_title' => $mp3_filename,
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $mp3_path, $post_id);
            
            if (is_wp_error($attach_id)) {
                logAudio("Error al crear attachment para post_id: $post_id - " . $attach_id->get_error_message());
                continue;
            }

            $attach_data = wp_generate_attachment_metadata($attach_id, $mp3_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
            update_post_meta($post_id, 'post_audio_lite', $attach_id);

            logAudio("Regenerado exitosamente audio lite para post_id: $post_id, nuevo audio_lite_id: $attach_id");
        }
    }
}


// Añadir intervalo de 6 horas en WordPress

function intervalo_cada_seis_horas($schedules) {
    $schedules['cada_seis_horas'] = array(
        'interval' => 21600, // 6 horas en segundos (6 * 60 * 60)
        'display' => __('Cada 6 Horas')
    );
    return $schedules;
}

if (!wp_next_scheduled('regenerar_audio_lite_evento')) {
    wp_schedule_event(time(), 'cada_seis_horas', 'regenerar_audio_lite_evento');
}
add_filter('cron_schedules', 'intervalo_cada_seis_horas');
add_action('regenerar_audio_lite_evento', 'regenerarLite');



// Función para programar el evento de optimización de audios
function programar_optimizacion_audios() {
    if (!wp_next_scheduled('optimizar_audios_lote')) {
        wp_schedule_event(time(), 'hourly', 'optimizar_audios_lote'); // Ejecuta cada hora
    }
}
add_action('wp', 'programar_optimizacion_audios');
add_action('optimizar_audios_lote', 'optimizar_audios_en_lote');

function optimizar_audios_en_lote($limite = 1000) {
    // Obtener los posts de tipo 'social_post' que no han sido optimizados
    $query = new WP_Query(array(
        'post_type' => 'social_post',
        'meta_query' => array(
            array(
                'key' => 'audio_optimizado',
                'compare' => 'NOT EXISTS' // Solo los que no tienen la meta 'audio_optimizado'
            )
        ),
        'posts_per_page' => $limite, // Limitar el número de posts a procesar por ciclo
        'fields' => 'ids', // Solo obtener los IDs de los posts
        'no_found_rows' => true // Para optimizar la consulta
    ));

    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            optimizarAudioPost($post_id);
        }
    }

    wp_reset_postdata();
}

function optimizarAudioPost($post_id) {
    $audio_id = get_post_meta($post_id, 'post_audio', true);
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);
    $wave_cargada = get_post_meta($post_id, 'waveCargada', true);
    $waveform_image_id = get_post_meta($post_id, 'waveform_image_id', true);
    $waveform_image_url = get_post_meta($post_id, 'waveform_image_url', true);
    $audio_optimizado_meta = get_post_meta($post_id, 'audio_optimizado', true);

    // Si ya tiene la meta 'audio_optimizado', salir para no optimizar de nuevo
    if ($audio_optimizado_meta) {
        logAudio("El audio ya tiene la meta de 'audio_optimizado' para el post ID $post_id. No se volverá a optimizar.");
        return;
    }

    if ($audio_id) {
        $archivo_original = get_attached_file($audio_id);

        if (!$archivo_original || !file_exists($archivo_original)) {
            logAudio("No se encontró el archivo original para el ID: $audio_id");
            return;
        }

        // Mover el ID actual de `post_audio_lite` a `post_audio_lite_128k` si existe
        if ($audio_lite_id) {
            update_post_meta($post_id, 'post_audio_lite_128k', $audio_lite_id);
        }

        // Obtener la duración original del audio
        $duracion_original = shell_exec("/usr/bin/ffprobe -i " . escapeshellarg($archivo_original) . " -show_entries format=duration -v quiet -of csv='p=0'");
        $duracion_original = trim($duracion_original); // Asegurarse de que esté limpio
        update_post_meta($post_id, 'duracionAudio', $duracion_original);

        // Generar la ruta para el archivo optimizado
        $ruta_info = pathinfo($archivo_original);
        $ruta_optimizada = $ruta_info['dirname'] . '/' . $ruta_info['filename'] . '_optimizado.mp3';

        // Comando para convertir el audio usando ffmpeg, ajustar a 64 kbps, limitar a 20s y aplicar desvanecimiento
        $comando = "/usr/bin/ffmpeg -i " . escapeshellarg($archivo_original) . 
                   " -b:a 64k -ar 44100 -t 20 -af 'afade=t=out:st=15:d=5' " . escapeshellarg($ruta_optimizada) . " -y";
        exec($comando, $output, $return_var);

        // Verificar si la conversión fue exitosa
        if ($return_var === 0) {
            // Insertar el nuevo adjunto optimizado y guardar su ID en `post_audio_lite`
            $nuevo_audio_id = wp_insert_attachment(array(
                'post_mime_type' => 'audio/mpeg',
                'post_title' => $ruta_info['filename'] . '_optimizado',
                'post_content' => '',
                'post_status' => 'inherit'
            ), $ruta_optimizada, $post_id);

            if ($nuevo_audio_id) {
                wp_update_attachment_metadata($nuevo_audio_id, wp_generate_attachment_metadata($nuevo_audio_id, $ruta_optimizada));
                update_post_meta($post_id, 'post_audio_lite', $nuevo_audio_id);

                // Si el audio original duraba más de 20 segundos y las metas de la waveform están presentes
                if ($duracion_original > 20 && $wave_cargada == 1 && $waveform_image_id && $waveform_image_url) {
                    // Eliminar la waveform y las metas relacionadas
                    wp_delete_attachment($waveform_image_id, true); // Borra el adjunto del waveform
                    delete_post_meta($post_id, 'waveCargada');
                    delete_post_meta($post_id, 'waveform_image_id');
                    delete_post_meta($post_id, 'waveform_image_url');
                    logAudio("Se ha eliminado la waveform y las metas relacionadas para el post ID $post_id.");
                }

                if ($duracion_original > 20) {
                    update_post_meta($post_id, 'recortado', true);
                }

                // Agregar la meta de 'audio_optimizado' para asegurarnos de que no se optimice de nuevo
                update_post_meta($post_id, 'audio_optimizado', 1);
            } else {
                logAudio("Error al insertar el adjunto optimizado para el post ID $post_id.");
            }
        } else {
            logAudio("Error al optimizar el archivo $archivo_original: " . implode("\n", $output));
        }
    } else {
        logAudio("No se encontró el audio para el post ID $post_id");
    }
}







