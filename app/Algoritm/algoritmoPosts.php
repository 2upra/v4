<?


global $wpdb;
define('INTERES_TABLE', "{$wpdb->prefix}interes");
define('BATCH_SIZE', 1000);



function calcularFeedPersonalizado($userId, $identifier = '', $similar_to = null)
{
    $datos = obtenerDatosFeedConCache($userId);
    if (empty($datos)) {
        return [];
    }

    $usuario = get_userdata($userId);
    $esAdmin = in_array('administrator', (array) $usuario->roles);
    $vistas_posts_processed = obtenerYProcesarVistasPosts($userId);

    $posts_personalizados = [];
    $resumenPuntos = [];

    foreach ($datos['author_results'] as $post_id => $post_data) {
        $puntosFinal = calcularPuntosPost(
            $post_id,
            $post_data,
            $datos,
            $esAdmin,
            $vistas_posts_processed,
            $identifier,
            $similar_to
        );

        $posts_personalizados[$post_id] = $puntosFinal;
        $resumenPuntos[$post_id] = round($puntosFinal, 2);
    }

    arsort($posts_personalizados);
    arsort($resumenPuntos);

    logResumenDePuntos($userId, $resumenPuntos);

    return $posts_personalizados;
}



function generarMetaDeIntereses($user_id)
{
    $cache_key = 'meta_intereses_' . $user_id;
    $cached_result = get_transient($cache_key);
    if ($cached_result !== false) {
        return $cached_result;
    }

    global $wpdb;
    $likePost = obtenerLikesDelUsuario($user_id, 500);
    if (empty($likePost)) {
        return false;
    }

    $interesesActuales = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $user_id
    ), OBJECT_K);

    $placeholders = implode(', ', array_fill(0, count($likePost), '%d'));

    $query = "
        SELECT p.ID, p.post_content, pm.meta_value
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
        WHERE p.ID IN ($placeholders)
    ";
    $sql = $wpdb->prepare($query, $likePost);
    $post_data = $wpdb->get_results($sql);

    if (empty($post_data)) {
        return false;
    }

    $tag_intensidad = [];

    foreach ($post_data as $post) {
        $datosAlgoritmo = !empty($post->meta_value) ? json_decode($post->meta_value, true) : [];

        foreach ($datosAlgoritmo as $key => $value) {
            if (is_array($value)) {
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
                $value = normalizarTexto($value);
                $tag_intensidad[$value] = isset($tag_intensidad[$value]) ? $tag_intensidad[$value] + 1 : 1;
            }
        }

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

    arsort($tag_intensidad);
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
        'posts_per_page' => 10000,
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

    $sql_datos = "
        SELECT post_id, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'datosAlgoritmo' AND post_id IN ($placeholders)
    ";
    $datosAlgoritmo_results = $wpdb->get_results($wpdb->prepare($sql_datos, $posts_ids), OBJECT_K);

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
    $datosAlgoritmo = (isset($datos['datosAlgoritmo'][$post_id]) && isset($datos['datosAlgoritmo'][$post_id]->meta_value))
        ? json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true)
        : [];
    $oneshot = ['one shot', 'one-shot', 'oneshot'];
    $esOneShot = false;

    $metaValue = isset($datos['datosAlgoritmo'][$post_id]->meta_value) ? $datos['datosAlgoritmo'][$post_id]->meta_value : '';

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
        $puntosIntereses *= 0.2;
    }

    return $puntosIntereses;
}


function calcularPuntosPost($post_id, $post_data, $datos, $esAdmin, $vistas_posts_processed, $identifier = '', $similar_to = null)
{
    $autor_id = $post_data->post_author;
    $post_date = $post_data->post_date;

    $puntosUsuario = in_array($autor_id, $datos['siguiendo']) ? 20 : 0;
    $puntosIntereses = calcularPuntosIntereses($post_id, $datos);

    // Calculate points based on identifier matches
    $puntosIdentifier = 0;
    if (!empty($identifier)) {
        $puntosIdentifier = calcularPuntosIdentifier($post_id, $identifier, $datos);
    }

    // Calculate points based on similarity to the specified post
    $puntosSimilarTo = 0;
    if (!empty($similar_to)) {
        $puntosSimilarTo = calcularPuntosSimilarTo($post_id, $similar_to, $datos);
    }

    $likes = isset($datos['likes_by_post'][$post_id]) ? $datos['likes_by_post'][$post_id] : 0;
    $puntosLikes = 30 + $likes;

    $diasDesdePublicacion = (current_time('timestamp') - strtotime($post_date)) / (3600 * 24);
    $factorTiempo = pow(0.99, $diasDesdePublicacion);

    $metaVerificado = (isset($datos['verificado_results'][$post_id]->meta_value) && $datos['verificado_results'][$post_id]->meta_value == '1') ? true : false;
    $metaPostAut = (isset($datos['postAut_results'][$post_id]->meta_value) && $datos['postAut_results'][$post_id]->meta_value == '1') ? true : false;

    $puntosFinal = calcularPuntosFinales(
        $puntosUsuario,
        $puntosIntereses + $puntosIdentifier + $puntosSimilarTo,
        $puntosLikes,
        $metaVerificado,
        $metaPostAut,
        $esAdmin
    );

    if (isset($vistas_posts_processed[$post_id])) {
        $vistas = $vistas_posts_processed[$post_id]['count'];
        $reduccion_por_vista = 0.50;
        $factorReduccion = pow(1 - $reduccion_por_vista, $vistas);
        $puntosFinal *= $factorReduccion;
    }

    $aleatoriedad = mt_rand(0, 20);
    $puntosFinal = $puntosFinal * (1 + ($aleatoriedad / 100));
    $ajusteExtra = mt_rand(-50, 50);
    $puntosFinal = $puntosFinal * $factorTiempo;
    $puntosFinal += $ajusteExtra;

    return max($puntosFinal, 0);
}


function calcularPuntosIdentifier($post_id, $identifier, $datos)
{
    // Get datosAlgoritmo for the post
    $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$post_id]->meta_value)
        ? json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true)
        : [];

    // Normalize identifier(s)
    if (is_array($identifier)) {
        $identifiers = $identifier;
    } else {
        $identifiers = preg_split('/\s+/', strtolower($identifier), -1, PREG_SPLIT_NO_EMPTY);
    }

    $count = 0;

    // Now go through datosAlgoritmo and count matches
    foreach ($datosAlgoritmo as $key => $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        $item_normalized = strtolower($item);
                        foreach ($identifiers as $id_word) {
                            if ($item_normalized == $id_word) {
                                $count += 1;
                            }
                        }
                    }
                }
            }
        } elseif (!empty($value)) {
            $value_normalized = strtolower($value);
            foreach ($identifiers as $id_word) {
                if ($value_normalized == $id_word) {
                    $count += 1;
                }
            }
        }
    }

    // Assign points based on occurrences
    $puntosIdentifier = $count * 50; // For example, 50 points per occurrence

    return $puntosIdentifier;
}

function calcularPuntosSimilarTo($post_id, $similar_to, $datos)
{
    // Get datosAlgoritmo for the current post
    $datosAlgoritmo_1 = !empty($datos['datosAlgoritmo'][$post_id]->meta_value)
        ? json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true)
        : [];

    // Get datosAlgoritmo for the post to compare with
    if (isset($datos['datosAlgoritmo'][$similar_to])) {
        $datosAlgoritmo_2 = json_decode($datos['datosAlgoritmo'][$similar_to]->meta_value, true);
    } else {
        // Fetch datosAlgoritmo for 'similar_to' post if not already in $datos
        $datosAlgoritmo_meta = get_post_meta($similar_to, 'datosAlgoritmo', true);
        $datosAlgoritmo_2 = !empty($datosAlgoritmo_meta) ? json_decode($datosAlgoritmo_meta, true) : [];
    }

    // Convert datosAlgoritmo to flat arrays of words
    $words_in_post_1 = extractWordsFromDatosAlgoritmo($datosAlgoritmo_1);
    $words_in_post_2 = extractWordsFromDatosAlgoritmo($datosAlgoritmo_2);

    if (empty($words_in_post_1) || empty($words_in_post_2)) {
        return 0;
    }

    // Compute similarity as the Jaccard index
    $set1 = array_unique($words_in_post_1);
    $set2 = array_unique($words_in_post_2);

    $intersection = array_intersect($set1, $set2);
    $union = array_unique(array_merge($set1, $set2));

    $similarity = count($intersection) / count($union);

    // Assign points based on similarity
    $puntosSimilarTo = $similarity * 100; // Scale to 0-100 points

    return $puntosSimilarTo;
}

function extractWordsFromDatosAlgoritmo($datosAlgoritmo)
{
    $words = [];

    foreach ($datosAlgoritmo as $key => $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        $item_normalized = strtolower($item);
                        $words[] = $item_normalized;
                    }
                }
            }
        } elseif (!empty($value)) {
            $value_normalized = strtolower($value);
            $words[] = $value_normalized;
        }
    }
    return $words;
}


#PASO 5
function calcularPuntosFinales($puntosUsuario, $puntosIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin)
{
    if ($esAdmin) {

        if (!$metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 10;
        } elseif ($metaVerificado && !$metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 0.1;
        }
    } else {
        if ($metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 2;
        } elseif (!$metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 0.1;
        }
    }

    return $puntosUsuario + $puntosIntereses + $puntosLikes;
}
