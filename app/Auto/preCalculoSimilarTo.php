<?php

// Definir el nombre de la opción para hacer seguimiento del progreso
define('SIMILAR_TO_PROGRESS_OPTION', 'similar_to_feed_progress');

// Función que se ejecutará en cada cron
function recalcularSimilarToFeed() {
    error_log("Cron 'recalcular_similar_to_feed_cron' se está ejecutando.", 0); // Log cuando el cron se ejecuta
    
    // Recuperar el progreso de la última ejecución (post ID)
    $last_processed_post_id = get_option(SIMILAR_TO_PROGRESS_OPTION, 0);
    
    // Buscar el siguiente post a procesar
    $args = [
        'numberposts' => 1,
        'orderby' => 'date',
        'order' => 'ASC', // Desde el más antiguo
        'post__gt' => $last_processed_post_id, // Solo obtener los que no han sido procesados
    ];
    
    $posts_to_process = get_posts($args);
    
    if ($posts_to_process) {
        $post = $posts_to_process[0]; // Tomamos el primer post

        if ($post->ID) {
            $similar_to = $post->ID;
            $similar_to_cache_key = "similar_to_{$similar_to}";
            $cached_data = get_transient($similar_to_cache_key);

            // Verificar si la caché está disponible
            if ($cached_data) {
                error_log("Cache encontrada para 'similar_to_{$similar_to}'", 0); // Log si la caché es encontrada
            } else {
                error_log("No hay cache para 'similar_to_{$similar_to}', calculando feed...", 0); // Log si no se encuentra caché

                // Realizar el cálculo y guardar en caché
                $posts_personalizados = calcularFeedPersonalizado(0, '', $similar_to);
                
                // Verificar si los posts fueron correctamente calculados
                if ($posts_personalizados) {
                    set_transient($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);
                    error_log("Cache guardada para 'similar_to_{$similar_to}'", 0); // Log cuando la caché es guardada
                } else {
                    error_log("Error al calcular el feed para 'similar_to_{$similar_to}'", 0); // Log si hay un error en el cálculo
                }
            }

            // Actualizar el progreso: guardar el último post procesado
            update_option(SIMILAR_TO_PROGRESS_OPTION, $post->ID);
        }
    } else {
        // Si no hay más posts para procesar, reiniciar el progreso
        delete_option(SIMILAR_TO_PROGRESS_OPTION);
    }
}

// Agregar a cron para ejecutar cada 30 segundos
add_action('init', 'agregarCron30Segundos');
function agregarCron30Segundos() {
    if (!wp_next_scheduled('recalcular_similar_to_feed_cron_30sec')) {
        $scheduled = wp_schedule_event(time(), 'every_30_seconds', 'recalcular_similar_to_feed_cron_30sec');
        if ($scheduled) {
            error_log("Evento cron programado correctamente.", 0); // Log cuando el cron se programa
        } else {
            error_log("Error al programar el evento cron.", 0); // Log si hay un error al programar el cron
        }
    }
}

// Función para registrar el cron con intervalo de 30 segundos
add_filter('cron_schedules', 'agregar_cron_30_segundos');
function agregar_cron_30_segundos($schedules) {
    if (!isset($schedules['every_30_seconds'])) {
        $schedules['every_30_seconds'] = [
            'interval' => 30, // cada 30 segundos
            'display' => 'Cada 30 segundos',
        ];
    }
    return $schedules;
}

// Registrar el hook del cron
add_action('recalcular_similar_to_feed_cron_30sec', 'recalcularSimilarToFeed');
