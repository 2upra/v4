<?php

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


function intervalo_cada_seis_horas($schedules)
{
    $schedules['cada_seis_horas'] = array(
        'interval' => 21600,
        'display' => __('Cada 6 Horas')
    );
    return $schedules;
}

add_filter('cron_schedules', 'intervalo_cada_seis_horas');

if (!wp_next_scheduled('regenerarAudioLiteEvento')) {
    wp_schedule_event(time(), 'cada_seis_horas', 'regenerarAudioLiteEvento');
}

add_action('regenerarAudioLiteEvento', 'regenerarLite');
