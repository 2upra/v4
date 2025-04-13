<?


function multiplesPost($postIdOriginal)
{
    if (!get_post($postIdOriginal)) {
        error_log("El post con ID: {$postIdOriginal} no existe.");
        return;
    }

    // Verifica si el post es del tipo 'social_post'
    if (get_post_type($postIdOriginal) !== 'social_post') {
        error_log("El post con ID: {$postIdOriginal} no es del tipo 'social_post'.");
        return;
    }

    // Verifica si el post tiene el meta 'multiple' igual a 1
    $isMultiple = get_post_meta($postIdOriginal, 'multiple', true);
    if ($isMultiple !== '1') {
        error_log("El post con ID: {$postIdOriginal} no tiene el meta 'multiple' igual a 1.");
        return;
    }

    $author_id = get_post_field('post_author', $postIdOriginal);
    $paraColab = get_post_meta($postIdOriginal, 'paraColab', true);
    $paraDescarga = get_post_meta($postIdOriginal, 'paraDescarga', true);
    $artista = get_post_meta($postIdOriginal, 'artista', true);
    $fan = get_post_meta($postIdOriginal, 'fan', true);
    $rola = get_post_meta($postIdOriginal, 'rola', true);
    $sample = get_post_meta($postIdOriginal, 'sample', true);
    $tagsUsuario = get_post_meta($postIdOriginal, 'tagsUsuario', true);
    $tienda = get_post_meta($postIdOriginal, 'tienda', true);
    $nombreLanzamiento = get_post_meta($postIdOriginal, 'nombreLanzamiento', true);

    list($multiples_audios_encontrados, $ids_nuevos_posts) = procesarAudiosMultiples($postIdOriginal, $author_id, $paraColab, $paraDescarga, $artista, $fan, $rola, $sample, $tagsUsuario, $tienda, $nombreLanzamiento);

    if (!$multiples_audios_encontrados) {
        delete_post_meta($postIdOriginal, 'multiple');
    } else {
        // Verifica si hay IDs de nuevos posts antes de actualizar
        if (!empty($ids_nuevos_posts)) {
            update_post_meta($postIdOriginal, 'posts_generados', $ids_nuevos_posts);
        }
        $quedan_audios = false;
        for ($i = 2; $i <= 30; $i++) {
            if (get_post_meta($postIdOriginal, 'post_audio_lite_' . $i, true)) {
                $quedan_audios = true;
                break;
            }
        }
        if (!$quedan_audios) {
            delete_post_meta($postIdOriginal, 'multiple');
        }
    }
}
