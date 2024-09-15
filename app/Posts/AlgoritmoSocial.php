<?php


global $wpdb;  
define('INTERES_TABLE', $wpdb->prefix . 'interes');
define('BATCH_SIZE', 1000);

/*
function obtenerLikesDelUsuario($user_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $liked_posts = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    if (empty($liked_posts)) {
        return array();
    }

    return $liked_posts;
}

mysql> DESCRIBE wpsg_post_likes;
+-----------+-----------------+------+-----+-------------------+-------------------+
| Field     | Type            | Null | Key | Default           | Extra             |
+-----------+-----------------+------+-----+-------------------+-------------------+
| like_id   | bigint unsigned | NO   | PRI | NULL              | auto_increment    |
| user_id   | bigint unsigned | NO   |     | NULL              |                   |
| post_id   | bigint unsigned | NO   | MUL | NULL              |                   |
| like_date | datetime        | NO   |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
+-----------+-----------------+------+-----+-------------------+-------------------+

la tabla si tiene datos y existe, y el usuario que estoy comprobando si tiene likes por esto dice que no hay likes para el usuario

*/

function generarMetaDeIntereses($user_id) {
    global $wpdb;

    // Obtener todos los posts con likes del usuario
    $liked_posts = obtenerLikesDelUsuario($user_id);
    if (empty($liked_posts)) {
        guardarLog("No hay posts con likes para el usuario: $user_id");
        return false;
    }

    // Obtener intereses actuales del usuario
    $current_interests = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $user_id
    ), OBJECT_K);

    // Obtener metadatos y contenido de los posts con likes
    $placeholders = implode(',', array_fill(0, count($liked_posts), '%d'));
    $post_data = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_content, pm.meta_value 
         FROM $wpdb->posts p
         LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
         WHERE p.ID IN ($placeholders)",
        ...$liked_posts
    ));

    if (empty($post_data)) {
        guardarLog("No se encontraron datos para los posts con likes del usuario: $user_id");
        return false;
    }

    // Procesar los intereses del usuario
    $tag_intensidad = array_reduce($post_data, function($acc, $post) {
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

    // Actualizar intereses del usuario
    return actualizarIntereses($user_id, $tag_intensidad, $current_interests);
}

function actualizarIntereses($user_id, $tag_intensidad, $current_interests) {
    global $wpdb;

    $wpdb->query('START TRANSACTION');
    try {
        $batch = [];

        foreach ($tag_intensidad as $interest => $intensity) {
            $current_intensity = $current_interests[$interest]->intensity ?? 0;
            $intensity_change = $intensity - $current_intensity;

            $batch[] = $wpdb->prepare(
                "(%d, %s, %d)", $user_id, $interest, $intensity
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
        $intereses_a_eliminar = array_diff_key($current_interests, $tag_intensidad);
        if (!empty($intereses_a_eliminar)) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM " . INTERES_TABLE . " 
                 WHERE user_id = %d AND interest IN (" . implode(',', array_fill(0, count($intereses_a_eliminar), '%s')) . ")",
                array_merge([$user_id], array_keys($intereses_a_eliminar))
            ));
        }

        $wpdb->query('COMMIT');
        guardarLog("Intereses actualizados exitosamente para el usuario: $user_id");
        return true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Error al actualizar intereses: ' . $e->getMessage());
        guardarLog("Error al actualizar intereses: " . $e->getMessage());
        return false;
    }
}

function actualizarInteresesEnLote($batch) {
    global $wpdb;
    $wpdb->query(
        "INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity) 
        VALUES " . implode(', ', $batch) . "
        ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)"
    );
}


function calcularFeedPersonalizado($userId) {
    global $wpdb;

    // Tablas necesarias
    $table_likes = $wpdb->prefix . 'post_likes';
    $table_intereses = $wpdb->prefix . 'interes';

    // Obtener listas de seguimiento y seguidores del usuario
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
    $seguidores = (array) get_user_meta($userId, 'seguidores', true);

    // Generar o actualizar los intereses del usuario
    generarMetaDeIntereses($userId);
    guardarLog("Intereses del usuario generados para el usuario ID: $userId");

    // Obtener intereses del usuario
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);

    guardarLog("Intereses del usuario obtenidos: " . json_encode($interesesUsuario));

    // Consultar los posts en los últimos 100 días
    $query = new WP_Query([
        'post_type' => 'social_post',
        'posts_per_page' => -1,
        'date_query' => [
            'after' => date('Y-m-d', strtotime('-100 days'))
        ]
    ]);

    guardarLog("Consulta de posts realizada, total de posts: " . $query->found_posts);

    $posts_personalizados = [];

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

    }

    // Ordenar los posts por puntuación descendente
    arsort($posts_personalizados);
    wp_reset_postdata();

    // Log final del proceso
    guardarLog("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($posts_personalizados));

    return $posts_personalizados;
}



/*
function updateUserScore($post_id) {
    $author_id = get_post_field('post_author', $post_id);
    $user_scores = get_user_meta($author_id, '_user_scores', true);
    
    if (is_array($user_scores)) {
        $user_scores = array_column(array_filter($user_scores, function($score) use ($post_id) {
            return $score['post_id'] != $post_id;
        }), 'score');

        if ($user_scores) {
            update_user_meta($author_id, '_average_user_score', array_sum($user_scores) / count($user_scores));
        } else {
            delete_user_meta($author_id, '_average_user_score');
        }
    }
}
add_action('delete_post', 'updateUserScore');

function reset_scores_and_recalculate() {
    global $wpdb;

    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_post_puntuacion_final'");
    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key IN ('_average_user_score', '_user_scores')");

    postTop();
}

if (!wp_next_scheduled('postTop_hook')) {
    wp_schedule_event(time(), 'hourly', 'postTop_hook');
}
add_action('postTop_hook', 'reset_scores_and_recalculate');

function recomendarUsuarios() {
    ob_start();
    $current_user_id = get_current_user_id();
    $following = get_user_meta($current_user_id, 'siguiendo', true) ?: [];

    $users = (new WP_User_Query([
        'exclude' => $following,
        'meta_key' => '_average_user_score',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'number' => 3
    ]))->get_results();

    echo "<div class='LKIRWH'>";
    foreach ($users as $user) {
        $user_id = $user->ID;
        $user_url = esc_url(get_author_posts_url($user_id));
        $avatar_url = esc_url(imagenPerfil($user_id));
        $display_name = esc_html($user->display_name);
        $is_following = in_array($user_id, $following);
        $btn_class = $is_following ? 'RQZEWL' : 'MBTHLA';
        $btn_text = $is_following ? 'Dejar de seguir' : 'Seguir';

        echo "<div class='GDZTMT'>
                <a href='$user_url' class='IRBSEZ'>
                    <img src='$avatar_url' alt='Avatar' class='LOQTXE'>
                </a>
                <div class='PEZRWX'>
                    <a href='$user_url' class='XJHTRG'>
                        <span class='WZKLVN'>$display_name</span>
                    </a>
                    <button class='$btn_class' data-seguidor-id='$current_user_id' data-seguido-id='$user_id'>$btn_text</button>
                    <span class='YGCWFT' style='display:none;'>" . get_user_meta($user_id, '_average_user_score', true) . "</span>
                </div>
            </div>";
    }
    echo "</div>";

    return ob_get_clean();
}

*/