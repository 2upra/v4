<?

function obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts_per_page, $tipoUsuario = null)
{
    try {
        if (!$current_user_id) {
            guardarLog("Error: ID de usuario no válido al obtener feed personalizado");
            return ['post_ids' => [], 'post_not_in' => []];
        }

        error_log("Identifier recibido obtenerFeedPersonalizado: " . $identifier);

        if ($similar_to) {
            $resultado_similares = obtenerPostsSimilares($current_user_id, $similar_to);
            $posts_personalizados = $resultado_similares['posts_personalizados'];
            $post_not_in = $resultado_similares['post_not_in'];
        } else {
            $cache_key = ($current_user_id == 44)
                ? "feed_personalizado_user_44_{$identifier}"
                : "feed_personalizado_user_{$current_user_id}_{$identifier}";

            $cache_time = $is_admin ? 7200 : 43200; // 2 horas para admin, 12 horas para usuarios

            $cache_data = obtenerCache($cache_key);

            if ($cache_data) {
            error_log("Cache utilizada: " . $cache_key);

                guardarLog("Usuario ID: $current_user_id usando caché para feed personalizado");
                $posts_personalizados = $cache_data['posts'];
            } else {
                if ($paged === 1) {
                    guardarLog("Usuario ID: $current_user_id calculando nuevo feed para primera página (sin caché)");
                    $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, '', $tipoUsuario);

                    if (!$posts_personalizados) {
                        guardarLog("Error: Fallo al calcular feed personalizado para usuario ID: $current_user_id");
                        return ['post_ids' => [], 'post_not_in' => []];
                    }

                    $cache_content = ['posts' => $posts_personalizados, 'timestamp' => time()];
                    guardarCache($cache_key, $cache_content, $cache_time);
                } else {
                    if (empty($posts_personalizados)) {
                        guardarLog("Usuario ID: $current_user_id backup no encontrado, calculando nuevo feed (sin caché)");
                        $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, '', $tipoUsuario);
                    }

                    if (!$posts_personalizados) {
                        guardarLog("Error: Fallo al calcular feed personalizado para usuario ID: $current_user_id");
                        return ['post_ids' => [], 'post_not_in' => []];
                    }

                    $cache_content = ['posts' => $posts_personalizados, 'timestamp' => time()];
                    guardarCache($cache_key, $cache_content, $cache_time);
                }
            }

            $post_not_in = [];
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


function obtenerPostsSimilares($current_user_id, $similar_to)
{
        $post_not_in = [];
    if ($similar_to) {
        $post_not_in[] = $similar_to;
        $similar_to_cache_key = "similar_to_{$similar_to}";

        // Verificar caché global en archivos
        $cached_data = obtenerCache($similar_to_cache_key);

        if ($cached_data) {
            guardarLog("Usuario ID: $current_user_id usando caché global para posts similares a $similar_to");
            return [
                'posts_personalizados' => $cached_data,
                'post_not_in' => $post_not_in,
            ];
        } else {
            guardarLog("Usuario ID: $current_user_id calculando nuevo feed similar para post ID: $similar_to");
            $posts_personalizados = calcularFeedPersonalizado(44, '', $similar_to);

            if (!$posts_personalizados) {
                guardarLog("Error: Fallo al calcular posts similares para post ID: $similar_to");
                return ['posts_personalizados' => [], 'post_not_in' => $post_not_in];
            }

            guardarCache($similar_to_cache_key, $posts_personalizados, 15 * DAY_IN_SECONDS);

            return [
                'posts_personalizados' => $posts_personalizados,
                'post_not_in' => $post_not_in,
            ];
        }
    }

    return ['posts_personalizados' => [], 'post_not_in' => $post_not_in];
}



/*
no entiendo porque tipoUsuari no llega  calcularFeedPersonalizado
[04-Dec-2024 13:17:29 UTC] TipoUsuario inicial=Fan reiniciarFeed
[04-Dec-2024 13:17:29 UTC] TipoUsuario inicial=Fan enviado a calcularFeedPersonalizado
[04-Dec-2024 13:17:29 UTC] TipoUsuario inicial= calcularFeedPersonalizado

*/

