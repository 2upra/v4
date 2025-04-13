<?php

function optimizar64kAudios($limite = 10000)
{
    $query = new WP_Query(array(
        'post_type' => 'social_post',
        'meta_query' => array(
            'relation' => 'AND', 
            array(
                'key' => 'audio_optimizado',
                'compare' => 'NOT EXISTS' 
            ),
            array(
                'key' => 'rola',
                'value' => '1',
                'compare' => '!=' 
            )
        ),
        'posts_per_page' => $limite,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_term_cache' => false, 
        'update_post_meta_cache' => false 
    ));

    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            optimizarAudioPost($post_id);
        }
    }

    wp_reset_postdata(); 
}