<?


global $wpdb;
define('INTERES_TABLE', "{$wpdb->prefix}interes");
define('BATCH_SIZE', 1000);

function calcularFeedPersonalizado($userId, $identifier = '', $similar_to = null)
{
    // Validar que el userId sea válido
    if (empty($userId) || !is_numeric($userId)) {
        error_log("Usuario ID inválido Feed: " . print_r($userId, true));
        return [];
    }

    $datos = obtenerDatosFeedConCache($userId);
    if (empty($datos)) {
        error_log("Datos vacíos para usuario ID: " . $userId);
        return [];
    }

    // Obtener y validar datos del usuario
    $usuario = get_userdata($userId);
    if (!$usuario || !is_object($usuario)) {
        error_log("No se pudo obtener datos del usuario ID: " . $userId);
        return [];
    }

    // Validar roles del usuario
    $esAdmin = false;
    if (isset($usuario->roles) && is_array($usuario->roles)) {
        $esAdmin = in_array('administrator', $usuario->roles);
    } else {
        $roles = array_map('strtolower', (array) $usuario->roles);
        $esAdmin = in_array('administrator', $roles);
    }

    // Obtener y validar vistas de posts
    $vistas_posts_processed = obtenerYProcesarVistasPosts($userId);
    if ($vistas_posts_processed === false) {
        error_log("Error al procesar vistas de posts para usuario ID: " . $userId);
        return [];
    }

    $posts_personalizados = [];
    $resumenPuntos = [];

    // Validar que author_results sea un array
    if (!isset($datos['author_results']) || !is_array($datos['author_results'])) {
        error_log("author_results no es válido para usuario ID: " . $userId);
        return [];
    }
    $current_timestamp = current_time('timestamp');

    foreach ($datos['author_results'] as $post_id => $post_data) {
        try {
            $puntosFinal = calcularPuntosPost(
                $post_id,
                $post_data,
                $datos,
                $esAdmin,
                $vistas_posts_processed,
                $identifier,
                $similar_to, 
                $current_timestamp,
                $userId
            );

            if (is_numeric($puntosFinal)) {
                $posts_personalizados[$post_id] = $puntosFinal;
                $resumenPuntos[$post_id] = round($puntosFinal, 2);
            }
        } catch (Exception $e) {
            error_log("Error al calcular puntos para post ID {$post_id}: " . $e->getMessage());
            continue;
        }
    }

    if (empty($posts_personalizados)) {
        error_log("No se generaron posts personalizados para usuario ID: " . $userId);
        return [];
    }

    arsort($posts_personalizados);
    arsort($resumenPuntos);

    return $posts_personalizados;
}

function obtenerDatosFeedConCache($userId)
{
    return obtenerDatosFeed($userId);
    if (current_user_can('administrator')) {
        return obtenerDatosFeed($userId);
    }
    $cache_key = 'feed_datos_' . $userId;
    $datos = wp_cache_get($cache_key);
    if (false === $datos) {
        $datos = obtenerDatosFeed($userId);
        wp_cache_set($cache_key, $datos, '', 800);
    }
    if (!isset($datos['author_results']) || !is_array($datos['author_results'])) {
        return [];
    }

    return $datos;
    
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
        // Convertir array de IDs a string de placeholders
        $placeholders = implode(',', array_map(function() { return '%d'; }, $likePost));
        
        $query = "
            SELECT p.ID, p.post_content, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
            WHERE p.ID IN ($placeholders)
        ";
        
        // Preparar la consulta con los valores
        $sql = $wpdb->prepare($query, $likePost);
        $post_data = $wpdb->get_results($sql);

        if (empty($post_data)) {
            error_log("No se encontraron datos de posts para los likes del usuario: " . $user_id);
            return false;
        }

        $tag_intensidad = [];

        foreach ($post_data as $post) {
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
                                            $tag_intensidad[$item] = ($tag_intensidad[$item] ?? 0) + 1;
                                        }
                                    }
                                }
                            }
                        } elseif (is_string($value) && !empty($value)) {
                            $value = normalizarTexto($value);
                            $tag_intensidad[$value] = ($tag_intensidad[$value] ?? 0) + 1;
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
                        $tag_intensidad[$palabra] = ($tag_intensidad[$palabra] ?? 0) + 1;
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


function obtenerDatosFeed($userId)
{
    global $wpdb;
    $table_likes = "{$wpdb->prefix}post_likes";
    $table_intereses = INTERES_TABLE;
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
    generarMetaDeIntereses($userId);
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);

    $vistas_posts = get_user_meta($userId, 'vistas_posts', true);
    $args = [
        'post_type'      => 'social_post',
        'posts_per_page' => 20000,
        'date_query'     => [
            'after' => date('Y-m-d', strtotime('-100 days'))
        ],
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ];
    $posts_ids = get_posts($args);

    if (empty($posts_ids)) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($posts_ids), '%d'));
    
    // Consulta para obtener likes
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

    // Consulta para obtener datosAlgoritmo
    $sql_datos = "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'datosAlgoritmo' AND post_id IN ($placeholders)
    ";
    $datosAlgoritmo_results = $wpdb->get_results($wpdb->prepare($sql_datos, $posts_ids), OBJECT_K);

    // Consulta para obtener verificado
    $sql_verificado = "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'Verificado' AND post_id IN ($placeholders)
    ";
    $verificado_results = $wpdb->get_results($wpdb->prepare($sql_verificado, $posts_ids), OBJECT_K);

    // Consulta para obtener postAut
    $sql_postAut = "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'postAut' AND post_id IN ($placeholders)
    ";
    $postAut_results = $wpdb->get_results($wpdb->prepare($sql_postAut, $posts_ids), OBJECT_K);

    // Consulta modificada para obtener autor, fecha y contenido
    $sql_posts = "
        SELECT ID, post_author, post_date, post_content
        FROM {$wpdb->posts}
        WHERE ID IN ($placeholders)
    ";
    $posts_results = $wpdb->get_results($wpdb->prepare($sql_posts, $posts_ids), OBJECT_K);

    // Preparar array de contenido de posts
    $post_content = [];
    foreach ($posts_results as $post) {
        $post_content[$post->ID] = $post->post_content;
    }

    return [
        'siguiendo'             => $siguiendo,
        'interesesUsuario'      => $interesesUsuario,
        'posts_ids'             => $posts_ids,
        'likes_by_post'         => $likes_by_post,
        'datosAlgoritmo'        => $datosAlgoritmo_results,
        'verificado_results'    => $verificado_results,
        'postAut_results'       => $postAut_results,
        'author_results'        => $posts_results,
        'post_content'          => $post_content,    
    ];
}

function obtenerYProcesarVistasPosts($userId)
{
    $vistas_posts = obtenerVistasPosts($userId);
    $vistas_posts_processed = [];

    if (!empty($vistas_posts)) {
        foreach ($vistas_posts as $post_id => $view_data) {
            $vistas_posts_processed[$post_id] = [
                'count'     => $view_data['count'],
                'last_view' => date('Y-m-d H:i:s', $view_data['last_view']),
            ];
        }
    }

    return $vistas_posts_processed;
}


function calcularPuntosIntereses($post_id, $datos)
{
    $puntosIntereses = 0;
    
    // Verificar si existen los índices necesarios
    if (!isset($datos['datosAlgoritmo'][$post_id]) || 
        !isset($datos['datosAlgoritmo'][$post_id]->meta_value)) {
        return $puntosIntereses;
    }

    $datosAlgoritmo = json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true);
    
    // Verificar si el json_decode fue exitoso
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosAlgoritmo)) {
        return $puntosIntereses;
    }

    $oneshot = ['one shot', 'one-shot', 'oneshot'];
    $esOneShot = false;
    $metaValue = $datos['datosAlgoritmo'][$post_id]->meta_value;

    if (!empty($metaValue)) {
        foreach ($oneshot as $palabra) {
            if (stripos($metaValue, $palabra) !== false) {
                $esOneShot = true;
                break;
            }
        }
    }

    foreach ($datosAlgoritmo as $key => $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        if (isset($datos['interesesUsuario'][$item])) {
                            $puntosIntereses += 10 + $datos['interesesUsuario'][$item]->intensity;
                        }
                    }
                }
            }
        } elseif (!empty($value) && isset($datos['interesesUsuario'][$value])) {
            $puntosIntereses += 10 + $datos['interesesUsuario'][$value]->intensity;
        }
    }
    
    if ($esOneShot) {
        $puntosIntereses *= 1;
    }
    
    return $puntosIntereses;
}


function calcularPuntosPost(
    $post_id, 
    $post_data, 
    $datos, 
    $esAdmin, 
    $vistas_posts_processed, 
    $identifier = '', 
    $similar_to = null,
    $current_timestamp = null,
    $user_id = null
) {

    if ($current_timestamp === null) {
        $current_timestamp = current_time('timestamp');
    }

    $autor_id = $post_data->post_author;
    $post_date = $post_data->post_date;

    // If $post_date is not already a timestamp, convert it once
    if (is_string($post_date)) {
        $post_timestamp = strtotime($post_date);
    } else {
        $post_timestamp = $post_date; // Assume it's already a timestamp
    }

    // Calculate days since publication
    $diasDesdePublicacion = ($current_timestamp - $post_timestamp) / (3600 * 24);
    $diasDesdePublicacion = (int) floor($diasDesdePublicacion);
    $factorTiempo = getDecayFactor($diasDesdePublicacion); // Using the precomputed decay factor

    // Calculate puntosUsuario
    $puntosUsuario = in_array($autor_id, $datos['siguiendo']) ? 20 : 0;

    // Calculate puntosIntereses
    $puntosIntereses = calcularPuntosIntereses($post_id, $datos);

    // Calculate puntosIdentifier
    $puntosIdentifier = 0;
    if (!empty($identifier) && isset($datos['post_content']) && isset($datos['datosAlgoritmo'])) {
        $puntosIdentifier = calcularPuntosIdentifier($post_id, $identifier, $datos);
    }
    $pesoIdentifier = 1.0; 
    $puntosIdentifier *= $pesoIdentifier;

    // Calculate puntosSimilarTo
    $puntosSimilarTo = 0;
    if (!empty($similar_to)) {
        $puntosSimilarTo = calcularPuntosSimilarTo($post_id, $similar_to, $datos);
    }

    // Calculate puntosLikes
    $likes = isset($datos['likes_by_post'][$post_id]) ? $datos['likes_by_post'][$post_id] : 0;
    $puntosLikes = 30 + $likes;

    // Access meta data efficiently
    $verificado_result = isset($datos['verificado_results'][$post_id]->meta_value) 
        ? $datos['verificado_results'][$post_id]->meta_value 
        : null;
    $metaVerificado = ($verificado_result === '1');

    $postAut_result = isset($datos['postAut_results'][$post_id]->meta_value) 
        ? $datos['postAut_results'][$post_id]->meta_value 
        : null;
    $metaPostAut = ($postAut_result === '1');

    // Calculate puntosFinal
    $puntosFinal = calcularPuntosFinales(
        $puntosUsuario,
        $puntosIntereses + $puntosSimilarTo,
        $puntosLikes,
        $metaVerificado,
        $metaPostAut,
        $esAdmin
    );

    $puntosFinal += $puntosIdentifier;

    // Apply reduction based on views
    if (isset($vistas_posts_processed[$post_id])) {
        $vistas = $vistas_posts_processed[$post_id]['count'];
        $reduccion_por_vista = 0.01;
        $factorReduccion = pow(1 - $reduccion_por_vista, $vistas);
        $puntosFinal *= $factorReduccion;
    }

    // Apply randomness and time factor
    $aleatoriedad = mt_rand(0, 20);
    $ajusteExtra = mt_rand(-50, 50);
    $puntosFinal = ($puntosFinal * (1 + ($aleatoriedad / 100))) * $factorTiempo;
    $puntosFinal += $ajusteExtra;

    return max($puntosFinal, 0);
}

// Helper function for decay factors
function getDecayFactor($days) {
    static $decay_factors = [];

    // Populate the decay factors up to 365 days
    if (empty($decay_factors)) {
        for ($d = 0; $d <= 365; $d++) {
            $decay_factors[$d] = pow(0.99, $d);
        }
    }

    // Cap days to 365 to prevent undefined index
    $days = min(max(0, (int) $days), 365);

    return $decay_factors[$days];
}



function calcularPuntosIdentifier($post_id, $identifier, $datos) {
    $resumen = [
        'post_id' => $post_id,
        'identifiers' => [],
        'matches' => [
            'content' => 0,
            'data' => 0
        ],
        'puntos' => [
            'contenido' => 0,
            'datos' => 0,
            'bonus' => 0,
            'total' => 0
        ]
    ];

    // Normalizar identificadores
    if (is_array($identifier)) {
        $identifiers = array_unique(array_map('strtolower', $identifier));
    } else {
        $identifiers = array_unique(preg_split('/\s+/', strtolower($identifier), -1, PREG_SPLIT_NO_EMPTY));
    }
    $resumen['identifiers'] = $identifiers;
    $totalIdentifiers = count($identifiers);

    if ($totalIdentifiers === 0) {
        return 0;
    }

    // Obtener contenido y datos
    $post_content = !empty($datos['post_content'][$post_id]) 
        ? strtolower($datos['post_content'][$post_id]) 
        : '';
    
    $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$post_id]->meta_value)
        ? json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true)
        : [];

    // Inicializar arrays para tracking de coincidencias
    $contentMatches = [];
    $dataMatches = [];

    // Calcular coincidencias en contenido
    foreach ($identifiers as $id_word) {
        if (strpos($post_content, $id_word) !== false) {
            $resumen['matches']['content']++;
            $contentMatches[] = $id_word;
        } else {
            // Comparación difusa si no hay coincidencia exacta
            foreach (explode(" ", $post_content) as $word) {
                similar_text($id_word, $word, $percent);
                if ($percent > 75) { // Umbral de similitud
                    $resumen['matches']['content']++;
                    $contentMatches[] = $id_word;
                    break;
                }
            }
        }
    }

    // Procesar datosAlgoritmo
    $postWords = [];
    foreach ($datosAlgoritmo as $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        $postWords[strtolower($item)] = true;
                    }
                }
            }
        } elseif (!empty($value)) {
            $postWords[strtolower($value)] = true;
        }
    }

    // Calcular coincidencias en datos
    foreach ($identifiers as $id_word) {
        if (isset($postWords[$id_word])) {
            $resumen['matches']['data']++;
            $dataMatches[] = $id_word;
        } else {
            // Comparación difusa en caso de no coincidencia exacta
            foreach (array_keys($postWords) as $word) {
                similar_text($id_word, $word, $percent);
                if ($percent > 75) { // Umbral de similitud
                    $resumen['matches']['data']++;
                    $dataMatches[] = $id_word;
                    break;
                }
            }
        }
    }

    // Calcular puntos
    $puntosBasePorCoincidenciaContenido = 1000;
    $puntosBasePorCoincidenciaDatos = 250;
    $bonusCompleto = 2000;

    $resumen['puntos']['contenido'] = $resumen['matches']['content'] * $puntosBasePorCoincidenciaContenido;
    $resumen['puntos']['datos'] = $resumen['matches']['data'] * $puntosBasePorCoincidenciaDatos;

    // Aplicar bonus
    if ($resumen['matches']['content'] === $totalIdentifiers) {
        $resumen['puntos']['bonus'] = $bonusCompleto;
    } elseif ($resumen['matches']['data'] === $totalIdentifiers) {
        $resumen['puntos']['bonus'] = $bonusCompleto * 0.5;
    }

    $resumen['puntos']['total'] = $resumen['puntos']['contenido'] + 
                                 $resumen['puntos']['datos'] + 
                                 $resumen['puntos']['bonus'];

    return $resumen['puntos']['total'];
}

function calcularPuntosSimilarTo($post_id, $similar_to, $datos)
{
    // Obtener y normalizar contenido del post
    $contenido_post_1 = isset($datos['post_content'][$post_id]) ? strtolower($datos['post_content'][$post_id]) : '';
    $contenido_post_2 = isset($datos['post_content'][$similar_to]) ? strtolower($datos['post_content'][$similar_to]) : '';

    // Extraer palabras clave de datosAlgoritmo para cada post
    $datosAlgoritmo_1 = !empty($datos['datosAlgoritmo'][$post_id]->meta_value) 
        ? json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true) : [];
    $datosAlgoritmo_2 = isset($datos['datosAlgoritmo'][$similar_to]) 
        ? json_decode($datos['datosAlgoritmo'][$similar_to]->meta_value, true) 
        : json_decode(get_post_meta($similar_to, 'datosAlgoritmo', true), true) ?? [];

    $words_in_post_1 = array_merge(
        extractWordsFromDatosAlgoritmo($datosAlgoritmo_1),
        extractWordsFromContent($contenido_post_1)
    );
    $words_in_post_2 = array_merge(
        extractWordsFromDatosAlgoritmo($datosAlgoritmo_2),
        extractWordsFromContent($contenido_post_2)
    );

    if (empty($words_in_post_1) || empty($words_in_post_2)) {
        return 0;
    }

    // Convertir arrays en conjuntos únicos
    $set1 = array_unique($words_in_post_1);
    $set2 = array_unique($words_in_post_2);

    // Calcular índice de Jaccard
    $intersection = array_intersect($set1, $set2);
    $union = array_unique(array_merge($set1, $set2));

    // Asignar pesos diferentes a las coincidencias en el contenido principal
    $contentWeight = 1.5; // Peso adicional para coincidencias en el contenido
    $contenidoMatches = count(array_intersect(extractWordsFromContent($contenido_post_1), extractWordsFromContent($contenido_post_2)));
    $similarity = (count($intersection) + $contenidoMatches * $contentWeight) / count($union);

    // Escalar puntos con un factor personalizado
    $puntosSimilarTo = $similarity * 150; // Escala para obtener hasta 150 puntos

    return $puntosSimilarTo;
}

// Función para extraer palabras de datosAlgoritmo
function extractWordsFromDatosAlgoritmo($datosAlgoritmo) {
    $words = [];
    foreach ($datosAlgoritmo as $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        $words[] = strtolower($item);
                    }
                }
            }
        } elseif (!empty($value)) {
            $words[] = strtolower($value);
        }
    }
    return $words;
}

// Función para extraer palabras de contenido (usando stemming para mejorar coincidencias)
function extractWordsFromContent($content) {
    $words = preg_split('/\s+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);
    $stemmedWords = array_map('stemWord', $words); // Aplicar stemming a cada palabra
    return $stemmedWords;
}

// Función de stemming básica (puedes usar una librería específica de stemming para mejores resultados)
function stemWord($word) {
    // Ejemplo básico de stemming: eliminar sufijos comunes (puedes mejorar esta función)
    return preg_replace('/(s|ed|ing)$/', '', $word); // Simplificación de ejemplo
}


#PASO 5
function calcularPuntosFinales($puntosUsuario, $puntosIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin)
{
    
    if ($esAdmin) {

        if (!$metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 10;
        } elseif ($metaVerificado && !$metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1;
        }
    } else {
        if ($metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 2;
        } elseif (!$metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1;
        }
    }
    
    return $puntosUsuario + $puntosIntereses + $puntosLikes;
}
