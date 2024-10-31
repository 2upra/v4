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
            $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($wav_file) . " -b:a 128k " . escapeshellarg($mp3_path) . " -y";
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

regenerarLite();

// Registrar intervalo personalizado de 6 horas
add_filter('cron_schedules', function($schedules) {
    $schedules['every_6_hours'] = array(
        'interval' => 21600, // 6 horas en segundos
        'display' => 'Cada 6 horas'
    );
    return $schedules;
});

// Programar el evento si no está programado
add_action('wp', function() {
    if (!wp_next_scheduled('regenerar_audios_lite_event')) {
        wp_schedule_event(time(), 'every_6_hours', 'regenerar_audios_lite_event');
    }
});

// Conectar el evento con la función
add_action('regenerar_audios_lite_event', 'regenerarLite');

// Limpiar el evento programado cuando se desactive el plugin
register_deactivation_hook(__FILE__, function() {
    $timestamp = wp_next_scheduled('regenerar_audios_lite_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'regenerar_audios_lite_event');
    }
});