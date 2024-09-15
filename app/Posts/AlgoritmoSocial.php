<?php


global $wpdb;  
define('INTERES_TABLE', $wpdb->prefix . 'interes');
define('BATCH_SIZE', 1000);

function generarMetaDeIntereses($user_id) {
    global $wpdb;

    // Obtener todos los posts con likes del usuario
    $liked_posts = obtenerLikesDelUsuario($user_id);

    // Obtener los intereses actuales del usuario
    $current_interests = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $user_id
    ), OBJECT_K);

    $tag_intensidad = [];

    if (!empty($liked_posts)) {
        // Obtener metadatos y contenido del post en una sola consulta
        $post_data = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_content, pm.meta_value 
             FROM $wpdb->posts p
             LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
             WHERE p.ID IN (" . implode(',', array_fill(0, count($liked_posts), '%d')) . ")",
            $liked_posts
        ));

        foreach ($post_data as $post) {
            $datosAlgoritmo = json_decode($post->meta_value, true);

            // Procesar tags
            if (isset($datosAlgoritmo['tags'])) {
                foreach ($datosAlgoritmo['tags'] as $tag) {
                    $tag_intensidad[$tag] = ($tag_intensidad[$tag] ?? 0) + 1;
                }
            }

            // Procesar autor
            if (isset($datosAlgoritmo['autor']['usuario'])) {
                $autor = $datosAlgoritmo['autor']['usuario'];
                $tag_intensidad[$autor] = ($tag_intensidad[$autor] ?? 0) + 1;
            }

            // Procesar texto
            $palabras = array_filter(explode(' ', strtolower(trim($post->post_content))));
            foreach ($palabras as $palabra) {
                $palabra = preg_replace('/[^a-z0-9]+/', '', $palabra);
                if (!empty($palabra)) {
                    $tag_intensidad[$palabra] = ($tag_intensidad[$palabra] ?? 0) + 1;
                }
            }
        }
    }

    // Actualizar intereses en lotes
    $wpdb->query('START TRANSACTION');

    try {
        $batch = [];
        foreach ($tag_intensidad as $interest => $intensity) {
            $current_intensity = $current_interests[$interest]->intensity ?? 0;
            $intensity_change = $intensity - $current_intensity;

            $batch[] = $wpdb->prepare(
                "(%d, %s, %d)",
                $user_id, $interest, $intensity
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
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Error al actualizar intereses: ' . $e->getMessage());
        return false;
    }

    return $tag_intensidad;
}

function actualizarInteresesEnLote($batch) {
    global $wpdb;

    $wpdb->query("INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity) 
                  VALUES " . implode(', ', $batch) . "
                  ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)");
}

function crearTablaUserInterests() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'interes'; 

    // Verifica si la tabla ya existe
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            interest varchar(255) NOT NULL,
            intensity int(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY user_interest_unique (user_id, interest),
            KEY user_id (user_id),
            KEY interest (interest)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
crearTablaUserInterests();

function calcularFeedPersonalizado($userId) {
    global $wpdb;

    $table_name_likes = $wpdb->prefix . 'post_likes';
    $table_name_intereses = $wpdb->prefix . 'interes';
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
    $seguidores = (array) get_user_meta($userId, 'seguidores', true);

    // Obtener los intereses del usuario desde la tabla
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_name_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);

    $query = new WP_Query([
        'post_type' => 'social_post',
        'posts_per_page' => -1,
        'date_query' => [
            'after' => date('Y-m-d', strtotime('-100 days'))
        ]
    ]);

    $posts_personalizados = [];

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $autor_id = get_post_field('post_author', $post_id);

        // Obtener los datos del algoritmo de la publicación
        $datosAlgoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
        $datosAlgoritmo = json_decode($datosAlgoritmo, true);

        // Inicializar variables de puntuación
        $puntosUsuario = 0;
        $puntosIntereses = 0;
        $puntosFinal = 0;

        // 1. Priorizar publicaciones de usuarios que el usuario sigue
        if (in_array($autor_id, $siguiendo)) {
            $puntosUsuario += 50;
        }

        // 2. Verificar si los intereses (tags) coinciden
        $tagsPost = $datosAlgoritmo['tags'];

        foreach ($tagsPost as $tag) {
            if (isset($interesesUsuario[$tag])) {
                $puntosIntereses += 10 * $interesesUsuario[$tag]->intensity;
            }
        }

        // 3. Número de likes en la publicación
        $likes = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name_likes WHERE post_id = %d", $post_id));
        $puntosLikes = $likes * 5;

        // 4. Tiempo de publicación (decay factor)
        $horasDesdePublicacion = (current_time('timestamp') - get_post_time('U', true, $post_id)) / 3600;
        $factorTiempo = pow(0.9, floor($horasDesdePublicacion / 24));

        // 5. Calcular puntuación final
        $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * $factorTiempo;

        // Guardar la puntuación final para este post
        $posts_personalizados[$post_id] = $puntosFinal;
    }

    arsort($posts_personalizados);
    wp_reset_postdata();
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