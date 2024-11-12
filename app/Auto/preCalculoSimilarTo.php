<?
/*
// Definiciones de constantes
define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_feed_progress');    // Opción para guardar progreso
define('SIMILAR_TO_PROCESS_LOCK', 'similar_to_process_lock');        // Bloqueo de proceso
define('SIMILAR_TO_MAX_LOCK_TIME', 60);                             // 1 minuto de bloqueo máximo (ajustado a 60 segundos)

// Nuevas constantes para manejo de detención prolongada
define('SIMILAR_TO_CACHED_COUNT_OPTION', 'similar_to_cached_count'); // Contador de posts consecutivos con caché
define('SIMILAR_TO_STOP_UNTIL_OPTION', 'similar_to_stop_until');     // Timestamp hasta el cual se detiene la ejecución
define('SIMILAR_TO_CONSECUTIVE_LIMIT', 100);                        // Límite de posts consecutivos con caché
define('SIMILAR_TO_STOP_DURATION', 6 * HOUR_IN_SECONDS);            // Duración de la detención (6 horas)



/**
 * Función principal para recalcular el feed similar.
 */
function recalcularSimilarToFeed() {
    // Verificar si hay una detención prolongada activa
    $stop_until = get_option(SIMILAR_TO_STOP_UNTIL_OPTION, 0);
    if ($stop_until && time() < $stop_until) {
        $remaining = human_time_diff(time(), $stop_until);
        guardarLog("Detención activa. Próxima ejecución en: $remaining.");
        return;
    } elseif ($stop_until && time() >= $stop_until) {
        // Si la detención ha terminado, limpiar la opción
        guardarLog("La detención de 6 horas ha finalizado, reanudando el procesamiento.");
        delete_option(SIMILAR_TO_STOP_UNTIL_OPTION);
        update_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);
    }

    // Verificar si un proceso ya está en ejecución basado en el tiempo de bloqueo
    $lock_time = get_transient(SIMILAR_TO_PROCESS_LOCK);
    if ($lock_time && (time() - $lock_time < SIMILAR_TO_MAX_LOCK_TIME)) {
        guardarLog("Proceso ya en ejecución, saltando esta iteración.");
        return;
    } elseif ($lock_time) {
        // Si el bloqueo ha excedido el tiempo máximo permitido, limpiar el bloqueo
        guardarLog("El bloqueo ha estado activo demasiado tiempo, limpiando el bloqueo.");
        delete_transient(SIMILAR_TO_PROCESS_LOCK);
    }

    // Establecer bloqueo con la marca de tiempo actual para evitar ejecuciones simultáneas
    set_transient(SIMILAR_TO_PROCESS_LOCK, time(), SIMILAR_TO_MAX_LOCK_TIME);

    try {
        // Obtener el ID del último post procesado, por defecto 0 si no se ha procesado ninguno
        $last_processed_post_id = get_option(SIMILAR_TO_PROGRESS_OPTION, 0);
        guardarLog("Último post procesado ID: $last_processed_post_id");

        // Obtener el contador de posts consecutivos con caché
        $cached_count = get_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);

        global $wpdb;
        
        while (true) {
            // Preparar y ejecutar la consulta para obtener el siguiente post a procesar
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
            
            $post_id = $wpdb->get_var($query);  // Obtener el siguiente post ID

            if (!$post_id) {
                guardarLog("No se encontraron más posts, reiniciando progreso.");
                update_option(SIMILAR_TO_PROGRESS_OPTION, 0);
                // También, puedes resetear el contador de caché aquí si lo consideras necesario
                update_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);
                break;
            }

            guardarLog("Siguiente post ID: $post_id");

            // Generar la clave de caché para el post actual
            $similar_to_cache_key = "similar_to_$post_id";
            
            if (get_transient($similar_to_cache_key)) {
                // Si ya tiene caché, actualizar el progreso y continuar al siguiente post
                guardarLog("Post ID: $post_id ya tiene caché, avanzando al siguiente post.");
                update_option(SIMILAR_TO_PROGRESS_OPTION, $post_id);
                guardarLog("Progreso actualizado a post ID: $post_id.");
                
                // Incrementar el contador de posts con caché
                $cached_count++;
                update_option(SIMILAR_TO_CACHED_COUNT_OPTION, $cached_count);
                guardarLog("Contador de posts con caché consecutivos: $cached_count.");

                // Verificar si se ha alcanzado el límite de posts con caché
                if ($cached_count >= SIMILAR_TO_CONSECUTIVE_LIMIT) {
                    // Establecer una detención de 6 horas
                    $new_stop_until = time() + SIMILAR_TO_STOP_DURATION;
                    update_option(SIMILAR_TO_STOP_UNTIL_OPTION, $new_stop_until);
                    guardarLog("Se han encontrado $cached_count posts con caché consecutivamente. Deteniendo el procesamiento durante 6 horas.");
                    // Resetear el contador
                    update_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);
                    break;
                }

                // Actualizar el último post procesado
                $last_processed_post_id = $post_id;
                // Continuar el bucle para verificar el siguiente post
            } else {
                // Si no tiene caché, procesarlo
                guardarLog("Procesando post ID: $post_id.");
                
                // Asegúrate de que la función `calcularFeedPersonalizado` esté definida y funcione correctamente
                $posts_personalizados = calcularFeedPersonalizado(44, '', $post_id);

                if ($posts_personalizados) {
                    // Guardar el resultado en caché por 15 días
                    set_transient($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);
                    guardarLog("Feed calculado y guardado en caché para post ID: $post_id.");
                } else {
                    guardarLog("Error al calcular feed para post ID: $post_id.");
                }

                // Actualizar el progreso con el último post procesado
                update_option(SIMILAR_TO_PROGRESS_OPTION, $post_id);
                guardarLog("Proceso completado para post ID: $post_id.");

                // Resetear el contador de posts con caché ya que se ha procesado un post nuevo
                update_option(SIMILAR_TO_CACHED_COUNT_OPTION, 0);

                // Romper el ciclo después de procesar un post para limitar la carga por ejecución
                break;
            }
        }

    } catch (Exception $e) {
        guardarLog("Error en el proceso: " . $e->getMessage());
    } finally {
        // Siempre eliminar el bloqueo al finalizar
        delete_transient(SIMILAR_TO_PROCESS_LOCK);
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
        guardarLog("Evento cron 'recalcular_similar_to_feed_cron' programado para cada 30 segundos.");
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
    guardarLog("Bloqueo de proceso similar_to limpiado manualmente.");
}
// Puedes asignar esta función a una acción específica si lo deseas, por ejemplo:
add_action('admin_post_limpia_bloqueo_similar_to', 'limpiar_bloqueo_similar_to');