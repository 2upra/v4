<?php 

function saberSi($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';

    $last_run = get_user_meta($user_id, 'ultima_ejecucion_saber', true);
    $current_time = current_time('timestamp');

    if ($last_run && ($current_time - $last_run < 1)) {
        return; 
    }
    update_user_meta($user_id, 'ultima_ejecucion_saber', $current_time);

    //Saber si le gusta una rola
    $liked_posts = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    if (empty($liked_posts)) {
        update_user_meta($user_id, 'leGustaAlMenosUnaRola', false);
        return;
    }

    $rola_posts = get_posts(array(
        'post__in' => $liked_posts,
        'meta_query' => array(
            array(
                'key' => 'rola',
                'value' => 'true',
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    $le_gusta_rola = !empty($rola_posts);
    update_user_meta($user_id, 'leGustaAlMenosUnaRola', $le_gusta_rola);
}
