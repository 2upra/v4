<?php


/*
Me proposiste este  algoritmo pero no tengo la meta de intereses
entiendo que esto regresa una lista de post, pero aun no entiendo como se relaciona los post entre los usuarios, etc
*/

function calcularFeedPersonalizado($userId) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'post_likes';
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
    $seguidores = (array) get_user_meta($userId, 'seguidores', true);

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

        // 1. **Priorizar publicaciones de usuarios que el usuario sigue**
        if (in_array($autor_id, $siguiendo)) {
            $puntosUsuario += 50; // Asignar puntos altos por seguir al autor
        }

        // 2. **Verificar si los intereses (tags) coinciden**
        $interesesUsuario = get_user_meta($userId, 'intereses', true); // Los intereses del usuario
        $tagsPost = $datosAlgoritmo['tags'];

        foreach ($tagsPost as $tag) {
            if (in_array($tag, $interesesUsuario)) {
                $puntosIntereses += 10; // Puntos por cada coincidencia en intereses
            }
        }

        // 3. **Número de likes en la publicación**
        $likes = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id));
        $puntosLikes = $likes * 5; // Cada "me gusta" aumenta la relevancia

        // 4. **Tiempo de publicación (decay factor)**
        $horasDesdePublicacion = (current_time('timestamp') - get_post_time('U', true, $post_id)) / 3600;
        $factorTiempo = pow(0.9, floor($horasDesdePublicacion / 24)); // Decae un 10% por día

        // 5. **Calcular puntuación final**
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