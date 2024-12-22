<?

function obtenerLikesDelUsuario($userId, $limit = 500)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_likes';
    $query = $wpdb->prepare(
        "SELECT post_id, like_type FROM $table_name WHERE user_id = %d ORDER BY like_date DESC LIMIT %d",
        $userId,
        $limit
    );
    $liked_posts = $wpdb->get_results($query); // Cambiado a get_results para obtener todas las columnas
    if (empty($liked_posts)) {
        return [];
    }
    return $liked_posts;
}

function generarMetaDeIntereses($user_id) {
    // Validación inicial del user_id
    if (empty($user_id) || !is_numeric($user_id)) {
        error_log("ID de usuario inválido en generarMetaDeIntereses: " . print_r($user_id, true));
        return false;
    }

    // Verificar cache
    $cache_key = 'meta_intereses_' . $user_id;
    $cached_result = get_transient($cache_key);
    if ($cached_result !== false) {
        return $cached_result;
    }

    global $wpdb;
    $likePost = obtenerLikesDelUsuario($user_id, 500);
    if (empty($likePost) || !is_array($likePost)) {
        error_log("No se encontraron likes para el usuario: " . $user_id);
        return false;
    }

    // Obtener intereses actuales
    $interesesActuales = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $user_id
    ), OBJECT_K);

    // Preparar la consulta para los posts
    try {
        // Extraer solo los post_id de $likePost
        $post_ids = array_map(function($like) { return $like->post_id; }, $likePost);
        
        // Convertir array de IDs a string de placeholders
        $placeholders = implode(',', array_map(function() { return '%d'; }, $post_ids));
        
        $query = "
            SELECT p.ID, p.post_content, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
            WHERE p.ID IN ($placeholders)
        ";
        
        // Preparar la consulta con los valores
        $sql = $wpdb->prepare($query, $post_ids);
        $post_data = $wpdb->get_results($sql);

        if (empty($post_data)) {
            error_log("No se encontraron datos de posts para los likes del usuario: " . $user_id);
            return false;
        }

        $tag_intensidad = [];

        foreach ($post_data as $post) {
            // Obtener el tipo de like del post actual
            $like_data = array_values(array_filter($likePost, function($like) use ($post) {
                return $like->post_id == $post->ID;
            }));

            $like_type = $like_data[0]->like_type ?? 'like'; // 'like' por defecto
            
            // Determinar el multiplicador basado en el tipo de like
            $multiplicador = 1; // Por defecto para 'like'
            if ($like_type === 'favorito') {
                $multiplicador = 2;
            } elseif ($like_type === 'no_me_gusta') {
                $multiplicador = -1;
            }
            
            // Procesar datosAlgoritmo
            if (!empty($post->meta_value)) {
                $datosAlgoritmo = json_decode($post->meta_value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error al decodificar JSON para post ID " . $post->ID . ": " . json_last_error_msg());
                    continue;
                }

                if (is_array($datosAlgoritmo)) {
                    foreach ($datosAlgoritmo as $key => $value) {
                        if (is_array($value)) {
                            foreach (['es', 'en'] as $lang) {
                                if (isset($value[$lang]) && is_array($value[$lang])) {
                                    foreach ($value[$lang] as $item) {
                                        if (is_string($item)) {
                                            $item = normalizarTexto($item);
                                            $tag_intensidad[$item] = ($tag_intensidad[$item] ?? 0) + $multiplicador;
                                        }
                                    }
                                }
                            }
                        } elseif (is_string($value) && !empty($value)) {
                            $value = normalizarTexto($value);
                            $tag_intensidad[$value] = ($tag_intensidad[$value] ?? 0) + $multiplicador;
                        }
                    }
                }
            }

            // Procesar contenido del post
            if (!empty($post->post_content)) {
                $content = wp_strip_all_tags($post->post_content);
                $content = normalizarTexto($content);
                $palabras = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($palabras as $palabra) {
                    $palabra = trim($palabra);
                    if (!empty($palabra)) {
                        $tag_intensidad[$palabra] = ($tag_intensidad[$palabra] ?? 0) + $multiplicador;
                    }
                }
            }
        }

        if (empty($tag_intensidad)) {
            error_log("No se generaron tags de intensidad para el usuario: " . $user_id);
            return false;
        }

        arsort($tag_intensidad);
        $tag_intensidad = array_slice($tag_intensidad, 0, 200, true);

        $result = actualizarIntereses($user_id, $tag_intensidad, $interesesActuales);
        
        if ($result !== false) {
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
        }

        return $result;

    } catch (Exception $e) {
        error_log("Error en generarMetaDeIntereses: " . $e->getMessage());
        return false;
    }
}

function actualizarIntereses($user_id, $tag_intensidad, $interesesActuales)
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');

    try {
        $batch_values = [];
        $intereses_nuevos = array_keys($tag_intensidad);

        foreach ($tag_intensidad as $interest => $intensity) {
            $batch_values[] = $wpdb->prepare('(%d, %s, %d)', $user_id, $interest, $intensity);
        }

        if (!empty($batch_values)) {
            $values = implode(', ', $batch_values);
            $sql = "
                INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity)
                VALUES $values
                ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)
            ";
            $wpdb->query($sql);
        }

        $intereses_a_eliminar = array_diff_key($interesesActuales, $tag_intensidad);
        if (!empty($intereses_a_eliminar)) {
            $placeholders = implode(', ', array_fill(0, count($intereses_a_eliminar), '%s'));
            $sql = $wpdb->prepare(
                "DELETE FROM " . INTERES_TABLE . " WHERE user_id = %d AND interest IN ($placeholders)",
                array_merge([$user_id], array_keys($intereses_a_eliminar))
            );
            $wpdb->query($sql);
        }

        $wpdb->query('COMMIT');

        return true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Error al actualizar intereses: ' . $e->getMessage());
        return false;
    }
}

