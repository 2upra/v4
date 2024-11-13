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
            $similar_to_cache_key = "similar_to_{$similar_to}";

            // Verificar caché global en archivos
            $cached_data = obtenerCache($similar_to_cache_key);

            if ($cached_data) {
                guardarLog("Usuario ID: $current_user_id usando caché global para posts similares a $similar_to");
                $posts_personalizados = $cached_data;
            } else {
                guardarLog("Usuario ID: $current_user_id calculando nuevo feed similar para post ID: $similar_to");
                $posts_personalizados = calcularFeedPersonalizado(44, '', $similar_to);

                if (!$posts_personalizados) {
                    guardarLog("Error: Fallo al calcular posts similares para post ID: $similar_to");
                    return ['post_ids' => [], 'post_not_in' => []];
                }

                guardarCache($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);
            }

            // Para usuarios anónimos, no se genera caché adicional
            // Para usuarios logueados, podríamos personalizar aún más si es necesario
        } else {
            // Generar clave de caché para el feed personalizado general
            $cache_key = ($current_user_id == 44)
                ? "feed_personalizado_user_44_{$identifier}"
                : "feed_personalizado_user_{$current_user_id}_{$identifier}";

            $cache_time = $is_admin ? 7200 : 43200; // 2 horas para admin, 12 horas para usuarios

            // Verificar caché en archivos
            $cache_data = obtenerCache($cache_key);

            if ($cache_data) {
                guardarLog("Usuario ID: $current_user_id usando caché para feed personalizado");
                $posts_personalizados = $cache_data['posts'];
            } else {
                if ($paged === 1) {
                    guardarLog("Usuario ID: $current_user_id calculando nuevo feed para primera página (sin caché)");
                    $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier);

                    if (!$posts_personalizados) {
                        guardarLog("Error: Fallo al calcular feed personalizado para usuario ID: $current_user_id");
                        return ['post_ids' => [], 'post_not_in' => []];
                    }

                    // Guardar en caché y respaldo
                    $cache_content = ['posts' => $posts_personalizados, 'timestamp' => time()];
                    guardarCache($cache_key, $cache_content, $cache_time);

                    // Guardar un respaldo en las opciones
                    update_option($cache_key . '_backup', $posts_personalizados);

                } else {
                    guardarLog("Usuario ID: $current_user_id intentando recuperar backup para página $paged (sin caché)");
                    $posts_personalizados = get_option($cache_key . '_backup', []);

                    if (empty($posts_personalizados)) {
                        guardarLog("Usuario ID: $current_user_id backup no encontrado, calculando nuevo feed (sin caché)");
                        $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier);
                    }

                    if (!$posts_personalizados) {
                        guardarLog("Error: Fallo al calcular feed personalizado para usuario ID: $current_user_id");
                        return ['post_ids' => [], 'post_not_in' => []];
                    }

                    // Guardar en caché y respaldo
                    $cache_content = ['posts' => $posts_personalizados, 'timestamp' => time()];
                    guardarCache($cache_key, $cache_content, $cache_time);
                    update_option($cache_key . '_backup', $posts_personalizados);
                }
            }
        }

        if (isset($posts_personalizados)) {
            $post_ids = array_keys($posts_personalizados);
        } else {
            $post_ids = [];
        }

        if (count($post_ids) > POSTINLIMIT) {
            guardarLog("Usuario ID: $current_user_id - Limitando resultados a " . POSTINLIMIT . " posts");
            $post_ids = array_slice($post_ids, 0, POSTINLIMIT);
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



function reiniciarFeed($current_user_id) {
    global $wpdb;

    guardarLog("Iniciando reinicio de feed para usuario ID: $current_user_id");

    // Obtener todos los archivos de caché relacionados con el usuario actual
    $cache_dir = WP_CONTENT_DIR . '/cache/feed/';
    $cache_pattern = ($current_user_id == 44)
        ? "feed_personalizado_anonymous_*"
        : "feed_personalizado_user_{$current_user_id}_*";
    $transients_eliminados = 0;

    if (file_exists($cache_dir)) {
        $files = glob($cache_dir . $cache_pattern . '.cache');

        if (empty($files)) {
            guardarLog("No se encontraron cachés para reiniciar del usuario ID: $current_user_id");
        } else {
            foreach ($files as $file) {
                if (unlink($file)) {
                    $transients_eliminados++;
                    guardarLog("Caché eliminada: {$file} para usuario ID: $current_user_id");
                }
            }
        }
    }

    // Eliminar los respaldos de opciones relacionados
    $option_pattern = ($current_user_id == 44)
        ? 'feed_personalizado_anonymous_%'
        : 'feed_personalizado_user_' . $current_user_id . '_%';

    $query = $wpdb->get_col($wpdb->prepare(
        "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
        $option_pattern . '_backup'
    ));

    foreach ($query as $option_name) {
        if (delete_option($option_name)) {
            guardarLog("Backup eliminado: {$option_name} para usuario ID: $current_user_id");
        }
    }

    borrarCache('feed_datos_' . $current_user_id);
    guardarLog("Caché específica eliminada: feed_datos_$current_user_id");

    guardarLog("Reinicio de feed completado para usuario ID: $current_user_id - Total de cachés eliminadas: $transients_eliminados");

    return $transients_eliminados;
}