<?

global $wpdb;
define('INTERES_TABLE', "{$wpdb->prefix}interes");
define('BATCH_SIZE', 1000);


function generarMetaDeIntereses($user_id)
{
    $cache_key = 'meta_intereses_' . $user_id;
    $cached_result = get_transient($cache_key);
    if ($cached_result !== false) {
        return $cached_result;
    }

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

    arsort($tag_intensidad); // Ordenar por intensidad de mayor a menor
    $tag_intensidad = array_slice($tag_intensidad, 0, 200, true);

    return actualizarIntereses($user_id, $tag_intensidad, $interesesActuales);

    set_transient($cache_key, $result, 1 * HOUR_IN_SECONDS);

    return $result;
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

function obtenerDatosFeed($userId) {
    global $wpdb;
    
    // Constantes y variables iniciales
    $prefix = $wpdb->prefix;
    $cache_key = "feed_data_user_{$userId}";
    $cache_time = 300; // 5 minutos
    
    // Intentar obtener datos de caché
    if ($cached_data = wp_cache_get($cache_key)) {
        return $cached_data;
    }

    // Preparar datos básicos
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
    generarMetaDeIntereses($userId);

    // Consulta combinada para intereses
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $userId
    ), OBJECT_K);

    // Obtener IDs de posts recientes
    $posts_ids = get_posts([
        'post_type'      => 'social_post',
        'posts_per_page' => 10000,
        'date_query'     => ['after' => date('Y-m-d', strtotime('-100 days'))],
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'cache_results'  => true
    ]);

    if (empty($posts_ids)) {
        return [];
    }

    // Preparar consulta en lote
    $placeholders = implode(',', array_fill(0, count($posts_ids), '%d'));
    
    // Consultas combinadas para metadata
    $queries = [
        // Likes
        "SELECT post_id, COUNT(*) as likes_count 
         FROM {$prefix}post_likes 
         WHERE post_id IN ($placeholders) 
         GROUP BY post_id",
         
        // Posts y autores
        "SELECT ID, post_author, post_date, 
         (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'datosAlgoritmo' LIMIT 1) as datos_algoritmo,
         (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'Verificado' LIMIT 1) as verificado,
         (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'postAut' LIMIT 1) as post_aut
         FROM {$wpdb->posts} p 
         WHERE ID IN ($placeholders)"
    ];

    // Ejecutar consultas
    $results = [];
    foreach ($queries as $query) {
        $results[] = $wpdb->get_results($wpdb->prepare($query, ...$posts_ids));
    }

    // Procesar resultados
    $likes_by_post = [];
    foreach ($results[0] as $row) {
        $likes_by_post[$row->post_id] = $row->likes_count;
    }

    $post_data = [];
    foreach ($results[1] as $row) {
        $post_data[$row->ID] = [
            'author'          => $row->post_author,
            'date'           => $row->post_date,
            'datosAlgoritmo' => $row->datos_algoritmo,
            'verificado'     => $row->verificado,
            'postAut'        => $row->post_aut
        ];
    }

    $data = [
        'siguiendo'        => $siguiendo,
        'interesesUsuario' => $interesesUsuario,
        'posts_ids'        => $posts_ids,
        'likes_by_post'    => $likes_by_post,
        'post_data'        => $post_data
    ];

    // Guardar en caché
    wp_cache_set($cache_key, $data, '', $cache_time);

    return $data;
}


function calcularFeedPersonalizado($userId) {
    // Cache check
    $cache_key = "feed_personalizado_{$userId}";
    if ($cached_result = wp_cache_get($cache_key)) {
        return $cached_result;
    }

    // Obtener datos necesarios
    $datos = obtenerDatosFeed($userId);
    if (empty($datos) || empty($datos['author_results'])) {
        return [];
    }

    // Preparar datos de usuario
    $esAdmin = in_array('administrator', (array) get_userdata($userId)->roles);
    $current_time = current_time('timestamp');
    
    // Precomputar valores constantes
    $siguiendo_set = array_flip($datos['siguiendo']);
    $posts_personalizados = [];
    
    // Crear función de cálculo de puntos inline
    $calcularPuntosIntereses = function($datosAlgoritmo, $interesesUsuario) {
        $puntos = 0;
        if (empty($datosAlgoritmo)) return 0;
        
        foreach ($datosAlgoritmo as $value) {
            if (is_array($value)) {
                foreach (['es', 'en'] as $lang) {
                    if (isset($value[$lang]) && is_array($value[$lang])) {
                        foreach ($value[$lang] as $item) {
                            if (isset($interesesUsuario[$item])) {
                                $puntos += 1 + $interesesUsuario[$item]->intensity;
                            }
                        }
                    }
                }
            } elseif (!empty($value) && isset($interesesUsuario[$value])) {
                $puntos += 1 + $interesesUsuario[$value]->intensity;
            }
        }
        return $puntos;
    };

    // Procesar posts en batch
    foreach ($datos['author_results'] as $post_id => $post_data) {
        // Calcular puntos base
        $puntos_base = (isset($siguiendo_set[$post_data->post_author]) ? 20 : 0) +
                      $calcularPuntosIntereses(
                          !empty($datos['datosAlgoritmo'][$post_id]->meta_value) ? 
                          json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true) : [],
                          $datos['interesesUsuario']
                      ) +
                      (5 + ($datos['likes_by_post'][$post_id] ?? 0));

        // Calcular factor tiempo
        $factor_tiempo = pow(0.99, ($current_time - strtotime($post_data->post_date)) / 3600);

        // Verificar metadata
        $meta_verificado = !empty($datos['verificado_results'][$post_id]->meta_value);
        $meta_post_aut = !empty($datos['postAut_results'][$post_id]->meta_value);

        // Aplicar multiplicadores
        $multiplicador = 1;
        if ($esAdmin) {
            if (!$meta_verificado && $meta_post_aut) $multiplicador = 1.9;
            elseif ($meta_verificado && !$meta_post_aut) $multiplicador = 0.1;
        } else {
            if ($meta_verificado && !$meta_post_aut) $multiplicador = 1.9;
            elseif (!$meta_verificado && $meta_post_aut) $multiplicador = 0.1;
        }

        // Calcular puntos finales
        $puntos_finales = max(
            0,
            ($puntos_base * $multiplicador * $factor_tiempo * (1 + (mt_rand(0, 60) / 100))) + 
            mt_rand(-100, 100)
        );

        $posts_personalizados[$post_id] = $puntos_finales;
    }

    // Ordenar resultados
    arsort($posts_personalizados);

    // Cachear resultados
    wp_cache_set($cache_key, $posts_personalizados, '', 300);

    // Log resumido
    logAlgoritmo(sprintf(
        "Feed calculado - Usuario: %d, Posts: %d, Rango puntos: %.2f-%.2f",
        $userId,
        count($posts_personalizados),
        min($posts_personalizados),
        max($posts_personalizados)
    ));

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
