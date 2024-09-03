<?php

function process_album_post($post_id)
{

    $is_rola = get_post_meta($post_id, 'rola', true);
    if ($is_rola == '1') {
        return; 
    }

    $is_album = get_post_meta($post_id, 'albumRolas', true);
    if ($is_album != '1') {
        return; 
    }

    $original_post = get_post($post_id);
    $album_title = $original_post->post_title;
    $album_content = $original_post->post_content;
    $album_author = $original_post->post_author;
    $album_thumbnail_id = get_post_thumbnail_id($post_id);
    $album_post_id = wp_insert_post(array(
        'post_type' => 'albums',
        'post_title' => $album_title,
        'post_content' => $album_content,
        'post_author' => $album_author,
        'post_status' => 'publish',
    ));

    $meta_keys_to_copy = array('_post_puntuacion_final', 'allow_download', 'content-block', 'para_colab', 'real_name', 'artistic_name', 'email', 'public', 'genre_tags', 'instrument_tags');
    foreach ($meta_keys_to_copy as $key) {
        $value = get_post_meta($post_id, $key, true);
        if ($value) {
            update_post_meta($album_post_id, $key, $value);
        }
    }


    if ($album_thumbnail_id) {
        set_post_thumbnail($album_post_id, $album_thumbnail_id);
    }

    $rolas_meta = get_post_meta($post_id, 'rolas_meta_key', true);
    $rola_names = maybe_unserialize($rolas_meta);

    if (empty($rola_names)) {
        return;
    }
    $artistic_name = get_post_meta($post_id, 'artistic_name', true);
    $real_name = get_post_meta($post_id, 'real_name', true);

    $rola_posts = [];

    for ($i = 0; $i < count($rola_names); $i++) {
        $rola_title = $rola_names[$i];
        guardar_log("Processing rola " . ($i + 1) . ": $rola_title");

        // Agregar logs adicionales aquí
        guardar_log("Obteniendo metadatos para rola $i");
        $audio_id = get_post_meta($post_id, "post_audio" . ($i + 1), true);
        $audio_lite_id = get_post_meta($post_id, "post_audio_lite_" . ($i + 1), true);
        $audio_hd_id = get_post_meta($post_id, "post_audio_hd_" . ($i + 1), true);
        $waveform_image = get_post_meta($post_id, "audio_waveform_image_" . ($i + 1), true);
        $duration = get_post_meta($post_id, "audio_duration_" . ($i + 1), true);

        if (!$audio_id || !$rola_title) {
            guardar_log("Faltan datos críticos para la rola " . ($i + 1) . ": $rola_title. Continuando con la siguiente rola.");
            continue;
        }

        $rola_post_id = wp_insert_post(array(
            'post_type' => 'social_post',
            'post_title' => $rola_title,
            'post_content' => $rola_title,
            'post_author' => $album_author,
            'post_status' => 'publish',
        ));

        update_post_meta($rola_post_id, 'post_audio', $audio_id);
        update_post_meta($rola_post_id, 'album_id', $album_post_id);
        update_post_meta($rola_post_id, 'real_name', $real_name);
        update_post_meta($rola_post_id, 'post_audio_lite', $audio_lite_id);
        update_post_meta($rola_post_id, 'post_audio_hd', $audio_hd_id);
        update_post_meta($rola_post_id, 'audio_waveform_image', $waveform_image);
        update_post_meta($rola_post_id, 'audio_duration', $duration);
        update_post_meta($rola_post_id, 'rola', true);
        update_post_meta($rola_post_id, 'artistic_name', $artistic_name);
        update_post_meta($rola_post_id, '_post_puntuacion_final', 100);

        $additional_search_data = array(
            'rola' => true,
            'artistic_name' => $artistic_name,
            'titulo' => $rola_title
        );
        update_post_meta($rola_post_id, 'additional_search_data', json_encode($additional_search_data));

        if ($album_thumbnail_id) {
            set_post_thumbnail($rola_post_id, $album_thumbnail_id);
        }
        guardar_log("Rola creada: ID = $rola_post_id, Título = $rola_title");
        $rola_posts[] = $rola_post_id;
    }

   
    update_post_meta($album_post_id, 'album_rolas', $rola_posts);
    guardar_log("Valor final de rola_posts: " . print_r($rola_posts, true));
}
