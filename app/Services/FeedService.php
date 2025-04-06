<?php
// Refactor(Org): Funcion reiniciarFeed() movida desde app/Content/Logic/reiniciarFeed.php

/*
[12-Dec-2024 08:25:19 UTC] Caché guardada exitosamente. Nombre de la caché: feed_personalizado_user_1_.cache
[12-Dec-2024 08:25:19 UTC] [borrarCache] Archivo de caché no encontrado: /var/www/wordpress/wp-content/cache/feed/feed_datos_1.cache
*/

function reiniciarFeed($current_user_id)
{
    $tipoUsuario = get_user_meta($current_user_id, 'tipoUsuario', true);
    //error_log("TipoUsuario inicial={$tipoUsuario} reiniciarFeed");
    global $wpdb;
    $is_admin = current_user_can('administrator');
    guardarLog("Iniciando reinicio de feed para usuario ID: $current_user_id");
    $cache_key = ($current_user_id == 44)
        ? "feed_personalizado_user_44_"
        : "feed_personalizado_user_{$current_user_id}_";

    $cache_time = $is_admin ? 7200 : 43200; // 2 horas para admin, 12 horas para usuarios

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

                    guardarLog("Usuario ID: $current_user_id REcalculando nuevo feed para primera página (sin caché)");
                    //error_log("TipoUsuario inicial={$tipoUsuario} enviado a calcularFeedPersonalizado");
                    $posts_personalizados = calcularFeedPersonalizado($current_user_id, '', '', $tipoUsuario);

                    if (!$posts_personalizados) {
                        guardarLog("Error: Fallo al calcular feed personalizado para usuario ID: $current_user_id");
                        return ['post_ids' => [], 'post_not_in' => []];
                    }

                    // Guardar en caché y respaldo
                    $cache_content = ['posts' => $posts_personalizados, 'timestamp' => time()];
                    guardarCache($cache_key, $cache_content, $cache_time);
                }
            }
        }
    }

    // Eliminar los respaldos de opciones relacionados
    /*
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
    */
    //borra la cache de calculo de posts
    borrarCache('feed_datos_' . $current_user_id);
    guardarLog("Caché específica eliminada: feed_datos_$current_user_id");

    guardarLog("Reinicio de feed completado para usuario ID: $current_user_id - Total de cachés eliminadas: $transients_eliminados");

    return $transients_eliminados;
}

// Refactor(Org): Funciones movidas desde app/Content/Logic/feed.php
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

// Refactor(Org): Funciones movidas desde app/Content/Logic/feed.php
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

// Refactor(Exec): Moved functions obtenerDatosFeed and obtenerDatosFeedConCache from app/Content/Logic/datosParaCalculo.php
function obtenerDatosFeed($userId) {
    $log = "[obtenerDatosFeed] Inicio para usuario ID: $userId \n";
    $tiempoInicio = microtime(true);

    try {
        if (!comprobarConexionBD()) {
            $log .= "[obtenerDatosFeed] Error: No se pudo conectar a la base de datos. \n";
            //guardarLog($log);
            return [];
        }

        if (!validarUsuario($userId)) {
            $log .= "[obtenerDatosFeed] Error: Usuario no válido. \n";
            //guardarLog($log);
            return [];
        }

        // Funcion obtenerUsuariosSeguidos movida a app/Services/FollowService.php
        // Se asume que la función está disponible globalmente o se cargará desde FollowService
        $siguiendo = obtenerUsuariosSeguidos($userId);
        // Se asume que la función obtenerInteresesUsuario está disponible globalmente (movida a UserService.php)
        $intereses = obtenerInteresesUsuario($userId);
        $vistas = vistasDatos($userId);
        generarMetaDeIntereses($userId);
        $log .= "[obtenerDatosFeed] Tiempo para vistas y generarMetaDeIntereses: " . (microtime(true) - $tiempoInicio) . " segundos \n";

        $postsIds = obtenerIdsPostsRecientes();
        if (empty($postsIds)) {
            $log .= "[obtenerDatosFeed] Aviso: No se encontraron posts recientes. \n";
            //guardarLog($log);
            $log .= "[obtenerDatosFeed] Terminó con aviso (sin posts) en " . (microtime(true) - $tiempoInicio) . " segundos \n";
            return [];
        }
        $cacheKey = 'metaData_' . md5(implode('_', $postsIds));
        $metaData = obtenerCache($cacheKey);

        if ($metaData === false) {
            $metaData = obtenerMetadatosPosts($postsIds);
            guardarCache($cacheKey, $metaData, 14400); 
        } else {
            $log .= "[obtenerDatosFeed] MetaData obtenido de la caché. \n";
        }

        $metaRoles = procesarMetadatosRoles($metaData);
        // Refactor(Org): Función obtenerLikesPorPost() movida a app/Services/LikeService.php
        // Se asume que la función está disponible globalmente o se cargará desde LikeService
        $likesPorPost = obtenerLikesPorPost($postsIds);
        $postsResultados = obtenerDatosBasicosPosts($postsIds);
        $postContenido = procesarContenidoPosts($postsResultados);

        $tiempoFin = microtime(true);
        $tiempoTotal = $tiempoFin - $tiempoInicio;
        $log .= "[obtenerDatosFeed] Fin. Tiempo total: $tiempoTotal segundos";
        guardarLog($log);

        return [
            'siguiendo'        => $siguiendo,
            'interesesUsuario' => $intereses,
            'posts_ids'        => $postsIds,
            'likes_by_post'    => $likesPorPost,
            'meta_data'        => $metaData,
            'meta_roles'       => $metaRoles,
            'author_results'   => $postsResultados,
            'post_content'     => $postContenido,
        ];

    } catch (Exception $e) {
        $log .= "[obtenerDatosFeed] Error: " . $e->getMessage() . "\n";
        //guardarLog($log);
        $log .= "[obtenerDatosFeed] Terminó con error en " . (microtime(true) - $tiempoInicio) . " segundos \n";
        return [];
    }
}

function obtenerDatosFeedConCache($userId)
{
    $cache_key = 'feed_datos_' . $userId;
    $datos = obtenerCache($cache_key);

    if (false === $datos) {
        //guardarLog("Usuario ID: $userId - Caché no encontrada, calculando nuevos datos de feed");
        $datos = obtenerDatosFeed($userId);
        guardarCache($cache_key, $datos, 43200); // Guarda en caché por 12 horas
        //guardarLog("Usuario ID: $userId - Nuevos datos de feed guardados en caché por 12 horas");
    } else {
        //guardarLog("Usuario ID: $userId - Usando datos de feed desde caché");
    }

    if (!isset($datos['author_results']) || !is_array($datos['author_results'])) {
        ////guardarLog("Usuario ID: $userId - Error: Datos de feed inválidos o vacíos");
        return [];
    }

    return $datos;
}

?>
