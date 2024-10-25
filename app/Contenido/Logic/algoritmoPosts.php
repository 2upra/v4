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

function obtenerDatosFeed($userId)
{
    global $wpdb;
    $table_likes = "{$wpdb->prefix}post_likes";
    $table_intereses = INTERES_TABLE;

    // Usuarios que el usuario actual está siguiendo
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);

    // Generar o actualizar los intereses del usuario
    generarMetaDeIntereses($userId);
    logAlgoritmo("Intereses del usuario generados para el usuario ID: $userId");

    // Obtener intereses del usuario
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);
    logAlgoritmo("Intereses del usuario obtenidos: " . json_encode($interesesUsuario));

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
    logAlgoritmo("Consulta de posts realizada, total de posts: " . count($posts_ids));

    if (empty($posts_ids)) {
        logAlgoritmo("No se encontraron posts para el feed del usuario ID: $userId");
        return [];
    }

    // Preparar placeholders para consultas en lote
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

    // Obtener datos de 'datosAlgoritmo' de los posts
    $sql_datos = "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'datosAlgoritmo' AND post_id IN ($placeholders)
    ";
    $datosAlgoritmo_results = $wpdb->get_results($wpdb->prepare($sql_datos, $posts_ids), OBJECT_K);

    // Obtener 'Verificado' y 'postAut' de los posts
    $sql_verificado = "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'Verificado' AND post_id IN ($placeholders)
    ";
    $verificado_results = $wpdb->get_results($wpdb->prepare($sql_verificado, $posts_ids), OBJECT_K);

    $sql_postAut = "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'postAut' AND post_id IN ($placeholders)
    ";
    $postAut_results = $wpdb->get_results($wpdb->prepare($sql_postAut, $posts_ids), OBJECT_K);

    // Obtener autores y fechas de los posts
    $sql_authors = "
        SELECT ID, post_author, post_date
        FROM {$wpdb->posts}
        WHERE ID IN ($placeholders)
    ";
    $author_results = $wpdb->get_results($wpdb->prepare($sql_authors, $posts_ids), OBJECT_K);

    return [
        'siguiendo'             => $siguiendo,
        'interesesUsuario'      => $interesesUsuario,
        'posts_ids'             => $posts_ids,
        'likes_by_post'         => $likes_by_post,
        'datosAlgoritmo'        => $datosAlgoritmo_results,
        'verificado_results'    => $verificado_results,
        'postAut_results'       => $postAut_results,
        'author_results'        => $author_results,
    ];
}


function calcularFeedPersonalizado($userId) {
    // Clave única para el caché
    $cache_key = 'feed_personalizado_' . $userId;
    
    // Intentar obtener resultados del caché
    $cached_results = wp_cache_get($cache_key);
    if ($cached_results !== false) {
        return $cached_results;
    }

    // Si no hay caché, calcular el feed
    try {
        $datos = obtenerDatosFeed($userId);

        if (empty($datos) || !isset($datos['author_results']) || !is_array($datos['author_results'])) {
            logAlgoritmo("Error: Datos inválidos para user ID: $userId");
            return [];
        }

        $usuario = get_userdata($userId);
        $esAdmin = in_array('administrator', (array) $usuario->roles);
        
        $posts_personalizados = calcularPuntosPosts($datos, $esAdmin);
        
        // Guardar en caché por 5 minutos
        wp_cache_set($cache_key, $posts_personalizados, '', 300);
        
        // Logging
        logResultados($userId, $posts_personalizados);
        
        return $posts_personalizados;

    } catch (Exception $e) {
        logAlgoritmo("Error en calcularFeedPersonalizado: " . $e->getMessage());
        return [];
    }
}

function calcularPuntosPosts($datos, $esAdmin) {
    $posts_personalizados = [];
    
    foreach ($datos['author_results'] as $post_id => $post_data) {
        $puntos = calcularPuntosPost(
            $post_id,
            $post_data,
            $datos,
            $esAdmin
        );
        
        if ($puntos > 0) {
            $posts_personalizados[$post_id] = $puntos;
        }
    }
    
    arsort($posts_personalizados);
    return $posts_personalizados;
}

function calcularPuntosPost($post_id, $post_data, $datos, $esAdmin) {
    // Cache key para los puntos del post específico
    $cache_key = "post_points_{$post_id}_{$post_data->post_author}";
    $cached_points = wp_cache_get($cache_key);
    
    if ($cached_points !== false) {
        return $cached_points;
    }
    
    $puntosBase = calcularPuntosBase($post_data, $datos);
    $puntosIntereses = calcularPuntosIntereses($post_id, $datos);
    $puntosLikes = 5 + (isset($datos['likes_by_post'][$post_id]) ? $datos['likes_by_post'][$post_id] : 0);
    
    $factorTiempo = calcularFactorTiempo($post_data->post_date);
    $puntosFinal = aplicarAjustes(
        $puntosBase + $puntosIntereses + $puntosLikes,
        $datos,
        $post_id,
        $esAdmin,
        $factorTiempo
    );
    
    // Cachear los puntos del post por 1 hora
    wp_cache_set($cache_key, $puntosFinal, '', 3600);
    
    return $puntosFinal;
}

function calcularPuntosBase($post_data, $datos) {
    return in_array($post_data->post_author, $datos['siguiendo']) ? 20 : 0;
}

function calcularPuntosIntereses($post_id, $datos) {
    $puntosIntereses = 0;
    $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$post_id]->meta_value) ? 
        json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true) : [];
    
    foreach ($datosAlgoritmo as $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        if (isset($datos['interesesUsuario'][$item])) {
                            $puntosIntereses += 1 + $datos['interesesUsuario'][$item]->intensity;
                        }
                    }
                }
            }
        } elseif (!empty($value) && isset($datos['interesesUsuario'][$value])) {
            $puntosIntereses += 1 + $datos['interesesUsuario'][$value]->intensity;
        }
    }
    
    return $puntosIntereses;
}

function calcularFactorTiempo($post_date) {
    $horasDesdePublicacion = (current_time('timestamp') - strtotime($post_date)) / 3600;
    return pow(0.99, $horasDesdePublicacion);
}

function aplicarAjustes($puntos, $datos, $post_id, $esAdmin, $factorTiempo) {
    $metaVerificado = isset($datos['verificado_results'][$post_id]->meta_value) && 
        $datos['verificado_results'][$post_id]->meta_value == '1';
    $metaPostAut = isset($datos['postAut_results'][$post_id]->meta_value) && 
        $datos['postAut_results'][$post_id]->meta_value == '1';
    
    // Aplicar multiplicadores según el tipo de usuario y metadata
    if ($esAdmin) {
        $puntos = aplicarMultiplicadoresAdmin($puntos, $metaVerificado, $metaPostAut);
    } else {
        $puntos = aplicarMultiplicadoresUsuario($puntos, $metaVerificado, $metaPostAut);
    }
    
    // Aplicar factores adicionales
    $aleatoriedad = mt_rand(0, 60) / 100;
    $puntos = $puntos * $factorTiempo * (1 + $aleatoriedad);
    $puntos += mt_rand(-100, 100);
    
    return max($puntos, 0);
}

function logResultados($userId, $posts_personalizados) {
    $resumen = array_map(function($puntos) {
        return round($puntos, 2);
    }, $posts_personalizados);
    
    logAlgoritmo("Feed personalizado para ID: $userId. Posts: " . count($posts_personalizados));
    logAlgoritmo("Puntos: " . implode(', ', array_map(function($id, $puntos) {
        return "$id:$puntos";
    }, array_keys($resumen), $resumen)));
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