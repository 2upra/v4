<?

/*
2.5 segundos
*/

function obtenerDatosFeed($userId)
{
    rendimientolog("[obtenerDatosFeed] Inicio de la función para el usuario ID: " . $userId);
    $tiempoInicio = microtime(true);

    try {
        if (!comprobarConexionBD()) {
            return [];
        }

        if (!validarUsuario($userId)) {
            return [];
        }

        $siguiendo = obtenerUsuariosSeguidos($userId);
        $intereses = obtenerInteresesUsuario($userId);
        $vistas = vistasDatos($userId);
        generarMetaDeIntereses($userId);
        rendimientolog("[obtenerDatosFeed] Tiempo para obtener 'vistas' y generarMetaDeIntereses: " . (microtime(true) - $tiempoInicio) . " segundos");

        $postsIds = obtenerIdsPostsRecientes();
        if (empty($postsIds)) {
            guardarLog("[obtenerDatosFeed] Aviso: No se encontraron posts en los últimos 365 días");
            rendimientolog("[obtenerDatosFeed] Terminó con aviso (sin posts) en " . (microtime(true) - $tiempoInicio) . " segundos");
            return [];
        }

        $metaData = obtenerMetadatosPosts($postsIds);
        $metaRoles = procesarMetadatosRoles($metaData);
        $likesPorPost = obtenerLikesPorPost($postsIds);
        $postsResultados = obtenerDatosBasicosPosts($postsIds);
        $postContenido = procesarContenidoPosts($postsResultados);

        $tiempoFin = microtime(true);
        $tiempoTotal = $tiempoFin - $tiempoInicio;
        rendimientolog("[obtenerDatosFeed] Fin de la función. Tiempo total de ejecución: " . $tiempoTotal . " segundos");

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
        guardarLog("[obtenerDatosFeed] Error crítico: " . $e->getMessage());
        rendimientolog("[obtenerDatosFeed] Terminó con error crítico (Exception) en " . (microtime(true) - $tiempoInicio) . " segundos");
        return [];
    }
}

function obtenerUsuariosSeguidos($userId)
{
    $tiempoInicio = microtime(true);


    global $wpdb;
    $siguiendo = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT meta_value 
             FROM {$wpdb->usermeta} 
             WHERE user_id = %d AND meta_key = 'siguiendo'",
            $userId
        )
    );

    if (empty($siguiendo)) {
        guardarLog("[obtenerUsuariosSeguidos] Advertencia: No se encontraron usuarios seguidos para el usuario ID: " . $userId);
        $siguiendo = [];
    } else {

        $siguiendo = maybe_unserialize($siguiendo[0]);
        //si no es un array devolver un array vacio
        $siguiendo = is_array($siguiendo) ? $siguiendo : [];
    }

    rendimientolog("[obtenerUsuariosSeguidos] Tiempo para obtener 'siguiendo': " . (microtime(true) - $tiempoInicio) . " segundos");
    return $siguiendo;
}


function comprobarConexionBD()
{
    global $wpdb;
    $tiempoInicio = microtime(true);

    if (!$wpdb) {
        guardarLog("[comprobarConexionBD] Error crítico: No se pudo acceder a la base de datos wpdb");
        rendimientolog("[comprobarConexionBD] Terminó con error crítico (sin acceso a \$wpdb) en " . (microtime(true) - $tiempoInicio) . " segundos");
        return false;
    }
    return true;
}

function validarUsuario($userId)
{
    $tiempoInicio = microtime(true);
    if (!$userId) {
        guardarLog("[validarUsuario] Error: ID de usuario no válido");
        rendimientolog("[validarUsuario] Terminó con error (ID de usuario no válido) en " . (microtime(true) - $tiempoInicio) . " segundos");
        return false;
    }
    return true;
}

function obtenerInteresesUsuario($userId)
{
    global $wpdb;
    $tiempoInicio = microtime(true);
    $tablaIntereses = INTERES_TABLE;
    $intereses = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $tablaIntereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);
    if ($wpdb->last_error) {
        guardarLog("[obtenerInteresesUsuario] Error: Fallo al obtener intereses del usuario: " . $wpdb->last_error);
    }
    rendimientolog("[obtenerInteresesUsuario] Tiempo para obtener 'intereses': " . (microtime(true) - $tiempoInicio) . " segundos");
    return $intereses;
}

function vistasDatos($userId)
{
    $tiempoInicio = microtime(true);
    $vistas = get_user_meta($userId, 'vistas_posts', true);
    rendimientolog("[vistasDatos] Tiempo para obtener 'vistas': " . (microtime(true) - $tiempoInicio) . " segundos");
    return $vistas;
}

function obtenerIdsPostsRecientes()
{
    $tiempoInicio = microtime(true);
    $args = [
        'post_type'      => 'social_post',
        'posts_per_page' => 50000,
        'date_query'     => [
            'after' => date('Y-m-d', strtotime('-365 days'))
        ],
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ];
    $postsIds = get_posts($args);
    rendimientolog("[obtenerIdsPostsRecientes] Tiempo para obtener \$postsIds: " . (microtime(true) - $tiempoInicio) . " segundos");
    return $postsIds;
}

//usa function guardarCache($cacheKey, $data, $exp) y function obtenerCache($cacheKey) aca
function obtenerMetadatosPosts($postsIds)
{
    global $wpdb;
    $tiempoInicio = microtime(true);
    $log = "[obtenerMetadatosPosts] ";

    $metaKeys = ['datosAlgoritmo', 'Verificado', 'postAut', 'artista', 'fan'];
    $metaData = [];

    foreach ($postsIds as $postId) {
        $cacheKey = 'post_metadata_' . $postId;
        $cachedData = obtenerCache($cacheKey);

        if ($cachedData !== false) {
            $metaData[$postId] = $cachedData;
            $log .= "Datos de caché encontrados para post_id: $postId. ";
        } else {
            $log .= "Datos de caché no encontrados para post_id: $postId. ";
            $postMetaData = [];
            foreach ($metaKeys as $metaKey) {
                $metaValue = get_post_meta($postId, $metaKey, true);
                if ($metaValue) {
                    $postMetaData[$metaKey] = $metaValue;
                }
            }

            if (!empty($postMetaData)) {
                guardarCache($cacheKey, $postMetaData, 4 * HOUR_IN_SECONDS);
                $log .= "Datos guardados en caché para post_id: $postId. ";
                $metaData[$postId] = $postMetaData;
            }
        }
    }

    $tiempoTotal = microtime(true) - $tiempoInicio;
    $log .= "\n Tiempo total de ejecución: $tiempoTotal segundos.";
    guardarLog($log);

    return $metaData;
}

function procesarMetadatosRoles($metaData)
{
    $tiempoInicio = microtime(true);
    $metaRoles = [];
    foreach ($metaData as $postId => $meta) {
        $metaRoles[$postId] = [
            'artista' => isset($meta['artista']) ? filter_var($meta['artista'], FILTER_VALIDATE_BOOLEAN) : false,
            'fan'     => isset($meta['fan']) ? filter_var($meta['fan'], FILTER_VALIDATE_BOOLEAN) : false,
        ];
    }
    rendimientolog("[procesarMetadatosRoles] Tiempo para procesar \$metaRoles: " . (microtime(true) - $tiempoInicio) . " segundos");
    return $metaRoles;
}

function obtenerLikesPorPost($postsIds)
{
    global $wpdb;
    $tiempoInicio = microtime(true);
    $tablaLikes = "{$wpdb->prefix}post_likes";

    $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));

    $sqlLikes = "
        SELECT post_id, like_type, COUNT(*) as cantidad
        FROM $tablaLikes
        WHERE post_id IN ($placeholders)
        GROUP BY post_id, like_type
    ";

    $args = array_merge([$sqlLikes], $postsIds);
    $likesResultados = $wpdb->get_results(call_user_func_array([$wpdb, 'prepare'], $args));

    if ($wpdb->last_error) {
        guardarLog("[obtenerLikesPorPost] Error: Fallo al obtener likes: " . $wpdb->last_error);
    }
    rendimientolog("[obtenerLikesPorPost] Tiempo para obtener \$likesResultados: " . (microtime(true) - $tiempoInicio) . " segundos");

    $likesPorPost = [];
    foreach ($likesResultados as $like) {
        if (!isset($likesPorPost[$like->post_id])) {
            $likesPorPost[$like->post_id] = [
                'like' => 0,
                'favorito' => 0,
                'no_me_gusta' => 0
            ];
        }
        $likesPorPost[$like->post_id][$like->like_type] = (int)$like->cantidad;
    }
    rendimientolog("[obtenerLikesPorPost] Tiempo para procesar \$likesPorPost: " . (microtime(true) - $tiempoInicio) . " segundos");

    return $likesPorPost;
}


function obtenerDatosBasicosPosts($postsIds)
{
    global $wpdb;
    $tiempoInicio = microtime(true);

    $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));

    $sqlPosts = "
        SELECT ID, post_author, post_date, post_content
        FROM {$wpdb->posts}
        WHERE ID IN ($placeholders)
    ";
    $postsResultados = $wpdb->get_results($wpdb->prepare($sqlPosts, $postsIds), OBJECT_K);

    if ($wpdb->last_error) {
        guardarLog("[obtenerDatosBasicosPosts] Error: Fallo al obtener posts: " . $wpdb->last_error);
    }
    rendimientolog("[obtenerDatosBasicosPosts] Tiempo para obtener \$postsResultados: " . (microtime(true) - $tiempoInicio) . " segundos");

    return $postsResultados;
}

function procesarContenidoPosts($postsResultados)
{
    $tiempoInicio = microtime(true);
    $postContenido = [];
    foreach ($postsResultados as $post) {
        $postContenido[$post->ID] = $post->post_content;
    }
    rendimientolog("[procesarContenidoPosts] Tiempo para procesar \$postContenido: " . (microtime(true) - $tiempoInicio) . " segundos");
    return $postContenido;
}

function obtenerDatosFeedConCache($userId)
{
    $cache_key = 'feed_datos_' . $userId;
    $datos = obtenerCache($cache_key);

    if (false === $datos) {
        guardarLog("Usuario ID: $userId - Caché no encontrada, calculando nuevos datos de feed");
        $datos = obtenerDatosFeed($userId);
        guardarCache($cache_key, $datos, 43200); // Guarda en caché por 12 horas
        guardarLog("Usuario ID: $userId - Nuevos datos de feed guardados en caché por 12 horas");
    } else {
        guardarLog("Usuario ID: $userId - Usando datos de feed desde caché");
    }

    if (!isset($datos['author_results']) || !is_array($datos['author_results'])) {
        //guardarLog("Usuario ID: $userId - Error: Datos de feed inválidos o vacíos");
        return [];
    }

    return $datos;
}
