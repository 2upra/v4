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
                        $item = normalizarTexto($item);
                        $tag_intensidad[$item] = isset($tag_intensidad[$item]) ? $tag_intensidad[$item] + 1 : 1;
                    }
                }
                if (isset($value['en']) && is_array($value['en'])) {
                    foreach ($value['en'] as $item) {
                        $item = normalizarTexto($item);
                        $tag_intensidad[$item] = isset($tag_intensidad[$item]) ? $tag_intensidad[$item] + 1 : 1;
                    }
                }
            } elseif (!empty($value)) {
                // Si el valor es un string o un número, simplemente lo agregamos como un interés
                $value = normalizarTexto($value);
                $tag_intensidad[$value] = isset($tag_intensidad[$value]) ? $tag_intensidad[$value] + 1 : 1;
            }
        }

        // Procesar palabras del contenido del post
        $content = wp_strip_all_tags($post->post_content);
        $content = normalizarTexto($content);
        $palabras = preg_split('/\s+/', $content);

        foreach ($palabras as $palabra) {
            $palabra = trim($palabra);
            if (!empty($palabra)) {
                $tag_intensidad[$palabra] = isset($tag_intensidad[$palabra]) ? $tag_intensidad[$palabra] + 1 : 1;
            }
        }
    }

    // Limitar a los 100 intereses más intensos
    arsort($tag_intensidad); // Ordenar por intensidad de mayor a menor
    $tag_intensidad = array_slice($tag_intensidad, 0, 100, true); // Quedarse con los 100 primeros

    return actualizarIntereses($user_id, $tag_intensidad, $interesesActuales);
}

function actualizarIntereses($user_id, $tag_intensidad, $interesesActuales)
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');

    try {
        $batch_values = [];
        $intereses_nuevos = array_keys($tag_intensidad);

        // Preparar batch para inserción/actualización
        foreach ($tag_intensidad as $interest => $intensity) {
            $batch_values[] = $wpdb->prepare('(%d, %s, %d)', $user_id, $interest, $intensity);
        }

        if (!empty($batch_values)) {
            // Insertar o actualizar intereses en lote
            $values = implode(', ', $batch_values);
            $sql = "
                INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity)
                VALUES $values
                ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)
            ";
            $wpdb->query($sql);
        }

        // Eliminar intereses que ya no aplican
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
        logAlgoritmo("Intereses actualizados exitosamente para el usuario: $user_id");
        return true;
    } catch (Exception $e) {
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
    
        // Introducir aleatoriedad controlada en los puntos finales (aleatoriedad incrementada)
        $aleatoriedad = mt_rand(0, 20);  // Aumentar el rango de aleatoriedad del 0-10 a 0-20
        $puntosFinal = $puntosFinal * $factorTiempo;
        $puntosFinal = $puntosFinal * (1 + ($aleatoriedad / 100)); // Hasta 20% de variación
    
        // Introducir una segunda capa de aleatoriedad con una pequeña suma o resta aleatoria
        $ajusteExtra = mt_rand(-5, 5);  // Variación entre -5 y +5 puntos
        $puntosFinal += $ajusteExtra;
    
        // Asignar los puntos finales al post
        $posts_personalizados[$post_id] = $puntosFinal;
        $resumenPuntos[] = $post_id . ':' . round($puntosFinal, 2);
    }
    
    // Mezclar los posts ligeramente para introducir aleatoriedad
    uasort($posts_personalizados, function($a, $b) {
        $random_factor_a = mt_rand(-10, 10) / 100; // Variación entre -10% y +10%
        $random_factor_b = mt_rand(-10, 10) / 100; // Variación entre -10% y +10%
        
        // Aplicar aleatoriedad diferente para cada post
        $a_adjusted = $a * (1 + $random_factor_a);
        $b_adjusted = $b * (1 + $random_factor_b);
        
        return $b_adjusted <=> $a_adjusted;
    });

    logAlgoritmo("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($posts_personalizados));
    logAlgoritmo("Resumen de puntos - " . implode(', ', $resumenPuntos));

    return $posts_personalizados;
}

// Función para normalizar el texto (evitar problemas de acentos y caracteres especiales)
function normalizarTexto($texto)
{
    // Convertir a minúsculas y eliminar acentos
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = preg_replace('/[áàäâã]/u', 'a', $texto);
    $texto = preg_replace('/[éèëê]/u', 'e', $texto);
    $texto = preg_replace('/[íìïî]/u', 'i', $texto);
    $texto = preg_replace('/[óòöôõ]/u', 'o', $texto);
    $texto = preg_replace('/[úùüû]/u', 'u', $texto);
    $texto = preg_replace('/[ñ]/u', 'n', $texto);

    // Eliminar cualquier carácter no alfanumérico
    $texto = preg_replace('/[^a-z0-9\s]+/u', '', $texto);

    return $texto;
}