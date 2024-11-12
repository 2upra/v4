<?

define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_feed_progress');

function recalcularSimilarToFeed() {
    guardarLog("Iniciando ejecución del cron 'recalcular_similar_to_feed_cron'");
    
    $last_processed_post_id = get_option(SIMILAR_TO_PROGRESS_OPTION, 0);
    guardarLog("Último post procesado ID: $last_processed_post_id");
    
    $args = [
        'numberposts' => 1,
        'post_type' => 'social_post',
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'ASC',
        'post__gt' => $last_processed_post_id,
        'meta_query' => array(
            array(
                'key' => 'datosAlgoritmo',
                'compare' => 'EXISTS'
            )
        )
    ];
    
    $posts_to_process = get_posts($args);
    guardarLog("Cantidad de posts encontrados para procesar: " . count($posts_to_process));
    
    if ($posts_to_process) {
        $post = $posts_to_process[0];
        guardarLog("Procesando post ID: " . $post->ID);

        if ($post->ID) {
            $similar_to = $post->ID;
            $similar_to_cache_key = "similar_to_{$similar_to}";
            guardarLog("Verificando cache key: $similar_to_cache_key");
            
            $cached_data = get_transient($similar_to_cache_key);

            if (!$cached_data) {
                guardarLog("Cache no encontrada, calculando feed personalizado para similar_to: $similar_to");
                $posts_personalizados = calcularFeedPersonalizado(44, '', $similar_to);
                
                if (!$posts_personalizados) {
                    guardarLog("Error: No se pudo calcular el feed para 'similar_to_{$similar_to}'");
                } else {
                    guardarLog("Feed calculado exitosamente, guardando en caché");
                    set_transient($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);
                }
            } else {
                guardarLog("Cache encontrada para $similar_to_cache_key");
            }

            guardarLog("Actualizando opción de progreso a ID: " . $post->ID);
            update_option(SIMILAR_TO_PROGRESS_OPTION, $post->ID);
        }
    } else {
        guardarLog("No se encontraron más posts para procesar, reiniciando progreso");
        delete_option(SIMILAR_TO_PROGRESS_OPTION);
    }
    guardarLog("Finalización de la ejecución del cron");
}

// Para evitar el bucle en los logs, modificamos estas funciones
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