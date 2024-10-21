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

/*
3 ajustes pequeños
que ya tenga tanta relevancia la novedad, es decir, que no importe tanto lo reciente que sea el post
un poco mas de aleatearidad 
y por ultimo

esto tiene que invertirse si el usuario es admin, es decir, que los usuarios admin tienen que ver los post que no estan verificados

    $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1.9;
} elseif (!$metaVerificado && $metaPostAut) {
    $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * 0.1;
*/

function calcularFeedPersonalizado($userId)
{
    $datos = obtenerDatosFeed($userId);

    if (empty($datos)) {
        return [];
    }

    // Verificar si el usuario es administrador
    $usuario = get_userdata($userId);
    $esAdmin = in_array('administrator', (array) $usuario->roles);

    $posts_personalizados = [];
    $resumenPuntos = [];

    foreach ($datos['author_results'] as $post_id => $post_data) {
        $autor_id = $post_data->post_author;
        $post_date = $post_data->post_date;
        
        // Puntos por seguir al autor
        $puntosUsuario = in_array($autor_id, $datos['siguiendo']) ? 50 : 0;

        // Puntos por intereses
        $puntosIntereses = 0;
        $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$post_id]->meta_value) ? json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true) : [];
        foreach ($datosAlgoritmo as $key => $value) {
            if (is_array($value)) {
                // Procesar versiones en español ('es') e inglés ('en')
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

        // Puntos por likes
        $likes = isset($datos['likes_by_post'][$post_id]) ? $datos['likes_by_post'][$post_id] : 0;
        $puntosLikes = 5 + $likes;

        // Decaimiento por tiempo (ajustado para reducir la importancia de la recencia)
        $horasDesdePublicacion = (current_time('timestamp') - strtotime($post_date)) / 3600;
        // Aumentar el base de decaimiento para que la recencia tenga menor impacto
        $factorTiempo = pow(0.99, $horasDesdePublicacion);

        // Obtener 'Verificado' y 'postAut' individualmente
        $metaVerificado = isset($datos['verificado_results'][$post_id]->meta_value) && $datos['verificado_results'][$post_id]->meta_value == '1';
        $metaPostAut = isset($datos['postAut_results'][$post_id]->meta_value) && $datos['postAut_results'][$post_id]->meta_value == '1';

        // Ajuste por metadatos, invertido si el usuario es admin
        if ($esAdmin) {
            if (!$metaVerificado && $metaPostAut) {
                $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1.5;
            } elseif ($metaVerificado && !$metaPostAut) {
                $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * 0.5;
            } else {
                $puntosFinal = $puntosUsuario + $puntosIntereses + $puntosLikes;
            }
        } else {
            if ($metaVerificado && !$metaPostAut) {
                $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1.5;
            } elseif (!$metaVerificado && $metaPostAut) {
                $puntosFinal = ($puntosUsuario + $puntosIntereses + $puntosLikes) * 0.5;
            } else {
                $puntosFinal = $puntosUsuario + $puntosIntereses + $puntosLikes;
            }
        }

        // Aumentar la aleatoriedad (incrementar el rango para más variación)
        $aleatoriedad = mt_rand(0, 50); // Aumentamos hasta 50% de variación
        $puntosFinal = $puntosFinal * $factorTiempo;
        $puntosFinal = $puntosFinal * (1 + ($aleatoriedad / 100)); // Hasta 50% de variación

        // Ajuste extra aleatorio (puedes ajustar el rango si deseas más variación)
        $ajusteExtra = mt_rand(-20, 20); // Variación entre -15 y +15 puntos
        $puntosFinal += $ajusteExtra;

        // Asegurar que los puntos finales no sean negativos
        $puntosFinal = max($puntosFinal, 0);

        $posts_personalizados[$post_id] = $puntosFinal;
        $resumenPuntos[$post_id] = round($puntosFinal, 2);
    }

    // Ordenar los posts de mayor a menor puntos
    arsort($posts_personalizados);

    // Ordenar el resumen de puntos de mayor a menor
    arsort($resumenPuntos);

    // Loguear la información
    logAlgoritmo("Feed personalizado calculado para el usuario ID: $userId. Total de posts: " . count($posts_personalizados));
    $resumen_formateado = [];
    foreach ($resumenPuntos as $post_id => $puntos) {
        $resumen_formateado[] = "$post_id:$puntos";
    }
    logAlgoritmo("Resumen de puntos - " . implode(', ', $resumen_formateado));

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