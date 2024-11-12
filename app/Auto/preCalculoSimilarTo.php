<?

define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_feed_progress');

function recalcularSimilarToFeed() {
    error_log("Cron 'recalcular_similar_to_feed_cron' se está ejecutando.");
    
    $last_processed_post_id = get_option(SIMILAR_TO_PROGRESS_OPTION, 0);
    
    $args = [
        'numberposts' => 1,
        'orderby' => 'date',
        'order' => 'ASC',
        'post__gt' => $last_processed_post_id,
    ];
    
    $posts_to_process = get_posts($args);
    
    if ($posts_to_process) {
        $post = $posts_to_process[0];

        if ($post->ID) {
            $similar_to = $post->ID;
            $similar_to_cache_key = "similar_to_{$similar_to}";
            $cached_data = get_transient($similar_to_cache_key);

            if (!$cached_data) {
                $posts_personalizados = calcularFeedPersonalizado(44, '', $similar_to);
                
                if (!$posts_personalizados) {
                    error_log("Error al calcular el feed para 'similar_to_{$similar_to}'");
                }
                
                set_transient($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);
            }

            update_option(SIMILAR_TO_PROGRESS_OPTION, $post->ID);
        }
    } else {
        delete_option(SIMILAR_TO_PROGRESS_OPTION);
    }
}

function agregarCron30Segundos() {
    if (!wp_next_scheduled('recalcular_similar_to_feed_cron_30sec')) {
        $scheduled = wp_schedule_event(time(), 'every_30_seconds', 'recalcular_similar_to_feed_cron_30sec');
        if (!$scheduled) {
            error_log("Error al programar el evento cron.");
        }
    }
}

function agregar_cron_30_segundos($schedules) {
    if (!isset($schedules['every_30_seconds'])) {
        $schedules['every_30_seconds'] = [
            'interval' => 30,
            'display' => 'Cada 30 segundos',
        ];
    }
    return $schedules;
}

add_action('init', 'agregarCron30Segundos');
add_filter('cron_schedules', 'agregar_cron_30_segundos');
add_action('recalcular_similar_to_feed_cron_30sec', 'recalcularSimilarToFeed');