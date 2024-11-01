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


// Añadir intervalo de 6 horas en WordPress
add_filter('cron_schedules', 'intervalo_cada_seis_horas');
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
add_action('regenerar_audio_lite_evento', 'regenerarLite');



function repararAudiosWav() {
    // Configuración de consulta para obtener los últimos 5 posts de tipo "social_post" con metadato "post_audio"
    $args = [
        'post_type'      => 'social_post',
        'posts_per_page' => 5,
        'meta_query'     => [
            [
                'key'     => 'post_audio',
                'compare' => 'EXISTS',
            ],
        ],
        'orderby'        => 'date',
        'order'          => 'DESC'
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $audio_id = get_post_meta(get_the_ID(), 'post_audio', true);

            if ($audio_id) {
                // Intentar reparar el archivo WAV
                $archivo_original = get_attached_file($audio_id);
                if (!$archivo_original || !file_exists($archivo_original)) {
                    error_log("No se encontró el archivo original para el ID: $audio_id");
                    continue;
                }

                $ruta_reparada = str_replace('.wav', '_reparado.wav', $archivo_original);

                // Comando para reparar el archivo usando ffmpeg
                $comando = "/usr/bin/ffmpeg -i " . escapeshellarg($archivo_original) . " -c:a pcm_s16le " . escapeshellarg($ruta_reparada) . " -y";
                exec($comando, $output, $return_var);

                // Verificar si la reparación fue exitosa
                if ($return_var === 0) {
                    // Si la reparación fue exitosa, reemplaza el archivo original con el reparado
                    rename($ruta_reparada, $archivo_original);
                    echo "Archivo de audio reparado para el post ID " . get_the_ID() . "\n";
                } else {
                    error_log("Error al reparar el archivo $archivo_original: " . implode("\n", $output));
                    echo "No se pudo reparar el archivo de audio para el post ID " . get_the_ID() . "\n";
                }
            } else {
                echo "No se encontró el ID de audio para el post ID " . get_the_ID() . "\n";
            }
        }
        wp_reset_postdata();
    } else {
        echo "No se encontraron posts con audios dañados.\n";
    }
}

// Llamar a la función principal para ejecutar el proceso
repararAudiosWav();


