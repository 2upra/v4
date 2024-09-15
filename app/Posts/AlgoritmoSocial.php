<?php


global $wpdb;
define('INTERES_TABLE', $wpdb->prefix . 'interes');
define('BATCH_SIZE', 1000);


function generarMetaDeIntereses($user_id)
{
    global $wpdb;
    // Obtener todos los posts con likes del usuario
    $likePost = obtenerLikesDelUsuario($user_id);
    if (empty($likePost)) {
        return false;
    }
    // Obtener intereses actuales del usuario
    $interesesActuales = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $user_id
    ), OBJECT_K);
    // Obtener metadatos y contenido de los posts con likes
    $placeholders = implode(',', array_fill(0, count($likePost), '%d'));
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
    // Procesar los intereses del usuario
    $tag_intensidad = array_reduce($post_data, function ($acc, $post) {
        $datosAlgoritmo = json_decode($post->meta_value, true);

        // Procesar tags
        if (!empty($datosAlgoritmo['tags'])) {
            foreach ($datosAlgoritmo['tags'] as $tag) {
                $acc[$tag] = ($acc[$tag] ?? 0) + 1;
            }
        }
        // Procesar autor
        if (!empty($datosAlgoritmo['autor']['usuario'])) {
            $autor = $datosAlgoritmo['autor']['usuario'];
            $acc[$autor] = ($acc[$autor] ?? 0) + 1;
        }
        // Procesar palabras del contenido del post
        $palabras = array_filter(explode(' ', strtolower(trim($post->post_content))));
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
            $current_intensity = $interesesActuales[$interest]->intensity ?? 0;
            $intensity_change = $intensity - $current_intensity;

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
        // Eliminar intereses que ya no existen
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
    // Tablas necesarias
    $table_likes = $wpdb->prefix . 'post_likes';
    $table_intereses = $wpdb->prefix . 'interes';
    // Obtener listas de seguimiento y seguidores del usuario
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
    $seguidores = (array) get_user_meta($userId, 'seguidores', true);
    // Generar o actualizar los intereses del usuario
    generarMetaDeIntereses($userId);
    logAlgoritmo("Intereses del usuario generados para el usuario ID: $userId");
    // Obtener intereses del usuario
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);

    logAlgoritmo("Intereses del usuario obtenidos: " . json_encode($interesesUsuario));
    // Consultar los posts en los últimos 100 días
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
    // Procesar cada post en el query
    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $autor_id = get_post_field('post_author', $post_id);
        $puntosFinal = 0;
        // Obtener datos del post
        $datosAlgoritmo = json_decode(get_post_meta($post_id, 'datosAlgoritmo', true), true) ?? [];
        // 1. Puntuación por seguimiento
        $puntosUsuario = in_array($autor_id, $siguiendo) ? 50 : 0;
        // 2. Puntuación por intereses (tags)
        $puntosIntereses = 0;
        if (!empty($datosAlgoritmo['tags'])) {
            foreach ($datosAlgoritmo['tags'] as $tag) {
                if (isset($interesesUsuario[$tag])) {
                    $puntosIntereses += 10 * $interesesUsuario[$tag]->intensity;
                }
            }
        }
        // 3. Puntuación por likes
        $likes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_likes WHERE post_id = %d",
            $post_id
        ));
        $puntosLikes = $likes * 5;
        // 4. Factor de tiempo (decay)
        $horasDesdePublicacion = (current_time('timestamp') - get_post_time('U', true)) / 3600;
        $factorTiempo = pow(0.9, floor($horasDesdePublicacion / 24)); // Factor de decaimiento diario
        // 5. Puntuación final
        $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * $factorTiempo;
        // Guardar la puntuación del post
        $posts_personalizados[$post_id] = $puntosFinal;
        $resumenPuntos[] = $post_id . ':' . round($puntosFinal, 2);
    }

    // Ordenar los posts por puntuación descendente
    arsort($posts_personalizados);
    wp_reset_postdata();
    // Log final del proceso
    logAlgoritmo("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($posts_personalizados));
    // Log de resumen de puntos en una sola línea
    logAlgoritmo("Resumen de puntos - " . implode(', ', $resumenPuntos));
    return $posts_personalizados;
}
