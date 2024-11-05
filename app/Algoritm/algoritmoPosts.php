<?php

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

    // Dividir los likes en lotes para evitar cargas pesadas
    $tag_intensidad = [];
    $likePostChunks = array_chunk($likePost, 100);

    foreach ($likePostChunks as $likePostChunk) {
        // Preparar placeholders para la consulta IN
        $placeholders = implode(', ', array_fill(0, count($likePostChunk), '%d'));

        // Obtener los datos de los posts que el usuario ha dado like
        $query = "
            SELECT p.ID, p.post_content, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
            WHERE p.ID IN ($placeholders)
        ";
        $sql = $wpdb->prepare($query, $likePostChunk);
        $post_data = $wpdb->get_results($sql);

        if (empty($post_data)) {
            continue;
        }

        foreach ($post_data as $post) {
            $datosAlgoritmo = !empty($post->meta_value) ? json_decode($post->meta_value, true) : [];

            // Procesar todos los campos de datosAlgoritmo
            foreach ($datosAlgoritmo as $key => $value) {
                if (is_array($value)) {
                    // Verificar si hay versiones en español e inglés
                    foreach (['es', 'en'] as $lang) {
                        if (isset($value[$lang]) && is_array($value[$lang])) {
                            foreach ($value[$lang] as $item) {
                                $item = normalizarTexto($item);
                                if (!empty($item)) {
                                    $tag_intensidad[$item] = isset($tag_intensidad[$item]) ? $tag_intensidad[$item] + 1 : 1;
                                }
                            }
                        }
                    }
                } elseif (!empty($value)) {
                    // Si el valor es un string o un número, simplemente lo agregamos como un interés
                    $value = normalizarTexto($value);
                    if (!empty($value)) {
                        $tag_intensidad[$value] = isset($tag_intensidad[$value]) ? $tag_intensidad[$value] + 1 : 1;
                    }
                }
            }

            // Procesar palabras del contenido del post
            if (!empty($post->post_content)) {
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
        }
    }

    arsort($tag_intensidad); // Ordenar por intensidad de mayor a menor
    $tag_intensidad = array_slice($tag_intensidad, 0, 200, true);

    $result = actualizarIntereses($user_id, $tag_intensidad, $interesesActuales);

    set_transient($cache_key, $result, HOUR_IN_SECONDS);

    return $result;
}

function actualizarIntereses($user_id, $tag_intensidad, $interesesActuales)
{
    global $wpdb;

    try {
        $wpdb->query('START TRANSACTION');

        // Preparar batch para inserción/actualización
        $batch_values = [];
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

        // Obtener intereses que ya no aplican
        $intereses_nuevos = array_keys($tag_intensidad);
        $intereses_a_eliminar = array_diff(array_keys($interesesActuales), $intereses_nuevos);

        if (!empty($intereses_a_eliminar)) {
            // Preparar placeholders
            $placeholders = implode(', ', array_fill(0, count($intereses_a_eliminar), '%s'));
            $sql = $wpdb->prepare(
                "DELETE FROM " . INTERES_TABLE . " WHERE user_id = %d AND interest IN ($placeholders)",
                array_merge([$user_id], $intereses_a_eliminar)
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

function obtenerDatosFeedConCache($userId)
{
    $cache_key = 'feed_datos_' . $userId;
    $datos = wp_cache_get($cache_key);
    if ($datos === false) {
        $datos = obtenerDatosFeed($userId);
        wp_cache_set($cache_key, $datos, '', 3600); // Cache por 1 hora
    }
    return $datos;
}

function obtenerDatosFeed($userId)
{
    global $wpdb;
    $table_likes = "{$wpdb->prefix}post_likes";
    $table_intereses = INTERES_TABLE;

    // Usuarios que el usuario actual está siguiendo
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);

    // Obtener intereses del usuario
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);

    // Usar la generación de intereses cacheada
    generarMetaDeIntereses($userId);

    // Definir la fecha límite para los posts (últimos 100 días)
    $date_after = date('Y-m-d', strtotime('-100 days'));

    // Obtener IDs de posts publicados después de la fecha límite
    $posts_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = %s AND post_status = 'publish' AND post_date >= %s",
        'social_post', $date_after
    ));

    if (empty($posts_ids)) {
        return [];
    }

    // Dividir los IDs de posts en lotes
    $posts_ids_chunks = array_chunk($posts_ids, 500);
    $likes_by_post = [];
    $datosAlgoritmo_results = [];
    $verificado_results = [];
    $postAut_results = [];
    $author_results = [];

    foreach ($posts_ids_chunks as $chunk) {
        // Preparar placeholders
        $placeholders = implode(', ', array_fill(0, count($chunk), '%d'));

        // Obtener likes de los posts
        $sql_likes = "
            SELECT post_id, COUNT(*) as likes_count
            FROM $table_likes
            WHERE post_id IN ($placeholders)
            GROUP BY post_id
        ";
        $likes_results = $wpdb->get_results($wpdb->prepare($sql_likes, $chunk));
        foreach ($likes_results as $like_row) {
            $likes_by_post[$like_row->post_id] = $like_row->likes_count;
        }

        // Obtener datos de 'datosAlgoritmo' de los posts
        $sql_datos = "
            SELECT post_id, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'datosAlgoritmo' AND post_id IN ($placeholders)
        ";
        $datos_chunk = $wpdb->get_results($wpdb->prepare($sql_datos, $chunk));
        foreach ($datos_chunk as $row) {
            $datosAlgoritmo_results[$row->post_id] = $row;
        }

        // Obtener 'Verificado' meta de los posts
        $sql_verificado = "
            SELECT post_id, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'Verificado' AND post_id IN ($placeholders)
        ";
        $verificado_chunk = $wpdb->get_results($wpdb->prepare($sql_verificado, $chunk));
        foreach ($verificado_chunk as $row) {
            $verificado_results[$row->post_id] = $row;
        }

        // Obtener 'postAut' meta de los posts
        $sql_postAut = "
            SELECT post_id, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'postAut' AND post_id IN ($placeholders)
        ";
        $postAut_chunk = $wpdb->get_results($wpdb->prepare($sql_postAut, $chunk));
        foreach ($postAut_chunk as $row) {
            $postAut_results[$row->post_id] = $row;
        }

        // Obtener autores y fechas de los posts
        $sql_authors = "
            SELECT ID, post_author, post_date
            FROM {$wpdb->posts}
            WHERE ID IN ($placeholders)
        ";
        $author_chunk = $wpdb->get_results($wpdb->prepare($sql_authors, $chunk));
        foreach ($author_chunk as $row) {
            $author_results[$row->ID] = $row;
        }
    }

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

function calcularFeedPersonalizado($userId)
{
    $datos = obtenerDatosFeedConCache($userId);
    if (empty($datos) || empty($datos['author_results'])) {
        return [];
    }

    $usuario = get_userdata($userId);
    $esAdmin = in_array('administrator', (array) $usuario->roles);

    $posts_personalizados = [];

    foreach ($datos['author_results'] as $post_id => $post_data) {
        $puntosFinal = calcularPuntosPost(
            $post_id,
            $post_data,
            $datos,
            $esAdmin
        );

        if ($puntosFinal > 0) {
            $posts_personalizados[$post_id] = $puntosFinal;
        }
    }

    arsort($posts_personalizados);

    return $posts_personalizados;
}

function calcularPuntosPost($post_id, $post_data, $datos, $esAdmin)
{
    $autor_id = $post_data->post_author;
    $post_date = $post_data->post_date;

    $puntosUsuario = in_array($autor_id, $datos['siguiendo']) ? 20 : 0;
    $puntosIntereses = calcularPuntosIntereses($post_id, $datos);

    $likes = isset($datos['likes_by_post'][$post_id]) ? $datos['likes_by_post'][$post_id] : 0;
    $puntosLikes = 30 + $likes;

    $diasDesdePublicacion = (current_time('timestamp') - strtotime($post_date)) / (3600 * 24);
    $factorTiempo = pow(0.99, $diasDesdePublicacion);

    $metaVerificado = isset($datos['verificado_results'][$post_id]->meta_value) && $datos['verificado_results'][$post_id]->meta_value == '1';
    $metaPostAut = isset($datos['postAut_results'][$post_id]->meta_value) && $datos['postAut_results'][$post_id]->meta_value == '1';

    $puntosFinal = calcularPuntosFinales($puntosUsuario, $puntosIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin);

    // Añadir factor de aleatoriedad y ajuste extra
    $aleatoriedad = mt_rand(0, 20);
    $puntosFinal = $puntosFinal * (1 + ($aleatoriedad / 100));
    $ajusteExtra = mt_rand(-50, 50);
    $puntosFinal = $puntosFinal * $factorTiempo;
    $puntosFinal += $ajusteExtra;

    return max($puntosFinal, 0);
}

function calcularPuntosIntereses($post_id, $datos)
{
    $puntosIntereses = 0;
    $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$post_id]->meta_value) ? json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true) : [];
    $oneshot = ['one shot', 'one-shot', 'oneshot'];
    $esOneShot = false;

    foreach ($oneshot as $palabra) {
        if (stripos($datos['datosAlgoritmo'][$post_id]->meta_value, $palabra) !== false) {
            $esOneShot = true;
            break;
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