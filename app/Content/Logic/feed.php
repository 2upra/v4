<?


function obtenerFeedPersonalizado($idUsuario, $identificador, $similar, $pagina, $esAdmin, $postsPagina, $tipoUsuario = null, $filtrosUsuario = null)
{
    try {
        if (!$idUsuario) {
            return ['post_ids' => [], 'post_not_in' => []];
        }

        $filtrosUsuario = get_user_meta($idUsuario, 'filtroPost', true);

        if ($similar) {
            $resultado = obtenerPostsSimilares($idUsuario, $similar);
            $posts = $resultado['posts_personalizados'];
            $postNotIn = $resultado['post_not_in'];
        } else {
            $filtrosHash = $filtrosUsuario ? md5(serialize($filtrosUsuario)) : 'sin_filtros';
            $tipoUsuarioCache = $tipoUsuario ? md5($tipoUsuario) : 'sin_tipo';
            $cacheKey = ($idUsuario == 44)
                ? "feed_personalizado_user_44_{$identificador}_{$filtrosHash}_{$tipoUsuarioCache}"
                : "feed_personalizado_user_{$idUsuario}_{$identificador}_{$filtrosHash}_{$tipoUsuarioCache}";

            $cacheTiempo = $esAdmin ? 7200 : 43200;
            $cacheData = obtenerCache($cacheKey);

            if ($cacheData) {
                //guardarLog("obtenerFeedPersonalizado - Usuario ID: $idUsuario usando caché para feed personalizado");
                $posts = $cacheData['posts'];
            } else {
                if ($pagina === 1) {
                    $posts = calcularFeedPersonalizado($idUsuario, $identificador, '', $tipoUsuario, $filtrosUsuario);

                    if (!$posts) {
                        return ['post_ids' => [], 'post_not_in' => []];
                    }

                    $cacheContenido = ['posts' => $posts, 'timestamp' => time()];
                    guardarCache($cacheKey, $cacheContenido, $cacheTiempo);
                } else {
                    if (empty($posts)) {
                        $posts = calcularFeedPersonalizado($idUsuario, $identificador, '', $tipoUsuario, $filtrosUsuario);
                    }

                    if (!$posts) {
                        return ['post_ids' => [], 'post_not_in' => []];
                    }

                    $cacheContenido = ['posts' => $posts, 'timestamp' => time()];
                    guardarCache($cacheKey, $cacheContenido, $cacheTiempo);
                }
            }

            $postNotIn = [];
        }

        $postIds = isset($posts) ? array_keys($posts) : [];

        if (count($postIds) > POSTINLIMIT) {
            $postIds = array_slice($postIds, 0, POSTINLIMIT);
        }

        if ($similar) {
            $postIds = array_filter($postIds, function ($postId) use ($similar) {
                return $postId != $similar;
            });
        }

        return [
            'post_ids' => $postIds,
            'post_not_in' => $postNotIn,
        ];
    } catch (Exception $e) {
        //guardarLog("obtenerFeedPersonalizado - Error crítico para usuario ID: $idUsuario - " . $e->getMessage());
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
            ////guardarLog("Usuario ID: $current_user_id usando caché global para posts similares a $similar_to");
            return [
                'posts_personalizados' => $cached_data,
                'post_not_in' => $post_not_in,
            ];
        } else {
            ////guardarLog("Usuario ID: $current_user_id calculando nuevo feed similar para post ID: $similar_to");
            $posts_personalizados = calcularFeedPersonalizado(44, '', $similar_to);

            if (!$posts_personalizados) {
                ////guardarLog("Error: Fallo al calcular posts similares para post ID: $similar_to");
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
