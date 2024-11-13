<?

// Asegúrate de definir estas constantes en tu código o en tu archivo de configuración.
define('SIMILAR_TO_PROCESS_LOCK', 'similar_to_process_lock');
define('SIMILAR_TO_MAX_LOCK_TIME', 300); // 5 minutos, ajusta según tus necesidades
define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_progress');
define('SIMILAR_TO_CACHED_COUNT_OPTION', 'similar_to_cached_count');
define('SIMILAR_TO_STOP_UNTIL_OPTION', 'similar_to_stop_until');
define('SIMILAR_TO_CONSECUTIVE_LIMIT', 100); // Límite de posts consecutivos con caché, ajusta según necesites
define('SIMILAR_TO_STOP_DURATION', 6 * HOUR_IN_SECONDS); // Detención de 6 horas




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