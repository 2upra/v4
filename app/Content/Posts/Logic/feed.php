<?

function obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts_per_page) {
    try {
        if (!$current_user_id) {
            guardarLog("Error: ID de usuario no válido al obtener feed personalizado");
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
                guardarLog("Usuario ID: $current_user_id usando caché global para posts similares a $similar_to");
                $posts_personalizados = $cached_data;
            } else {
                guardarLog("Usuario ID: $current_user_id calculando nuevo feed similar para post ID: $similar_to");
                $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
                
                if (!$posts_personalizados) {
                    guardarLog("Error: Fallo al calcular feed similar para usuario ID: $current_user_id");
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
            guardarLog("Usuario ID: $current_user_id usando caché para feed personalizado");
            $posts_personalizados = $cached_data['posts'];
        } else {
            if ($paged === 1) {
                guardarLog("Usuario ID: $current_user_id calculando nuevo feed para primera página (sin caché)");
                $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
            } else {
                guardarLog("Usuario ID: $current_user_id intentando recuperar backup para página $paged (sin caché)");
                $posts_personalizados = get_option($transient_key . '_backup', []);
                
                if (empty($posts_personalizados)) {
                    guardarLog("Usuario ID: $current_user_id backup no encontrado, calculando nuevo feed (sin caché)");
                    $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
                }

                if (!$posts_personalizados) {
                    guardarLog("Error: Fallo al calcular feed personalizado para usuario ID: $current_user_id");
                    return ['post_ids' => [], 'post_not_in' => []];
                }

                $cache_data = ['posts' => $posts_personalizados, 'timestamp' => time()];
                set_transient($transient_key, $cache_data, $cache_time);
                update_option($transient_key . '_backup', $posts_personalizados);
            }
        }

        $post_ids = array_keys($posts_personalizados);

        if (count($post_ids) > 2500) {
            guardarLog("Usuario ID: $current_user_id - Limitando resultados a 2500 posts");
            $post_ids = array_slice($post_ids, 0, 2500);
        }

        if ($similar_to) {
            $post_ids = array_filter($post_ids, function ($post_id) use ($similar_to) {
                return $post_id != $similar_to;
            });
            
            if (empty($post_ids)) {
                guardarLog("Usuario ID: $current_user_id - No se encontraron posts similares para ID: $similar_to");
            }
        }

        return [
            'post_ids' => $post_ids,
            'post_not_in' => $post_not_in,
        ];

    } catch (Exception $e) {
        guardarLog("Error crítico para usuario ID: $current_user_id - " . $e->getMessage());
        return ['post_ids' => [], 'post_not_in' => []];
    }
}

function reiniciarFeed($current_user_id)
{
    global $wpdb;

    guardarLog("Iniciando reinicio de feed para usuario ID: $current_user_id");

    // Obtener todos los transients relacionados con el usuario actual
    $transient_pattern = '_transient_feed_personalizado_user_' . $current_user_id . '_%';
    $backup_pattern = '_transient_feed_personalizado_user_' . $current_user_id . '_backup%';

    // Consulta para obtener transients que coincidan con el patrón
    $query = $wpdb->get_col($wpdb->prepare(
        "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
        $transient_pattern,
        $backup_pattern
    ));

    if (empty($query)) {
        guardarLog("No se encontraron cachés para reiniciar del usuario ID: $current_user_id");
        return 0;
    }

    // Borrar transients y sus backups
    $transients_eliminados = 0;
    foreach ($query as $option_name) {
        // Eliminar el transient
        $transient_name = str_replace('_transient_', '', $option_name);
        if (delete_transient($transient_name)) {
            $transients_eliminados++;
            guardarLog("Caché eliminada: $transient_name para usuario ID: $current_user_id");
        }

        // Eliminar el backup relacionado en las opciones
        if (delete_option($option_name . '_backup')) {
            guardarLog("Backup eliminado: {$option_name}_backup para usuario ID: $current_user_id");
        }
    }

    // Eliminar la caché específica del usuario
    wp_cache_delete('feed_datos_' . $current_user_id);
    guardarLog("Caché específica eliminada: feed_datos_$current_user_id");

    guardarLog("Reinicio de feed completado para usuario ID: $current_user_id - Total de cachés eliminadas: $transients_eliminados");

    return $transients_eliminados;
}