<?

define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_feed_progress');
define('SIMILAR_TO_PROCESS_LOCK', 'similar_to_process_lock');


function recalcularSimilarToFeed() {
    // Verificar si hay un proceso en ejecución
    if (get_transient(SIMILAR_TO_PROCESS_LOCK)) {
        guardarLog("Proceso ya en ejecución, saltando esta iteración");
        return;
    }

    // Establecer bloqueo
    set_transient(SIMILAR_TO_PROCESS_LOCK, true, 30);

    try {
        $last_processed_post_id = get_option(SIMILAR_TO_PROGRESS_OPTION, 0);
        
        global $wpdb;
        
        // Buscar el siguiente post que necesite caché
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
            guardarLog("No se encontraron más posts, reiniciando progreso");
            update_option(SIMILAR_TO_PROGRESS_OPTION, 0);
            return;
        }

        // Procesar el post independientemente de si tiene caché o no
        guardarLog("Procesando post ID: $post_id");
        $posts_personalizados = calcularFeedPersonalizado(44, '', $post_id);
        
        if ($posts_personalizados) {
            set_transient("similar_to_{$post_id}", $posts_personalizados, 15 * DAY_IN_SECONDS);
            guardarLog("Feed calculado y guardado en caché para post ID: $post_id");
        } else {
            guardarLog("Error al calcular feed para post ID: $post_id");
        }

        // Actualizar el último ID procesado
        update_option(SIMILAR_TO_PROGRESS_OPTION, $post_id);
        guardarLog("Proceso completado para post ID: $post_id");

    } catch (Exception $e) {
        guardarLog("Error en el proceso: " . $e->getMessage());
    } finally {
        delete_transient(SIMILAR_TO_PROCESS_LOCK);
    }
}

function agregar_cron_30_segundos($schedules) {
    $schedules['every_30_seconds'] = [
        'interval' => 30,
        'display' => 'Cada 30 segundos',
    ];
    return $schedules;
}

function inicializar_cron() {
    if (!wp_next_scheduled('recalcular_similar_to_feed_cron')) {
        wp_schedule_event(time(), 'every_30_seconds', 'recalcular_similar_to_feed_cron');
    }
}

// Registrar las acciones
add_filter('cron_schedules', 'agregar_cron_30_segundos');
add_action('init', 'inicializar_cron');
add_action('recalcular_similar_to_feed_cron', 'recalcularSimilarToFeed');

// Función para limpiar el bloqueo manualmente
function limpiar_bloqueo_similar_to() {
    delete_transient(SIMILAR_TO_PROCESS_LOCK);
    guardarLog("Bloqueo de proceso similar_to limpiado manualmente");
}
 