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
        //logAlgoritmo("No se encontraron datos para los posts con likes del usuario: $user_id");
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
        //logAlgoritmo("Intereses actualizados exitosamente para el usuario: $user_id");
        return true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Error al actualizar intereses: ' . $e->getMessage());
        //logAlgoritmo("Error al actualizar intereses: " . $e->getMessage());
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
    //logAlgoritmo("Intereses del usuario generados para el usuario ID: $userId");

    // Obtener intereses del usuario
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);
    //logAlgoritmo("Intereses del usuario obtenidos: " . json_encode($interesesUsuario));

    // Obtener la meta 'vistas_posts' del usuario
    $vistas_posts = get_user_meta($userId, 'vistas_posts', true);
    //logAlgoritmo("Meta 'vistas_posts' obtenida: " . json_encode($vistas_posts));

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
    //logAlgoritmo("Consulta de posts realizada, total de posts: " . count($posts_ids));

    if (empty($posts_ids)) {
        //logAlgoritmo("No se encontraron posts para el feed del usuario ID: $userId");
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

function obtenerDatosFeedConCache($userId)
{
    return obtenerDatosFeed($userId);
    // Verifica si el usuario es administrador
    if (current_user_can('administrator')) {
        // Si el usuario es admin, devuelve los datos directamente sin caché
        return obtenerDatosFeed($userId);
    }
    /*
    $cache_key = 'feed_datos_' . $userId;
    $datos = wp_cache_get($cache_key);

    if (false === $datos) {
        $datos = obtenerDatosFeed($userId);
        wp_cache_set($cache_key, $datos, '', 800);
    }

    if (!isset($datos['author_results']) || !is_array($datos['author_results'])) {
        //logAlgoritmo("Error: 'author_results' is not set or not an array for user ID: $userId");
        return [];
    }

    return $datos;
    */
}


# PASO 1
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
        //logAlgoritmo("Meta 'vistas_posts' procesada: " . json_encode($vistas_posts_processed));
    } else {
        //logAlgoritmo("Meta 'vistas_posts' está vacía para el usuario ID: $userId");
    }

    return $vistas_posts_processed;
}

#PASO 3
function calcularPuntosIntereses($post_id, $datos)
{
    $puntosIntereses = 0;
    $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$post_id]->meta_value) ? json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true) : [];

    // Verificar si el contenido tiene alguna palabra clave que requiera reducción de puntos
    $oneshot = ['one shot', 'one-shot', 'oneshot'];
    $esOneShot = false;

    foreach ($oneshot as $palabra) {
        if (stripos($datos['datosAlgoritmo'][$post_id]->meta_value, $palabra) !== false) {
            $esOneShot = true;
            break;
        }
    }

    // Calcular puntos de intereses
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

    // Aplicar la reducción del 90% si contiene una de las palabras clave
    if ($esOneShot) {
        $puntosIntereses *= 0;
    }

    return $puntosIntereses;
}


#PASO 2
function calcularPuntosPost($post_id, $post_data, $datos, $esAdmin, $vistas_posts_processed)
{
    $autor_id = $post_data->post_author;
    $post_date = $post_data->post_date;

    // Puntos por seguir al autor
    $puntosUsuario = in_array($autor_id, $datos['siguiendo']) ? 20 : 0;

    // Puntos por intereses
    $puntosIntereses = calcularPuntosIntereses($post_id, $datos);

    // Puntos por likes
    $likes = isset($datos['likes_by_post'][$post_id]) ? $datos['likes_by_post'][$post_id] : 0;
    $puntosLikes = 5 + $likes;

    // Decaimiento por tiempo
    $diasDesdePublicacion = (current_time('timestamp') - strtotime($post_date)) / (3600 * 24);
    $factorTiempo = pow(0.99, $diasDesdePublicacion);    

    // Obtener 'Verificado' y 'postAut'
    $metaVerificado = isset($datos['verificado_results'][$post_id]->meta_value) && $datos['verificado_results'][$post_id]->meta_value == '1';
    $metaPostAut = isset($datos['postAut_results'][$post_id]->meta_value) && $datos['postAut_results'][$post_id]->meta_value == '1';

    // Calcular puntos finales
    $puntosFinal = calcularPuntosFinales($puntosUsuario, $puntosIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin);

    // Aplicar reducción por vistas si corresponde
    if (isset($vistas_posts_processed[$post_id])) {
        $vistas = $vistas_posts_processed[$post_id]['count'];
        $reduccion_por_vista = 0.60; // Reducción del 40% por cada vista
        //logAlgoritmo("Aplicando reducción por vistas: $reduccion_por_vista para el post ID: $post_id, vistas: $vistas");

        // Factor de reducción acumulado por vistas
        $factorReduccion = pow(1 - $reduccion_por_vista, $vistas);
        $puntosFinalAntesReduccion = $puntosFinal; // Guardar puntos antes de la reducción

        // Aplicar la reducción
        $puntosFinal *= $factorReduccion;

        // Calcular el porcentaje de reducción total
        $reduccionTotal = (1 - $factorReduccion) * 100;
        //logAlgoritmo("Post ID: $post_id - Puntos antes de la reducción: $puntosFinalAntesReduccion, Puntos después de la reducción: $puntosFinal, Reducción total: " . round($reduccionTotal, 2) . "%");
    }

    // Aplicar aleatoriedad y ajuste extra
    $aleatoriedad = mt_rand(0, 60);
    $puntosFinal = $puntosFinal * (1 + ($aleatoriedad / 100));
    $ajusteExtra = mt_rand(-100, 100);
    $puntosFinal = $puntosFinal * $factorTiempo;
    $puntosFinal += $ajusteExtra;

    // Asegurar que los puntos finales no sean negativos
    return max($puntosFinal, 0);
}


#PASO 5
function calcularPuntosFinales($puntosUsuario, $puntosIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin)
{
    if ($esAdmin) {

        if (!$metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1.9;
        } elseif ($metaVerificado && !$metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 0.1;
        }
    } else {
        if ($metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 3;
        } elseif (!$metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 0.1;
        }
    }

    return $puntosUsuario + $puntosIntereses + $puntosLikes;
}

# CALCULO START
function calcularFeedPersonalizado($userId)
{
    // Obtener datos del feed con caché
    $datos = obtenerDatosFeedConCache($userId);
    if (empty($datos)) {
        return [];
    }

    // Verificar si el usuario es administrador
    $usuario = get_userdata($userId);
    $esAdmin = in_array('administrator', (array) $usuario->roles);

    // Obtener y procesar las vistas de los posts
    $vistas_posts_processed = obtenerYProcesarVistasPosts($userId);

    // Inicializar arrays para los posts personalizados y el resumen de puntos
    $posts_personalizados = [];
    $resumenPuntos = [];

    // Calcular los puntos para cada post y aplicar la lógica de personalización
    foreach ($datos['author_results'] as $post_id => $post_data) {
        $puntosFinal = calcularPuntosPost(
            $post_id,
            $post_data,
            $datos,
            $esAdmin,
            $vistas_posts_processed
        );

        // Guardar los puntos finales en los arrays correspondientes
        $posts_personalizados[$post_id] = $puntosFinal;
        $resumenPuntos[$post_id] = round($puntosFinal, 2);
    }

    // Ordenar los posts de mayor a menor puntos
    arsort($posts_personalizados);
    arsort($resumenPuntos);

    // Loguear el resumen de puntos
    // logResumenDePuntos($userId, $resumenPuntos);

    return $posts_personalizados;
}
