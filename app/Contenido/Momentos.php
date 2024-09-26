<?php 


function delete_expired_momentos() {
    $args = array(
        'post_type' => 'social_post',
        'meta_query' => array(
            array(
                'key' => 'momento',
                'value' => true,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    );

    $momentos = get_posts($args);

    foreach ($momentos as $momento) {
        // Verificar si el post lleva más de 24 horas publicado
        $post_date_timestamp = strtotime($momento->post_date);
        if (time() - $post_date_timestamp > 24 * 60 * 60) {
            // Obtener los archivos adjuntos
            $attachments = get_attached_media('', $momento->ID);
            
            // Borrar cada archivo adjunto
            foreach ($attachments as $attachment) {
                wp_delete_attachment($attachment->ID, true);
            }
            
            // Borrar el post
            wp_delete_post($momento->ID, true);
        }
    }
}

if (!wp_next_scheduled('delete_expired_momentos_hook')) {
    wp_schedule_event(time(), 'hourly', 'delete_expired_momentos_hook');
}
add_action('delete_expired_momentos_hook', 'delete_expired_momentos');
