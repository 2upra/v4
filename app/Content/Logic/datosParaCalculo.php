<?
function obtenerDatosFeed($userId) {
    try {
        global $wpdb;
        if (!$wpdb) {
            error_log("[obtenerDatosFeed] Error crítico: No se pudo acceder a la base de datos wpdb");
            return [];
        }

        if (!$userId) {
            error_log("[obtenerDatosFeed] Error: ID de usuario no válido");
            return [];
        }

        $table_likes = "{$wpdb->prefix}post_likes";
        $table_intereses = INTERES_TABLE;

        // Obtener datos de usuario
        $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
        if ($siguiendo === false) {
            error_log("[obtenerDatosFeed] Advertencia: No se encontraron usuarios seguidos para el usuario ID: " . $userId);
        }

        // Obtener intereses
        $interesesUsuario = $wpdb->get_results($wpdb->prepare(
            "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
            $userId
        ), OBJECT_K);

        if ($wpdb->last_error) {
            error_log("[obtenerDatosFeed] Error: Fallo al obtener intereses del usuario: " . $wpdb->last_error);
        }

        $vistas_posts = get_user_meta($userId, 'vistas_posts', true);
        generarMetaDeIntereses($userId);

        // Obtener posts
        $args = [
            'post_type'      => 'social_post',
            'posts_per_page' => 50000,
            'date_query'     => [
                'after' => date('Y-m-d', strtotime('-100 days'))
            ],
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ];
        $posts_ids = get_posts($args);

        if (empty($posts_ids)) {
            error_log("[obtenerDatosFeed] Aviso: No se encontraron posts en los últimos 100 días");
            return [];
        }

        // Preparar consultas
        $placeholders = implode(', ', array_fill(0, count($posts_ids), '%d'));
        $meta_keys = ['datosAlgoritmo', 'Verificado', 'postAut', 'artista', 'fan'];
        $meta_keys_placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));

        // Obtener metadata
        $sql_meta = "
            SELECT post_id, meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key IN ($meta_keys_placeholders) AND post_id IN ($placeholders)
        ";
        $prepared_sql_meta = $wpdb->prepare($sql_meta, array_merge($meta_keys, $posts_ids));
        $meta_results = $wpdb->get_results($prepared_sql_meta);

        if ($wpdb->last_error) {
            error_log("[obtenerDatosFeed] Error: Fallo al obtener metadata: " . $wpdb->last_error);
        }

        // Procesar metadata
        $meta_data = [];
        foreach ($meta_results as $meta_row) {
            $meta_data[$meta_row->post_id][$meta_row->meta_key] = $meta_row->meta_value;
        }

        // Procesar metas adicionales (artista o fan)
        $meta_roles = [];
        foreach ($meta_data as $post_id => $meta) {
            // Verificar si 'artista' o 'fan' existen y si alguno es true
            $meta_roles[$post_id] = [
                'artista' => isset($meta['artista']) ? filter_var($meta['artista'], FILTER_VALIDATE_BOOLEAN) : false,
                'fan'     => isset($meta['fan']) ? filter_var($meta['fan'], FILTER_VALIDATE_BOOLEAN) : false,
            ];
        }

        // Obtener likes
        $sql_likes = "
            SELECT post_id, COUNT(*) as likes_count
            FROM $table_likes
            WHERE post_id IN ($placeholders)
            GROUP BY post_id
        ";
        $likes_results = $wpdb->get_results($wpdb->prepare($sql_likes, $posts_ids));

        if ($wpdb->last_error) {
            error_log("[obtenerDatosFeed] Error: Fallo al obtener likes: " . $wpdb->last_error);
        }

        // Procesar likes
        $likes_by_post = [];
        foreach ($likes_results as $like_row) {
            $likes_by_post[$like_row->post_id] = $like_row->likes_count;
        }

        // Obtener posts
        $sql_posts = "
            SELECT ID, post_author, post_date, post_content
            FROM {$wpdb->posts}
            WHERE ID IN ($placeholders)
        ";
        $posts_results = $wpdb->get_results($wpdb->prepare($sql_posts, $posts_ids), OBJECT_K);

        if ($wpdb->last_error) {
            error_log("[obtenerDatosFeed] Error: Fallo al obtener posts: " . $wpdb->last_error);
        }

        // Procesar contenido
        $post_content = [];
        foreach ($posts_results as $post) {
            $post_content[$post->ID] = $post->post_content;
        }

        return [
            'siguiendo'        => $siguiendo,
            'interesesUsuario' => $interesesUsuario,
            'posts_ids'        => $posts_ids,
            'likes_by_post'    => $likes_by_post,
            'meta_data'        => $meta_data,
            'meta_roles'       => $meta_roles,  // Roles adicionales (artista/fan)
            'author_results'   => $posts_results,
            'post_content'     => $post_content,
        ];

    } catch (Exception $e) {
        error_log("[obtenerDatosFeed] Error crítico: " . $e->getMessage());
        return [];
    }
}

function obtenerDatosFeedConCache($userId) {
    $cache_key = 'feed_datos_' . $userId;
    $datos = obtenerCache($cache_key);
    
    if (false === $datos) {
        //guardarLog("Usuario ID: $userId - Caché no encontrada, calculando nuevos datos de feed");
        $datos = obtenerDatosFeed($userId);
        //guardarCache($cache_key, $datos, 43200); // Guarda en caché por 12 horas
        //guardarLog("Usuario ID: $userId - Nuevos datos de feed guardados en caché por 12 horas");
    } else {
        //guardarLog("Usuario ID: $userId - Usando datos de feed desde caché");
    }
    
    if (!isset($datos['author_results']) || !is_array($datos['author_results'])) {
        //guardarLog("Usuario ID: $userId - Error: Datos de feed inválidos o vacíos");
        return [];
    }

    return $datos;
}