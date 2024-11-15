<?

// Asegúrate de definir estas constantes en tu código o en tu archivo de configuración.
define('SIMILAR_TO_PROCESS_LOCK', 'similar_to_process_lock');
define('SIMILAR_TO_MAX_LOCK_TIME', 300); // 5 minutos, ajusta según tus necesidades
define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_progress');
define('SIMILAR_TO_CACHED_COUNT_OPTION', 'similar_to_cached_count');
define('SIMILAR_TO_STOP_UNTIL_OPTION', 'similar_to_stop_until');
define('SIMILAR_TO_CONSECUTIVE_LIMIT', 100); // Límite de posts consecutivos con caché, ajusta según necesites
define('SIMILAR_TO_STOP_DURATION', 6 * HOUR_IN_SECONDS); // Detención de 6 horas

//es un calculo automatico que se ejecuta cada 30 seg para diferentes post para que agilizar la carga de los post 
function recalcularSimilarToFeed() {
    // Verificar si hay una detención prolongada activa
    $stop_until = get_option(SIMILAR_TO_STOP_UNTIL_OPTION, 0);
    if ($stop_until && time() < $stop_until) {
        return;
    } elseif ($stop_until && time() >= $stop_until) {
        delete_option(SIMILAR_TO_STOP_UNTIL_OPTION);
        update_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);
    }

    // Verificar si un proceso ya está en ejecución
    $lock_time = obtenerCache(SIMILAR_TO_PROCESS_LOCK);
    if ($lock_time && (time() - $lock_time < SIMILAR_TO_MAX_LOCK_TIME)) {
        return;
    } elseif ($lock_time) {
        borrarCache(SIMILAR_TO_PROCESS_LOCK);
    }

    // Establecer bloqueo
    guardarCache(SIMILAR_TO_PROCESS_LOCK, time(), SIMILAR_TO_MAX_LOCK_TIME);

    try {
        // Obtener el ID del último post procesado
        $last_processed_post_id = get_option(SIMILAR_TO_PROGRESS_OPTION, 0);

        global $wpdb;

        while (true) {
            // Obtener el siguiente post a procesar
            $query = $wpdb->prepare(
                "SELECT p.ID 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'social_post'
                AND p.post_status = 'publish'
                AND p.ID > %d
                AND pm.meta_key = 'datosAlgoritmo'
                ORDER BY p.ID ASC
                LIMIT 1",
                $last_processed_post_id
            );

            $post_id = $wpdb->get_var($query);

            if (!$post_id) {
                update_option(SIMILAR_TO_PROGRESS_OPTION, 0);
                update_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);
                break;
            }

            $similar_to_cache_key = "similar_to_$post_id";

            if (obtenerCache($similar_to_cache_key)) {
                update_option(SIMILAR_TO_PROGRESS_OPTION, $post_id);

                // Incrementar el contador de posts con caché
                $cached_count = get_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);
                $cached_count++;
                update_option(SIMILAR_TO_CACHED_COUNT_OPTION, $cached_count);

                // Verificar límite de posts con caché
                if ($cached_count >= SIMILAR_TO_CONSECUTIVE_LIMIT) {
                    $new_stop_until = time() + SIMILAR_TO_STOP_DURATION;
                    update_option(SIMILAR_TO_STOP_UNTIL_OPTION, $new_stop_until);
                    update_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);
                    break;
                }

                $last_processed_post_id = $post_id;
            } else {
                // Calcular posts similares y guardar en caché
                $posts_similares = calcularFeedPersonalizado(44, '', $post_id);

                if ($posts_similares) {
                    guardarCache($similar_to_cache_key, $posts_similares, 15 * DAY_IN_SECONDS);
                }

                update_option(SIMILAR_TO_PROGRESS_OPTION, $post_id);
                update_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);

                // Romper después de procesar un post
                break;
            }
        }

    } catch (Exception $e) {
        // Manejo de excepciones
    } finally {
        // Eliminar el bloqueo
        borrarCache(SIMILAR_TO_PROCESS_LOCK);
    }
}



function agregar_cron_30_segundos($schedules) {
    if (!isset($schedules['every_30_seconds'])) {
        $schedules['every_30_seconds'] = [
            'interval' => 30, 
            'display'  => __('Cada 30 segundos'),
        ];
    }
    return $schedules;
}
add_filter('cron_schedules', 'agregar_cron_30_segundos');

/**
 * Inicializar el evento cron si no está ya programado.
 */
function inicializar_cron() {
    if (!wp_next_scheduled('recalcular_similar_to_feed_cron')) {
        wp_schedule_event(time(), 'every_30_seconds', 'recalcular_similar_to_feed_cron');
        //guardarLog("Evento cron 'recalcular_similar_to_feed_cron' programado para cada 30 segundos.");
    }
}
add_action('init', 'inicializar_cron');

/**
 * Asignar la función 'recalcularSimilarToFeed' al evento cron.
 */
add_action('recalcular_similar_to_feed_cron', 'recalcularSimilarToFeed');

/**
 * Función para limpiar el bloqueo manualmente.
 * Puedes llamar a esta función desde cualquier lugar (por ejemplo, un botón en el admin) para limpiar el bloqueo.
 */
function limpiar_bloqueo_similar_to() {
    delete_transient(SIMILAR_TO_PROCESS_LOCK);
    //guardarLog("Bloqueo de proceso similar_to limpiado manualmente.");
}
// Puedes asignar esta función a una acción específica si lo deseas, por ejemplo:
add_action('admin_post_limpia_bloqueo_similar_to', 'limpiar_bloqueo_similar_to');