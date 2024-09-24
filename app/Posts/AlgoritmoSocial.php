<?php

global $wpdb;
define('INTERES_TABLE', $wpdb->prefix . 'interes');
define('BATCH_SIZE', 1000);

function generarMetaDeIntereses($user_id)
{
    global $wpdb;

    $likePost = obtenerLikesDelUsuario($user_id, 500);
    if (empty($likePost)) {
        return false;
    }

    $interesesActuales = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $user_id
    ), OBJECT_K);

    $post_data = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_content, pm.meta_value
         FROM $wpdb->posts p
         LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
         WHERE p.ID IN ($placeholders)",
        ...$likePost
    ));

    if (empty($post_data)) {
        logAlgoritmo("No se encontraron datos para los posts con likes del usuario: $user_id");
        return false;
    }

    $tag_intensidad = array_reduce($post_data, function ($acc, $post) {
        if (!is_null($post->meta_value)) {
            $datosAlgoritmo = json_decode($post->meta_value, true);
        } else {
            $datosAlgoritmo = null;
        }

        if (!empty($datosAlgoritmo['tags'])) {
            foreach ($datosAlgoritmo['tags'] as $tag) {
                $acc[$tag] = ($acc[$tag] ?? 0) + 1;
            }
        }

        if (!empty($datosAlgoritmo['autor']['usuario'])) {
            $autor = $datosAlgoritmo['autor']['usuario'];
            $acc[$autor] = ($acc[$autor] ?? 0) + 1;
        }

        $palabras = array_filter(preg_split('/\s+/', strtolower(trim($post->post_content))));
        foreach ($palabras as $palabra) {
            $palabra = preg_replace('/[^a-z0-9]+/', '', $palabra);
            if (!empty($palabra)) {
                $acc[$palabra] = ($acc[$palabra] ?? 0) + 1;
            }
        }
        return $acc;
    }, []);

    return actualizarIntereses($user_id, $tag_intensidad, $interesesActuales);
}

function actualizarIntereses($user_id, $tag_intensidad, $interesesActuales)
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');
    try {
        $batch = [];

        foreach ($tag_intensidad as $interest => $intensity) {
            $batch[] = $wpdb->prepare(
                "(%d, %s, %d)",
                $user_id,
                $interest,
                $intensity
            );

            if (count($batch) >= BATCH_SIZE) {
                actualizarInteresesEnLote($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            actualizarInteresesEnLote($batch);
        }

        $intereses_a_eliminar = array_diff_key($interesesActuales, $tag_intensidad);
        if (!empty($intereses_a_eliminar)) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM " . INTERES_TABLE . " 
                 WHERE user_id = %d AND interest IN (" . implode(',', array_fill(0, count($intereses_a_eliminar), '%s')) . ")",
                array_merge([$user_id], array_keys($intereses_a_eliminar))
            ));
        }

        $wpdb->query('COMMIT');
        logAlgoritmo("Intereses actualizados exitosamente para el usuario: $user_id");
        return true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Error al actualizar intereses: ' . $e->getMessage());
        logAlgoritmo("Error al actualizar intereses: " . $e->getMessage());
        return false;
    }
}

function actualizarInteresesEnLote($batch)
{
    global $wpdb;
    $wpdb->query(
        "INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity) 
        VALUES " . implode(', ', $batch) . "
        ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)"
    );
}

function calcularFeedPersonalizado($userId)
{
    global $wpdb;
    $table_likes = $wpdb->prefix . 'post_likes';
    $table_intereses = $wpdb->prefix . 'interes';

    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
    $seguidores = (array) get_user_meta($userId, 'seguidores', true);

    generarMetaDeIntereses($userId);
    logAlgoritmo("Intereses del usuario generados para el usuario ID: $userId");

    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);

    logAlgoritmo("Intereses del usuario obtenidos: " . json_encode($interesesUsuario));

    $query = new WP_Query([
        'post_type' => 'social_post',
        'posts_per_page' => -1,
        'date_query' => [
            'after' => date('Y-m-d', strtotime('-100 days'))
        ]
    ]);

    logAlgoritmo("Consulta de posts realizada, total de posts: " . $query->found_posts);

    $posts_personalizados = [];
    $resumenPuntos = [];


    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $autor_id = get_post_field('post_author', $post_id);
        
        $puntosFinal = 0;

        $datosAlgoritmo = json_decode(get_post_meta($post_id, 'datosAlgoritmo', true), true) ?? [];

        $puntosUsuario = in_array($autor_id, $siguiendo) ? 50 : 0;

        $puntosIntereses = 0;
        if (!empty($datosAlgoritmo['tags'])) {
            foreach ($datosAlgoritmo['tags'] as $tag) {
                if (isset($interesesUsuario[$tag])) {
                    $puntosIntereses += 10 + $interesesUsuario[$tag]->intensity;
                }
            }
        }

        $likes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_likes WHERE post_id = %d",
            $post_id
        ));
        $puntosLikes = 5 + $likes;

        $horasDesdePublicacion = (current_time('timestamp') - get_post_time('U', true)) / 3600;
        $factorTiempo = pow(0.98, $horasDesdePublicacion); 

        $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * $factorTiempo;

        $posts_personalizados[$post_id] = $puntosFinal;
        $resumenPuntos[] = $post_id . ':' . round($puntosFinal, 2);
    }

    arsort($posts_personalizados);
    wp_reset_postdata();

    logAlgoritmo("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($posts_personalizados));

    logAlgoritmo("Resumen de puntos - " . implode(', ', $resumenPuntos));

    return $posts_personalizados;
}

