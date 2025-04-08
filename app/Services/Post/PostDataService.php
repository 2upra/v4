<?php

// Archivo creado para contener funciones auxiliares de obtención de datos de posts.
// Originalmente, estas funciones estaban o estarán en PostService.php.

// Refactor(Org): Función variablesPosts() movida desde PostService.php
#Define las variables de los posts
function variablesPosts($idPost = null)
{
    if ($idPost === null) {
        global $post;
        $idPost = $post->ID;
    }

    $idUsuarioActual = get_current_user_id();
    $autoresSuscritos = get_user_meta($idUsuarioActual, 'offering_user_ids', true);
    $idAutor = get_post_field('post_author', $idPost);

    $datosAlgoritmo = get_post_meta($idPost, 'datosAlgoritmo', true);
    $datosAlgoritmoRespaldo = get_post_meta($idPost, 'datosAlgoritmo_respaldo', true);

    if (is_array($datosAlgoritmoRespaldo)) {
        $datosAlgoritmoRespaldo = json_encode($datosAlgoritmoRespaldo);
    } elseif (is_object($datosAlgoritmoRespaldo)) {
        $datosAlgoritmoRespaldo = serialize($datosAlgoritmoRespaldo);
    }

    $datosAlgoritmoFinal = empty($datosAlgoritmo) ? $datosAlgoritmoRespaldo : $datosAlgoritmo;

    return [
        'current_user_id' => $idUsuarioActual,
        'autores_suscritos' => $autoresSuscritos,
        'author_id' => $idAutor,
        'es_suscriptor' => in_array($idAutor, (array)$autoresSuscritos),
        'author_name' => get_the_author_meta('display_name', $idAutor),
        'author_avatar' => imagenPerfil($idAutor),
        'audio_id_lite' => get_post_meta($idPost, 'post_audio_lite', true),
        'audio_id' => get_post_meta($idPost, 'post_audio', true),
        'audio_url' => wp_get_attachment_url(get_post_meta($idPost, 'post_audio', true)),
        'audio_lite' => wp_get_attachment_url(get_post_meta($idPost, 'post_audio_lite', true)),
        'wave' => get_post_meta($idPost, 'waveform_image_url', true),
        'post_date' => get_the_date('', $idPost),
        'block' => get_post_meta($idPost, 'esExclusivo', true),
        'colab' => get_post_meta($idPost, 'paraColab', true),
        'post_status' => get_post_status($idPost),
        'bpm' => get_post_meta($idPost, 'audio_bpm', true),
        'key' => get_post_meta($idPost, 'audio_key', true),
        'scale' => get_post_meta($idPost, 'audio_scale', true),
        'detallesIA' => get_post_meta($idPost, 'audio_descripcion', true),
        'datosAlgoritmo' => $datosAlgoritmoFinal,
        'postAut' => get_post_meta($idPost, 'postAut', true),
        'ultimoEdit' => get_post_meta($idPost, 'ultimoEdit', true),
    ];
}

// TODO: Mover otras funciones relacionadas aquí desde PostService.php.

