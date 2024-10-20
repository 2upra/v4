<?

global $wpdb;
define('INTERES_TABLE', "{$wpdb->prefix}interes");
define('BATCH_SIZE', 1000);


function generarMetaDeIntereses($user_id)
{
    global $wpdb;

    // Obtener los likes del usuario
    $likePost = obtenerLikesDelUsuario($user_id, 500);
    if (empty($likePost)) {
        return false;
    }

    // Obtener los intereses actuales del usuario
    $interesesActuales = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $user_id
    ), OBJECT_K);

    // Preparar placeholders para la consulta IN
    $placeholders = implode(', ', array_fill(0, count($likePost), '%d'));

    // Obtener los datos de los posts que el usuario ha dado like
    $query = "
        SELECT p.ID, p.post_content, pm.meta_value
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
        WHERE p.ID IN ($placeholders)
    ";
    $sql = $wpdb->prepare($query, $likePost);
    $post_data = $wpdb->get_results($sql);

    if (empty($post_data)) {
        logAlgoritmo("No se encontraron datos para los posts con likes del usuario: $user_id");
        return false;
    }

    $tag_intensidad = [];

    foreach ($post_data as $post) {
        $datosAlgoritmo = !empty($post->meta_value) ? json_decode($post->meta_value, true) : [];

        // Procesar todos los campos de datosAlgoritmo
        foreach ($datosAlgoritmo as $key => $value) {
            if (is_array($value)) {
                // Verificar si hay versiones en español e inglés
                if (isset($value['es']) && is_array($value['es'])) {
                    foreach ($value['es'] as $item) {
                        $tag_intensidad[$item] = isset($tag_intensidad[$item]) ? $tag_intensidad[$item] + 1 : 1;
                    }
                }
                if (isset($value['en']) && is_array($value['en'])) {
                    foreach ($value['en'] as $item) {
                        $tag_intensidad[$item] = isset($tag_intensidad[$item]) ? $tag_intensidad[$item] + 1 : 1;
                    }
                }
            } elseif (!empty($value)) {
                // Si el valor es un string o un número, simplemente lo agregamos como un interés
                $tag_intensidad[$value] = isset($tag_intensidad[$value]) ? $tag_intensidad[$value] + 1 : 1;
            }
        }

        // Procesar palabras del contenido del post
        $content = wp_strip_all_tags($post->post_content);
        $content = strtolower($content);
        $content = preg_replace('/[^a-z0-9\s]+/', '', $content);
        $palabras = preg_split('/\s+/', $content);

        foreach ($palabras as $palabra) {
            $palabra = trim($palabra);
            if (!empty($palabra)) {
                $tag_intensidad[$palabra] = isset($tag_intensidad[$palabra]) ? $tag_intensidad[$palabra] + 1 : 1;
            }
        }
    }

    return actualizarIntereses($user_id, $tag_intensidad, $interesesActuales);
}



function actualizarIntereses($user_id, $tag_intensidad, $interesesActuales)
{
    global $wpdb;

    // Iniciar transacción
    $wpdb->query('START TRANSACTION');

    try {
        // 1. Limitar a los 100 intereses más intensos
        uasort($tag_intensidad, function($a, $b) {
            return $b['intensity'] - $a['intensity'];
        });
        $tag_intensidad = array_slice($tag_intensidad, 0, 100, true);

        // 2. Preparar batch para inserción/actualización
        $batch_values = [];
        foreach ($tag_intensidad as $interest => $data) {
            $intensity = $data['intensity'];
            $batch_values[] = $wpdb->prepare('(%d, %s, %d)', $user_id, $interest, $intensity);
        }

        // 3. Insertar o actualizar intereses en lote
        if (!empty($batch_values)) {
            $values = implode(', ', $batch_values);
            $sql = "
                INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity)
                VALUES $values
                ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)
            ";
            $wpdb->query($sql);
        }

        // 4. Eliminar intereses que ya no aplican (los que no están entre los 100 más intensos)
        $intereses_a_eliminar = array_diff_key($interesesActuales, $tag_intensidad);
        if (!empty($intereses_a_eliminar)) {
            $placeholders = implode(', ', array_fill(0, count($intereses_a_eliminar), '%s'));
            $sql = $wpdb->prepare(
                "DELETE FROM " . INTERES_TABLE . " WHERE user_id = %d AND interest IN ($placeholders)",
                array_merge([$user_id], array_keys($intereses_a_eliminar))
            );
            $wpdb->query($sql);
        }

        // 5. Confirmar transacción
        $wpdb->query('COMMIT');
        
        // 6. Log de éxito
        logAlgoritmo("Intereses actualizados exitosamente para el usuario: $user_id");
        return true;
    } catch (Exception $e) {
        // 7. Manejo de errores y rollback
        $wpdb->query('ROLLBACK');
        error_log('Error al actualizar intereses: ' . $e->getMessage());
        logAlgoritmo("Error al actualizar intereses: " . $e->getMessage());
        return false;
    }
}


function calcularFeedPersonalizado($userId)
{
    global $wpdb;
    $table_likes = "{$wpdb->prefix}post_likes";
    $table_intereses = INTERES_TABLE;

    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);

    generarMetaDeIntereses($userId);  // Generar o actualizar los intereses del usuario
    logAlgoritmo("Intereses del usuario generados para el usuario ID: $userId");

    // Obtener intereses del usuario
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);

    logAlgoritmo("Intereses del usuario obtenidos: " . json_encode($interesesUsuario));

    // Obtener IDs de los posts relevantes
    $args = [
        'post_type'      => 'social_post',
        'posts_per_page' => 1000,
        'date_query'     => [
            'after' => date('Y-m-d', strtotime('-100 days'))
        ],
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ];
    $posts_ids = get_posts($args);

    if (empty($posts_ids)) {
        logAlgoritmo("No se encontraron posts para el feed del usuario ID: $userId");
        return [];
    }

    logAlgoritmo("Consulta de posts realizada, total de posts: " . count($posts_ids));

    // Obtener datos necesarios en lote
    $placeholders = implode(', ', array_fill(0, count($posts_ids), '%d'));

    // Obtener likes de los posts
    $sql_likes = "
        SELECT post_id, COUNT(*) as likes_count
        FROM $table_likes
        WHERE post_id IN ($placeholders)
        GROUP BY post_id
    ";
    $likes_results = $wpdb->get_results($wpdb->prepare($sql_likes, $posts_ids));
    $likes_by_post = [];
    foreach ($likes_results as $like_row) {
        $likes_by_post[$like_row->post_id] = $like_row->likes_count;
    }

    // Obtener metadata de los posts
    $sql_meta = "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'datosAlgoritmo' AND post_id IN ($placeholders)
    ";
    $meta_results = $wpdb->get_results($wpdb->prepare($sql_meta, $posts_ids), OBJECT_K);

    // Obtener autores de los posts
    $sql_authors = "
        SELECT ID, post_author, post_date
        FROM {$wpdb->posts}
        WHERE ID IN ($placeholders)
    ";
    $author_results = $wpdb->get_results($wpdb->prepare($sql_authors, $posts_ids), OBJECT_K);

    $posts_personalizados = [];
    $resumenPuntos = [];

    foreach ($author_results as $post_id => $post_data) {
        $autor_id = $post_data->post_author;
        $post_date = $post_data->post_date;

        // Puntos si el usuario sigue al autor
        $puntosUsuario = in_array($autor_id, $siguiendo) ? 50 : 0;

        // Puntos asignados por intereses, ahora abarcando más campos de 'datosAlgoritmo'
        $puntosIntereses = 0;
        $datosAlgoritmo = !empty($meta_results[$post_id]->meta_value) ? json_decode($meta_results[$post_id]->meta_value, true) : [];

        // Iterar sobre todos los campos de datosAlgoritmo
        foreach ($datosAlgoritmo as $key => $value) {
            if (is_array($value)) {
                // Procesar versiones en español ('es') e inglés ('en')
                if (isset($value['es']) && is_array($value['es'])) {
                    foreach ($value['es'] as $item) {
                        if (isset($interesesUsuario[$item])) {
                            $puntosIntereses += 10 + $interesesUsuario[$item]->intensity;
                        }
                    }
                }
                if (isset($value['en']) && is_array($value['en'])) {
                    foreach ($value['en'] as $item) {
                        if (isset($interesesUsuario[$item])) {
                            $puntosIntereses += 10 + $interesesUsuario[$item]->intensity;
                        }
                    }
                }
            } elseif (!empty($value) && isset($interesesUsuario[$value])) {
                // Si el valor es un string simple o numérico, verificar si coincide con algún interés
                $puntosIntereses += 10 + $interesesUsuario[$value]->intensity;
            }
        }

        // Puntos por likes
        $likes = isset($likes_by_post[$post_id]) ? $likes_by_post[$post_id] : 0;
        $puntosLikes = 5 + $likes;

        // Puntos por tiempo desde la publicación
        $horasDesdePublicacion = (current_time('timestamp') - strtotime($post_date)) / 3600;
        $factorTiempo = pow(0.98, $horasDesdePublicacion);

        // Verificar metas 'Verificado' y 'postAut'
        $metaVerificado = isset($datosAlgoritmo['Verificado']) && $datosAlgoritmo['Verificado'] == 1;
        $metaPostAut = isset($datosAlgoritmo['postAut']) && $datosAlgoritmo['postAut'] == 1;

        // Ajustar puntos según las metas
        if ($metaVerificado && !$metaPostAut) {
            $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1.9;
        } elseif (!$metaVerificado && $metaPostAut) {
            $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * 0.1;
        } else {
            $puntosFinal = $puntosUsuario + $puntosIntereses + $puntosLikes;
        }

        // Introducir aleatoriedad controlada en los puntos finales
        $aleatoriedad = mt_rand(10, 50); 
        $puntosFinal = $puntosFinal * $factorTiempo;
        $puntosFinal = $puntosFinal * (1 + ($aleatoriedad / 100));

        // Asignar los puntos finales al post
        $posts_personalizados[$post_id] = $puntosFinal;
        $resumenPuntos[] = $post_id . ':' . round($puntosFinal, 2);
    }

    // Mezclar los posts ligeramente para introducir aleatoriedad
    uasort($posts_personalizados, function($a, $b) {
        $random_factor = mt_rand(-10, 10) / 100; // Variación entre -10% y +10%
        $a_adjusted = $a * (1 + $random_factor);
        $b_adjusted = $b * (1 + $random_factor);
        return $b_adjusted <=> $a_adjusted;
    });

    logAlgoritmo("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($posts_personalizados));
    logAlgoritmo("Resumen de puntos - " . implode(', ', $resumenPuntos));

    return $posts_personalizados;
}