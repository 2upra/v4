<?



define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_feed_progress');

function recalcularSimilarToFeed() {
    guardarLog("Iniciando ejecución del cron 'recalcular_similar_to_feed_cron'");
    
    $last_processed_post_id = get_option(SIMILAR_TO_PROGRESS_OPTION, 0);
    guardarLog("Último post procesado ID: $last_processed_post_id");
    
    // Obtenemos varios posts en lugar de solo uno
    $args = array(
        'post_type' => 'social_post',
        'post_status' => 'publish',
        'posts_per_page' => 5, // Procesamos hasta 5 posts por ejecución
        'meta_query' => array(
            array(
                'key' => 'datosAlgoritmo',
                'compare' => 'EXISTS'
            )
        ),
        'orderby' => 'ID',
        'order' => 'ASC',
        'suppress_filters' => true,
        'fields' => 'ids'
    );

    if ($last_processed_post_id > 0) {
        $args['post__gt'] = $last_processed_post_id;
    }
    
    $query = new WP_Query($args);
    $posts_to_process = $query->posts;
    
    guardarLog("Cantidad de posts encontrados para procesar: " . count($posts_to_process));
    
    $posts_processed = 0;
    
    if (!empty($posts_to_process)) {
        foreach ($posts_to_process as $post_id) {
            guardarLog("Procesando post ID: " . $post_id);

            $similar_to_cache_key = "similar_to_{$post_id}";
            guardarLog("Verificando cache key: $similar_to_cache_key");
            
            $cached_data = get_transient($similar_to_cache_key);

            if (!$cached_data) {
                guardarLog("Cache no encontrada, calculando feed personalizado para similar_to: $post_id");
                $posts_personalizados = calcularFeedPersonalizado(44, '', $post_id);
                
                if (!$posts_personalizados) {
                    guardarLog("Error: No se pudo calcular el feed para 'similar_to_{$post_id}'");
                } else {
                    guardarLog("Feed calculado exitosamente, guardando en caché");
                    set_transient($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);
                }
            } else {
                guardarLog("Cache encontrada para $similar_to_cache_key");
            }

            $posts_processed++;
            guardarLog("Actualizando opción de progreso a ID: " . $post_id);
            update_option(SIMILAR_TO_PROGRESS_OPTION, $post_id);
        }
        
        guardarLog("Posts procesados en esta ejecución: $posts_processed");
    } else {
        guardarLog("No se encontraron más posts para procesar, reiniciando progreso");
        update_option(SIMILAR_TO_PROGRESS_OPTION, 0);
    }
    
    guardarLog("Finalización de la ejecución del cron");
}
function agregarCron30Segundos() {
    // Solo logueamos si realmente vamos a hacer algo
    if (!wp_next_scheduled('recalcular_similar_to_feed_cron_30sec')) {
        guardarLog("Programando nuevo evento cron de 30 segundos");
        $scheduled = wp_schedule_event(time(), 'every_30_seconds', 'recalcular_similar_to_feed_cron_30sec');
        if (!$scheduled) {
            guardarLog("Error: No se pudo programar el evento cron");
        }
    }
}

function agregar_cron_30_segundos($schedules) {
    // Eliminamos los logs de esta función ya que se llama frecuentemente
    if (!isset($schedules['every_30_seconds'])) {
        $schedules['every_30_seconds'] = [
            'interval' => 30,
            'display' => 'Cada 30 segundos',
        ];
    }
    return $schedules;
}

// Movemos estas acciones a un archivo que se cargue solo una vez
add_action('init', 'agregarCron30Segundos');
add_filter('cron_schedules', 'agregar_cron_30_segundos');
add_action('recalcular_similar_to_feed_cron_30sec', 'recalcularSimilarToFeed');