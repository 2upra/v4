<?php

# Procesa un post de tipo álbum, creando un nuevo post de álbum y posts de rolas asociados.
function process_album_post($idPost)
{
    $esRola = get_post_meta($idPost, 'rola', true);
    if ($esRola == '1') {
        return;
    }

    $esAlbum = get_post_meta($idPost, 'albumRolas', true);
    if ($esAlbum != '1') {
        return;
    }

    $postOriginal = get_post($idPost);
    $tituloAlbum = $postOriginal->post_title;
    $contenidoAlbum = $postOriginal->post_content;
    $autorAlbum = $postOriginal->post_author;
    $idMiniaturaAlbum = get_post_thumbnail_id($idPost);

    $idPostAlbum = wp_insert_post(array(
        'post_type' => 'albums',
        'post_title' => $tituloAlbum,
        'post_content' => $contenidoAlbum,
        'post_author' => $autorAlbum,
        'post_status' => 'publish',
    ));

    $clavesMetaCopiar = array('_post_puntuacion_final', 'paraDescarga', 'esExclusivo', 'paraColab', 'real_name', 'artistic_name', 'email', 'public', 'genre_tags', 'instrument_tags');
    foreach ($clavesMetaCopiar as $clave) {
        $valor = get_post_meta($idPost, $clave, true);
        if ($valor) {
            update_post_meta($idPostAlbum, $clave, $valor);
        }
    }

    if ($idMiniaturaAlbum) {
        set_post_thumbnail($idPostAlbum, $idMiniaturaAlbum);
    }

    $rolasMeta = get_post_meta($idPost, 'rolas_meta_key', true);
    $nombresRola = maybe_unserialize($rolasMeta);

    if (empty($nombresRola)) {
        return;
    }
    $nombreArtistico = get_post_meta($idPost, 'artistic_name', true);
    $nombreReal = get_post_meta($idPost, 'real_name', true);

    $postsRola = [];

    for ($i = 0; $i < count($nombresRola); $i++) {
        $tituloRola = $nombresRola[$i];

        $idAudio = get_post_meta($idPost, "post_audio" . ($i + 1), true);
        $idAudioLite = get_post_meta($idPost, "post_audio_lite_" . ($i + 1), true);
        $idAudioHd = get_post_meta($idPost, "post_audio_hd_" . ($i + 1), true);
        $imagenOnda = get_post_meta($idPost, "audio_waveform_image_" . ($i + 1), true);
        $duracion = get_post_meta($idPost, "audio_duration_" . ($i + 1), true);

        if (!$idAudio || !$tituloRola) {
            continue;
        }

        $idPostRola = wp_insert_post(array(
            'post_type' => 'social_post',
            'post_title' => $tituloRola,
            'post_content' => $tituloRola,
            'post_author' => $autorAlbum,
            'post_status' => 'publish',
        ));

        update_post_meta($idPostRola, 'post_audio', $idAudio);
        update_post_meta($idPostRola, 'album_id', $idPostAlbum);
        update_post_meta($idPostRola, 'real_name', $nombreReal);
        update_post_meta($idPostRola, 'post_audio_lite', $idAudioLite);
        update_post_meta($idPostRola, 'post_audio_hd', $idAudioHd);
        update_post_meta($idPostRola, 'audio_waveform_image', $imagenOnda);
        update_post_meta($idPostRola, 'audio_duration', $duracion);
        update_post_meta($idPostRola, 'rola', true);
        update_post_meta($idPostRola, 'artistic_name', $nombreArtistico);
        update_post_meta($idPostRola, '_post_puntuacion_final', 100);

        $datosAdicionalesBusqueda = array(
            'rola' => true,
            'artistic_name' => $nombreArtistico,
            'titulo' => $tituloRola
        );
        update_post_meta($idPostRola, 'additional_search_data', json_encode($datosAdicionalesBusqueda));

        if ($idMiniaturaAlbum) {
            set_post_thumbnail($idPostRola, $idMiniaturaAlbum);
        }
        $postsRola[] = $idPostRola;
    }

    update_post_meta($idPostAlbum, 'album_rolas', $postsRola);
}