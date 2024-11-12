<?php

define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_feed_progress');  // Opción para guardar progreso
define('SIMILAR_TO_PROCESS_LOCK', 'similar_to_process_lock');  // Bloqueo de proceso
define('SIMILAR_TO_MAX_LOCK_TIME', 60);  // 5 minutos de bloqueo máximo

/*
ESTO NO FUNCIONA BIEN SE QUEDA EN BUCLE
2024-11-12 05:09:38 - Último post procesado ID: 240218
2024-11-12 05:09:38 - Siguiente post ID: 240221
2024-11-12 05:09:38 - Post ID: 240221 ya tiene caché, avanzando al siguiente post
2024-11-12 05:10:02 - Último post procesado ID: 240218
2024-11-12 05:10:02 - Siguiente post ID: 240221
2024-11-12 05:10:02 - Post ID: 240221 ya tiene caché, avanzando al siguiente post
2024-11-12 05:10:04 - Último post procesado ID: 240218
2024-11-12 05:10:04 - Siguiente post ID: 240221
2024-11-12 05:10:04 - Post ID: 240221 ya tiene caché, avanzando al siguiente post
*/

function recalcularSimilarToFeed() {
    // Verificar si un proceso ya está en ejecución basado en el tiempo de bloqueo
    $lock_time = get_transient(SIMILAR_TO_PROCESS_LOCK);
    if ($lock_time && (time() - $lock_time < SIMILAR_TO_MAX_LOCK_TIME)) {
        guardarLog("Proceso ya en ejecución, saltando esta iteración");
        return;
    } elseif ($lock_time) {
        // Si el bloqueo ha excedido el tiempo máximo permitido, limpiar el bloqueo
        guardarLog("El bloqueo ha estado activo demasiado tiempo, limpiando el bloqueo");
        delete_transient(SIMILAR_TO_PROCESS_LOCK);
    }

    // Establecer bloqueo con la marca de tiempo actual para evitar ejecuciones simultáneas
    set_transient(SIMILAR_TO_PROCESS_LOCK, time(), SIMILAR_TO_MAX_LOCK_TIME);

    try {
        // Obtener el ID del último post procesado, por defecto 0 si no se ha procesado ninguno
        $last_processed_post_id = get_option(SIMILAR_TO_PROGRESS_OPTION, 0);  
        guardarLog("Último post procesado ID: $last_processed_post_id");

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
                guardarLog("No se encontraron más posts, reiniciando progreso");
                update_option(SIMILAR_TO_PROGRESS_OPTION, 0);
                break;
            }

            guardarLog("Siguiente post ID: $post_id");

            // Generar la clave de caché para el post actual
            $similar_to_cache_key = "similar_to_{$post_id}";
            
            if (get_transient($similar_to_cache_key)) {
                // Si ya tiene caché, actualizar el progreso y continuar al siguiente post
                guardarLog("Post ID: $post_id ya tiene caché, avanzando al siguiente post");
                update_option(SIMILAR_TO_PROGRESS_OPTION, $post_id);
                guardarLog("Progreso actualizado a post ID: $post_id");
                $last_processed_post_id = $post_id;
                // Continuar el bucle para verificar el siguiente post
            } else {
                // Si no tiene caché, procesarlo
                guardarLog("Procesando post ID: $post_id");
                
                $posts_personalizados = calcularFeedPersonalizado(44, '', $post_id);

                if ($posts_personalizados) {
                    // Guardar el resultado en caché por 15 días
                    set_transient($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);
                    guardarLog("Feed calculado y guardado en caché para post ID: $post_id");
                } else {
                    guardarLog("Error al calcular feed para post ID: $post_id");
                }

                // Actualizar el progreso con el último post procesado
                update_option(SIMILAR_TO_PROGRESS_OPTION, $post_id);
                guardarLog("Proceso completado para post ID: $post_id");
                
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
    $schedules['every_30_seconds'] = [
        'interval' => 15,
        'display'  => 'Cada 30 segundos',
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