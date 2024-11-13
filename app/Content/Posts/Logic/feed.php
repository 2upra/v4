<?

function obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts_per_page) {
    try {
        if (!$current_user_id) {
            error_log("[obtenerFeedPersonalizado] Error: ID de usuario no válido");
            return ['post_ids' => [], 'post_not_in' => []];
        }

        $post_not_in = [];

        // Manejo de posts similares
        if ($similar_to) {
            $post_not_in[] = $similar_to;
            $cache_suffix = "_similar_" . $similar_to;
            $similar_to_cache_key = "similar_to_{$similar_to}";
            
            // Verificar caché global
            $cached_data = get_transient($similar_to_cache_key);

            if ($cached_data) {
                error_log("[obtenerFeedPersonalizado] Aviso: Usando caché global para similar_to_{$similar_to}");
                $posts_personalizados = $cached_data;
            } else {
                error_log("[obtenerFeedPersonalizado] Aviso: Calculando nuevo feed similar para post ID: {$similar_to}");
                $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
                
                if (!$posts_personalizados) {
                    error_log("[obtenerFeedPersonalizado] Error: Fallo al calcular feed similar");
                    return ['post_ids' => [], 'post_not_in' => []];
                }
                
                set_transient($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);
            }
        } else {
            $cache_suffix = "";
        }

        // Generar clave de caché
        $transient_key = $current_user_id == 44
            ? "feed_personalizado_anonymous_{$identifier}{$cache_suffix}"
            : "feed_personalizado_user_{$current_user_id}_{$identifier}{$cache_suffix}";

        $cache_time = $is_admin ? 7200 : 43200;
        $cached_data = get_transient($transient_key);

        if ($cached_data) {
            error_log("[obtenerFeedPersonalizado] Aviso: Usando caché para usuario ID: {$current_user_id}");
            $posts_personalizados = $cached_data['posts'];
        } else {
            if ($paged === 1) {
                error_log("[obtenerFeedPersonalizado] Aviso: Calculando nuevo feed para primera página");
                $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
            } else {
                error_log("[obtenerFeedPersonalizado] Aviso: Intentando recuperar backup para página {$paged}");
                $posts_personalizados = get_option($transient_key . '_backup', []);
                
                if (empty($posts_personalizados)) {
                    error_log("[obtenerFeedPersonalizado] Aviso: Backup no encontrado, calculando nuevo feed");
                    $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
                }

                if (!$posts_personalizados) {
                    error_log("[obtenerFeedPersonalizado] Error: Fallo al calcular feed personalizado");
                    return ['post_ids' => [], 'post_not_in' => []];
                }

                $cache_data = ['posts' => $posts_personalizados, 'timestamp' => time()];
                set_transient($transient_key, $cache_data, $cache_time);
                update_option($transient_key . '_backup', $posts_personalizados);
            }
        }

        $post_ids = array_keys($posts_personalizados);

        if (count($post_ids) > 2500) {
            error_log("[obtenerFeedPersonalizado] Aviso: Limitando resultados a 2500 posts");
            $post_ids = array_slice($post_ids, 0, 2500);
        }

        if ($similar_to) {
            $post_ids = array_filter($post_ids, function ($post_id) use ($similar_to) {
                return $post_id != $similar_to;
            });
            
            if (empty($post_ids)) {
                error_log("[obtenerFeedPersonalizado] Advertencia: No se encontraron posts similares para ID: {$similar_to}");
            }
        }

        return [
            'post_ids' => $post_ids,
            'post_not_in' => $post_not_in,
        ];

    } catch (Exception $e) {
        error_log("[obtenerFeedPersonalizado] Error crítico: " . $e->getMessage());
        return ['post_ids' => [], 'post_not_in' => []];
    }
}

function reiniciarFeed($current_user_id)
{
    global $wpdb;

    // Obtener todos los transients relacionados con el usuario actual
    $transient_pattern = '_transient_feed_personalizado_user_' . $current_user_id . '_%';
    $backup_pattern = '_transient_feed_personalizado_user_' . $current_user_id . '_backup%';

    // Consulta para obtener transients que coincidan con el patrón
    $query = $wpdb->get_col($wpdb->prepare(
        "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
        $transient_pattern,
        $backup_pattern
    ));

    // Borrar transients y sus backups
    foreach ($query as $option_name) {
        // Eliminar el transient
        $transient_name = str_replace('_transient_', '', $option_name); // Eliminar el prefijo '_transient_'
        delete_transient($transient_name);

        // Eliminar el backup relacionado en las opciones
        delete_option($option_name . '_backup');
    }

    // Eliminar la caché específica del usuario
    wp_cache_delete('feed_datos_' . $current_user_id);

    return count($query); // Retorna cuántos transients se eliminaron
}
