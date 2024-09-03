<?php

//ALGORITMO
function calcular_y_actualizar_puntuacion()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $args = array(
        'post_type' => 'social_post',
        'posts_per_page' => -100,
        'date_query' => array(
            'after' => date('Y-m-d', strtotime('-100 days'))
        )
    );

    $query = new WP_Query($args);
    $user_scores = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $author_id = get_post_field('post_author', $post_id);
            $PI = 100;
            $LP = 10;
            $D = .75;

            // Obtenemos la cantidad de "likes" de la nueva tabla
            $likes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
                $post_id
            ));

            $publicacion_time = get_post_time('U', true, $post_id);
            $current_time = current_time('timestamp');
            $hours_since_publication = ($current_time - $publicacion_time) / 360;
            $decaimiento_aplicado = pow($D, floor($hours_since_publication));
            $puntuacion_final = ($PI + ($likes * $LP)) * $decaimiento_aplicado;

            update_post_meta($post_id, '_post_puntuacion_final', $puntuacion_final);

            if (!isset($user_scores[$author_id])) {
                $user_scores[$author_id] = array();
            }
            $user_scores[$author_id][] = $puntuacion_final;
        }
    }

    foreach ($user_scores as $user_id => $scores) {
        $average_score = array_sum($scores) / count($scores);
        update_user_meta($user_id, '_average_user_score', $average_score);
    }

    wp_reset_postdata();
}
calcular_y_actualizar_puntuacion();

function update_user_score_on_post_delete($post_id)
{
    $author_id = get_post_field('post_author', $post_id);
    $user_scores = get_user_meta($author_id, '_user_scores', true);
    if ($user_scores) {
        $user_scores = array_filter($user_scores, function ($score) use ($post_id) {
            return $score['post_id'] != $post_id;
        });
        $average_score = array_sum(array_column($user_scores, 'score')) / count($user_scores);
        update_user_meta($author_id, '_average_user_score', $average_score);
    }
}


add_action('delete_post', 'update_user_score_on_post_delete');


function reset_scores_and_recalculate()
{

    $posts = get_posts(array(
        'post_type' => 'social_post',
        'posts_per_page' => -100,
    ));
    foreach ($posts as $post) {
        delete_post_meta($post->ID, '_post_puntuacion_final');
    }
    $users = get_users();
    foreach ($users as $user) {
        delete_user_meta($user->ID, '_average_user_score');
        delete_user_meta($user->ID, '_user_scores');
    }

    // Recalcular puntuaciones y promedios
    calcular_y_actualizar_puntuacion();
}


if (!wp_next_scheduled('calcular_y_actualizar_puntuacion_hook')) {
    wp_schedule_event(time(), 'hourly', 'calcular_y_actualizar_puntuacion_hook');
}

add_action('calcular_y_actualizar_puntuacion_hook', 'reset_scores_and_recalculate');


function RecomendarUsuarios($atts)
{
    ob_start();

    $current_user_id = get_current_user_id();
    $following = get_user_meta($current_user_id, 'siguiendo', true);
    if (!is_array($following)) {
        $following = array();
    }

    $user_query = new WP_User_Query(array(
        'exclude' => $following,
        'meta_key' => '_average_user_score',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'number' => 3
    ));

    $users = $user_query->get_results();
?>
    <div class='LKIRWH'>
        <?php foreach ($users as $user) :
            $user_id = $user->ID;
            $user_url = esc_url(get_author_posts_url($user_id));
        ?>
            <div class='GDZTMT'>
                <a href='<?php echo $user_url; ?>' class='IRBSEZ'>
                    <img src='<?php echo esc_url(obtener_url_imagen_perfil_o_defecto($user_id)); ?>' alt='Avatar' class='LOQTXE'>
                </a>
                <div class='PEZRWX'>
                    <a href='<?php echo $user_url; ?>' class='XJHTRG'>
                        <span class='WZKLVN'><?php echo esc_html($user->display_name); ?></span>
                    </a>
                    <?php if (in_array($user_id, $following)) : ?>
                        <button class="RQZEWL" data-seguidor-id="<?php echo esc_attr($current_user_id); ?>" data-seguido-id="<?php echo esc_attr($user_id); ?>">Dejar de seguir</button>
                    <?php else : ?>
                        <button class="MBTHLA" data-seguidor-id="<?php echo esc_attr($current_user_id); ?>" data-seguido-id="<?php echo esc_attr($user_id); ?>">Seguir</button>
                    <?php endif; ?>
                    <span class='YGCWFT' style='display:none;'><?php echo get_user_meta($user_id, '_average_user_score', true); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php
    return ob_get_clean();
}
add_shortcode('RecomendarUsuarios', 'RecomendarUsuarios');


function show_top_listeners($atts)
{
    $all_users = get_users();
    $user_listener_counts = [];

    foreach ($all_users as $user) {
        $user_id = $user->ID;
        $listener_count = contar_oyentes_unicos($user_id);
        $user_listener_counts[$user_id] = $listener_count;
    }
    arsort($user_listener_counts);
    $top_listeners = array_slice($user_listener_counts, 0, 3, true);
    reset($top_listeners);
    $top_listener_id = key($top_listeners);
    $top_listener = get_user_by('id', $top_listener_id);
    $author_username = $top_listener->user_nicename;
    $music_profile_url = esc_url(home_url('/music/' . $author_username));

    $output = "<div class='algtop-top-listeners-container'>";
    foreach ($top_listeners as $user_id => $listener_count) {
        $user = get_user_by('id', $user_id);
        $user_url = esc_url(get_author_posts_url($user_id));
        $output .= "<div class='algtop-user-profile'>";
        $output .= "<a href='" . $music_profile_url . "' class='algtop-profile-link'>";
        $output .= "<img src='" . esc_url(obtener_url_imagen_perfil_o_defecto($user_id)) . "' alt='Avatar' class='algtop-profile-picture'>";
        $output .= "</a>";
        $output .= "<div class='algtop-user-info'>";
        $output .= "<a href='" . $user_url . "' class='algtop-profile-link'>";
        $output .= "<span class='user-name'>" . esc_html($user->display_name) . "</span>";
        $output .= "</a>";
        $output .= "<span class='listener-count'>Oyentes: " . $listener_count . "</span>";
        $output .= "</div>";
        $output .= "</div>";
    }
    $output .= "</div>";

    return $output;
}
add_shortcode('top_listeners', 'show_top_listeners');
