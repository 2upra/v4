<?php

function procesarAudiosMultiples($postIdOriginal, $author_id, $paraColab, $paraDescarga, $artista, $fan, $rola, $sample, $tagsUsuario, $tienda, $nombreLanzamiento)
{
    $multiples_audios_encontrados = false;
    $ids_nuevos_posts = array();

    // Obtener el ID de la imagen destacada (foto de portada)
    $imagen_destacada_id = get_post_thumbnail_id($postIdOriginal);

    for ($i = 2; $i <= 30; $i++) {
        $audio_lite_meta_key = 'post_audio_lite_' . $i;
        $audio_meta_key = 'post_audio' . $i;
        $idHash_audioId_key = 'idHash_audioId' . $i;
        $precio_key = 'precioRola' . $i;
        $name_key = 'nombreRola' . $i;
        $audioUrl_key = 'audioUrl' . $i;
        $audio_duration_key = 'audio_duration_' . $i;

        $audio_lite_id = get_post_meta($postIdOriginal, $audio_lite_meta_key, true);
        $audio_id_hash = get_post_meta($postIdOriginal, $idHash_audioId_key, true);
        $audio_id = get_post_meta($postIdOriginal, $audio_meta_key, true);
        $precio = get_post_meta($postIdOriginal, $precio_key, true);
        $name = get_post_meta($postIdOriginal, $name_key, true);
        $audioUrl = get_post_meta($postIdOriginal, $audioUrl_key, true);
        $audio_duration = get_post_meta($postIdOriginal, $audio_duration_key, true);

        if (! empty($audio_lite_id) && ! empty($audio_id_hash) && !empty($audio_id)) {
            $multiples_audios_encontrados = true;
            $ruta_audio_lite = wp_get_attachment_url($audio_lite_id);
            $upload_dir = wp_upload_dir();
            $ruta_servidor = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ruta_audio_lite);
            $nuevoPost = crearAutPost('', $ruta_servidor, $audio_id_hash, $author_id, $postIdOriginal);

            if (! is_wp_error($nuevoPost) && $nuevoPost) {
                $ids_nuevos_posts[] = $nuevoPost;

                // Copiar la imagen destacada al nuevo post
                if (!empty($imagen_destacada_id)) {
                    set_post_thumbnail($nuevoPost, $imagen_destacada_id);
                }

                // Copiar los demás metadatos
                if (! empty($audioUrl)) {
                    update_post_meta($nuevoPost, 'audioUrl', $audioUrl);
                }
                if (! empty($audio_duration)) {
                    update_post_meta($nuevoPost, 'audio_duration_1', $audio_duration);
                }
                if (! empty($paraColab)) {
                    update_post_meta($nuevoPost, 'paraColab', $paraColab);
                }
                if (! empty($paraDescarga)) {
                    update_post_meta($nuevoPost, 'paraDescarga', $paraDescarga);
                }
                if (! empty($artista)) {
                    update_post_meta($nuevoPost, 'artista', $artista);
                }
                if (! empty($fan)) {
                    update_post_meta($nuevoPost, 'fan', $fan);
                }
                if (! empty($rola)) {
                    update_post_meta($nuevoPost, 'rola', $rola);
                }
                if (! empty($tienda)) {
                    update_post_meta($nuevoPost, 'tienda', $sample);
                }
                if (! empty($sample)) {
                    update_post_meta($nuevoPost, 'sample', $sample);
                }
                if (! empty($tagsUsuario)) {
                    update_post_meta($nuevoPost, 'tagsUsuario', $tagsUsuario);
                }
                if (! empty($audio_id)) {
                    update_post_meta($nuevoPost, 'post_audio', $audio_id);
                }
                if (! empty($precio)) {
                    update_post_meta($nuevoPost, 'precioRola', $precio);
                }
                if (! empty($name)) {
                    update_post_meta($nuevoPost, 'nombreRola', $name);
                }
                if (! empty($nombreLanzamiento)) {
                    update_post_meta($nuevoPost, 'nombreLanzamiento', $nombreLanzamiento);
                }
                delete_post_meta($postIdOriginal, $audio_lite_meta_key);
                delete_post_meta($postIdOriginal, $audio_meta_key);
                delete_post_meta($postIdOriginal, $idHash_audioId_key);
                delete_post_meta($postIdOriginal, $precio_key);
                delete_post_meta($postIdOriginal, $name_key);
                delete_post_meta($postIdOriginal, $audioUrl_key);
                delete_post_meta($postIdOriginal, $audio_duration_key);
                sleep(2);
            }
        }
    }
    return array($multiples_audios_encontrados, $ids_nuevos_posts);
}
