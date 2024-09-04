<?php

//ALGORITMO
function calcular_y_actualizar_puntuacion() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $query = new WP_Query(array(
        'post_type' => 'social_post',
        'posts_per_page' => -100,
        'date_query' => array('after' => date('Y-m-d', strtotime('-100 days')))
    ));

    $user_scores = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $author_id = get_post_field('post_author', $post_id);
            $likes = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id));
            
            $hours_since_publication = (current_time('timestamp') - get_post_time('U', true, $post_id)) / 360;
            $puntuacion_final = (100 + ($likes * 10)) * pow(0.75, floor($hours_since_publication));

            update_post_meta($post_id, '_post_puntuacion_final', $puntuacion_final);
            $user_scores[$author_id][] = $puntuacion_final;
        }
    }

    foreach ($user_scores as $user_id => $scores) {
        update_user_meta($user_id, '_average_user_score', array_sum($scores) / count($scores));
    }

    wp_reset_postdata();
}
calcular_y_actualizar_puntuacion();

function update_user_score_on_post_delete($post_id) {
    $author_id = get_post_field('post_author', $post_id);
    $user_scores = get_user_meta($author_id, '_user_scores', true);
    
    if ($user_scores) {
        $user_scores = array_filter($user_scores, fn($score) => $score['post_id'] != $post_id);
        update_user_meta($author_id, '_average_user_score', array_sum(array_column($user_scores, 'score')) / count($user_scores));
    }
}
add_action('delete_post', 'update_user_score_on_post_delete');

function reset_scores_and_recalculate() {
    array_map(fn($post) => delete_post_meta($post->ID, '_post_puntuacion_final'), get_posts(array(
        'post_type' => 'social_post',
        'posts_per_page' => -100
    )));
    
    array_map(function($user) {
        delete_user_meta($user->ID, '_average_user_score');
        delete_user_meta($user->ID, '_user_scores');
    }, get_users());

    calcular_y_actualizar_puntuacion();
}

if (!wp_next_scheduled('calcular_y_actualizar_puntuacion_hook')) {
    wp_schedule_event(time(), 'hourly', 'calcular_y_actualizar_puntuacion_hook');
}
add_action('calcular_y_actualizar_puntuacion_hook', 'reset_scores_and_recalculate');


function RecomendarUsuarios()
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


