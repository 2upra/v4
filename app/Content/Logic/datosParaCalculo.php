<?

function obtenerDatosFeed($userId)
{
    try {
        global $wpdb;
        if (!$wpdb) {
            guardarLog("[obtenerDatosFeed] Error crítico: No se pudo acceder a la base de datos wpdb");
            return [];
        }

        if (!$userId) {
            guardarLog("[obtenerDatosFeed] Error: ID de usuario no válido");
            return [];
        }

        $tablaLikes = "{$wpdb->prefix}post_likes";
        $tablaIntereses = INTERES_TABLE;

        $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
        if ($siguiendo === false) {
            guardarLog("[obtenerDatosFeed] Advertencia: No se encontraron usuarios seguidos para el usuario ID: " . $userId);
        }

        $intereses = $wpdb->get_results($wpdb->prepare(
            "SELECT interest, intensity FROM $tablaIntereses WHERE user_id = %d",
            $userId
        ), OBJECT_K);
        if ($wpdb->last_error) {
            guardarLog("[obtenerDatosFeed] Error: Fallo al obtener intereses del usuario: " . $wpdb->last_error);
        }
        $vistas = get_user_meta($userId, 'vistas_posts', true);
        generarMetaDeIntereses($userId);


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

        if (empty($postsIds)) {
            guardarLog("[obtenerDatosFeed] Aviso: No se encontraron posts en los últimos 365 días");
            return [];
        }


        $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));
        $metaKeys = ['datosAlgoritmo', 'Verificado', 'postAut', 'artista', 'fan'];
        $metaKeysPlaceholders = implode(',', array_fill(0, count($metaKeys), '%s'));


        $sqlMeta = "
            SELECT post_id, meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key IN ($metaKeysPlaceholders) AND post_id IN ($placeholders)
        ";
        $preparedSqlMeta = $wpdb->prepare($sqlMeta, array_merge($metaKeys, $postsIds));
        $metaResultados = $wpdb->get_results($preparedSqlMeta);

        if ($wpdb->last_error) {
            guardarLog("[obtenerDatosFeed] Error: Fallo al obtener metadata: " . $wpdb->last_error);
        }


        $metaData = [];
        foreach ($metaResultados as $meta) {
            $metaData[$meta->post_id][$meta->meta_key] = $meta->meta_value;
        }


        $metaRoles = [];
        foreach ($metaData as $postId => $meta) {
            $metaRoles[$postId] = [
                'artista' => isset($meta['artista']) ? filter_var($meta['artista'], FILTER_VALIDATE_BOOLEAN) : false,
                'fan'     => isset($meta['fan']) ? filter_var($meta['fan'], FILTER_VALIDATE_BOOLEAN) : false,
            ];
        }

        $sqlLikes = "
            SELECT post_id, like_type, COUNT(*) as cantidad
            FROM $tablaLikes
            WHERE post_id IN ($placeholders)
            GROUP BY post_id, like_type
        ";

        $args = array_merge([$sqlLikes], $postsIds);
        $likesResultados = $wpdb->get_results(call_user_func_array([$wpdb, 'prepare'], $args));

        if ($wpdb->last_error) {
            guardarLog("[obtenerDatosFeed] Error: Fallo al obtener likes: " . $wpdb->last_error);
        }
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


        $sqlPosts = "
            SELECT ID, post_author, post_date, post_content
            FROM {$wpdb->posts}
            WHERE ID IN ($placeholders)
        ";
        $postsResultados = $wpdb->get_results($wpdb->prepare($sqlPosts, $postsIds), OBJECT_K);

        if ($wpdb->last_error) {
            guardarLog("[obtenerDatosFeed] Error: Fallo al obtener posts: " . $wpdb->last_error);
        }


        $postContenido = [];
        foreach ($postsResultados as $post) {
            $postContenido[$post->ID] = $post->post_content;
        }

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
        return [];
    }
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
