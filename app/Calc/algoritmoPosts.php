<?


global $wpdb;

function calcularFeedPersonalizado($userId, $identifier = '', $similarTo = null, $tipoUsuario = null)
{
    $datos = obtenerDatosFeedConCache($userId);
    if (empty($datos)) {
        return [];
    }
    //error_log("TipoUsuario inicial={$tipoUsuario} calcularFeedPersonalizado");
    $usuario = get_userdata($userId);
    if (!$usuario || !is_object($usuario)) {
        return [];
    }
    $posts_personalizados = [];
    $current_timestamp = current_time('timestamp');
    $vistas_posts_processed = obtenerYProcesarVistasPosts($userId);
    $esAdmin = in_array('administrator', (array)$usuario->roles);
    $decay_factors = [];

    foreach ($datos['author_results'] as $post_data) {
        $post_date = $post_data->post_date;
        $post_timestamp = is_string($post_date) ? strtotime($post_date) : $post_date;
        $diasDesdePublicacion = floor(($current_timestamp - $post_timestamp) / (3600 * 24));
        if (!isset($decay_factors[$diasDesdePublicacion])) {
            $decay_factors[$diasDesdePublicacion] = getDecayFactor($diasDesdePublicacion);
        }
    }

    $posts_data = $datos['author_results'];
    $puntos_por_post = calcularPuntosPostBatch(
        $posts_data,
        $datos,
        $esAdmin,
        $vistas_posts_processed,
        $identifier,
        $similarTo,
        $current_timestamp,
        $userId,
        $decay_factors,
        $tipoUsuario
    );

    if (!empty($puntos_por_post)) {
        arsort($puntos_por_post);

        $puntos_por_post = array_slice($puntos_por_post, 0, POSTINLIMIT, true);
    }
    return $puntos_por_post;
}

function calcularPuntosPostBatch(
    $posts_data,
    $datos,
    $esAdmin,
    $vistas_posts_processed,
    $identifier = '',
    $similarTo = null,
    $current_timestamp = null,
    $user_id = null,
    $decay_factors = [],
    $tipoUsuario = null
) {
    if ($current_timestamp === null) {
        $current_timestamp = current_time('timestamp');
    }

    $posts_puntos = [];
    //error_log("TipoUsuario inicial={$tipoUsuario} calcularPuntosPostBatch");

    foreach ($posts_data as $postId => $post_data) {
        try {
            $puntosFinal = calcularPuntosParaPost(
                $postId,
                $post_data,
                $datos,
                $esAdmin,
                $vistas_posts_processed,
                $identifier,
                $similarTo,
                $current_timestamp,
                $decay_factors,
                $tipoUsuario
            );

            if (is_numeric($puntosFinal) && $puntosFinal > 0) {
                $posts_puntos[$postId] = max($puntosFinal, 0);
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $posts_puntos;
}
//aqui en esta parte dice [05-Dec-2024 02:05:36 UTC] PHP Warning:  Undefined variable $puntosArtistaFan in /var/www/wordpress/wp-content/themes/2upra3v/app/Calc/algoritmoPosts.php
function calcularPuntosParaPost(
    $postId,
    $post_data,
    $datos,
    $esAdmin,
    $vistas_posts_processed,
    $identifier,
    $similarTo,
    $current_timestamp,
    $decay_factors,
    $tipoUsuario = null
) {
    $autor_id = $post_data->post_author;
    $post_date = $post_data->post_date;

    $post_timestamp = is_string($post_date) ? strtotime($post_date) : $post_date;

    $diasDesdePublicacion = floor(($current_timestamp - $post_timestamp) / (3600 * 24));
    $factorTiempo = $decay_factors[$diasDesdePublicacion] ?? getDecayFactor($diasDesdePublicacion);

    // Calculate puntosUsuario
    $siguiendo = $datos['siguiendo'];
    $puntosUsuario = in_array($autor_id, $siguiendo) ? 20 : 0;

    // Calculate puntosIntereses
    $puntosIntereses = calcularPuntosIntereses($postId, $datos);

    // Calculate puntosIdentifier
    $puntosIdentifier = 0;
    if (!empty($identifier)) {
        $puntosIdentifier = calcularPuntosIdentifier($postId, $identifier, $datos);
    }
    $pesoIdentifier = 1.0;
    $puntosIdentifier *= $pesoIdentifier;

    // Calculate puntosSimilarTo
    $puntosSimilarTo = 0;
    if (!empty($similarTo)) {
        $puntosSimilarTo = calcularPuntosSimilarTo($postId, $similarTo, $datos);
    }

    // Calculate puntosLikes
    $likesPorPost = $datos['likes_by_post'];
    $likesData = $likesPorPost[$postId] ?? ['like' => 0, 'favorito' => 0, 'no_me_gusta' => 0];
    $puntosLikes = 30 + $likesData['like'] + 50 * $likesData['favorito'] - ($likesData['no_me_gusta'] * 30);

    // Access meta data
    $meta_data = $datos['meta_data'];
    $metaVerificado = isset($meta_data[$postId]['Verificado']) && ($meta_data[$postId]['Verificado'] === '1');
    $metaPostAut = isset($meta_data[$postId]['postAut']) && ($meta_data[$postId]['postAut'] === '1');

    $meta_roles = $datos['meta_roles'];

    if (!isset($meta_roles[$postId]) || !is_array($meta_roles[$postId])) {
        $meta_roles[$postId] = ['artista' => false, 'fan' => false];
    }

    $puntosArtistaFan = 0;

    if (empty($similarTo)) {
        $postParaArtistas = !empty($meta_roles[$postId]['artista']);
        $postParaFans = !empty($meta_roles[$postId]['fan']);

        if ($tipoUsuario === 'Fan') {
            $puntosArtistaFan = $postParaFans ? 999 : 0;
        } elseif ($tipoUsuario === 'Artista') {
            $puntosArtistaFan = $postParaFans ? -50 : 0; 
        }
    }

    // Calculate puntosFinal
    $puntosFinal = calcularPuntosFinales(
        $puntosUsuario,
        $puntosIntereses + $puntosSimilarTo + $puntosArtistaFan,
        $puntosLikes,
        $metaVerificado,
        $metaPostAut,
        $esAdmin
    );

    $puntosFinal += $puntosIdentifier;

    // Apply reduction based on views
    if (isset($vistas_posts_processed[$postId])) {
        $vistas = $vistas_posts_processed[$postId]['count'];
        $reduccion_por_vista = 0.01;
        $factorReduccion = pow(1 - $reduccion_por_vista, $vistas);
        $puntosFinal *= $factorReduccion;
    }

    // Adjust randomness outside tight loops if possible
    $aleatoriedad = mt_rand(0, 20);
    $ajusteExtra = mt_rand(-50, 50);
    $puntosFinal = ($puntosFinal * (1 + ($aleatoriedad / 100))) * $factorTiempo;
    $puntosFinal += $ajusteExtra;

    return $puntosFinal;
}

function calcularPuntosIntereses($postId, $datos)
{
    $puntosIntereses = 0;

    // Verificar si existen los índices necesarios
    if (
        !isset($datos['datosAlgoritmo'][$postId]) ||
        !isset($datos['datosAlgoritmo'][$postId]->meta_value)
    ) {
        return $puntosIntereses;
    }

    $datosAlgoritmo = json_decode($datos['datosAlgoritmo'][$postId]->meta_value, true);

    // Verificar si el json_decode fue exitoso
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosAlgoritmo)) {
        return $puntosIntereses;
    }

    $oneshot = ['one shot', 'one-shot', 'oneshot'];
    $esOneShot = false;
    $metaValue = $datos['datosAlgoritmo'][$postId]->meta_value;

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

function calcularPuntosFinales($puntosUsuario, $puntosIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin)
{

    if ($esAdmin) {

        if (!$metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1;
        } elseif ($metaVerificado && !$metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1;
        }
    } else {
        if ($metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 4;
        } elseif (!$metaVerificado && $metaPostAut) {
            return ($puntosUsuario + $puntosIntereses + $puntosLikes) * 1;
        }
    }

    return $puntosUsuario + $puntosIntereses + $puntosLikes;
}


// Helper function for decay factors

function getDecayFactor($days, $useDecay = false)
{
    static $decay_factors = [];
    static $use_decay = false;

    if (func_num_args() > 1) {
        $use_decay = $useDecay;
    }

    if (!$use_decay) {
        return 1;
    }

    if (empty($decay_factors)) {
        for ($d = 0; $d <= 365; $d++) {
            $decay_factors[$d] = pow(0.99, $d);
        }
    }

    $days = min(max(0, (int) $days), 365);

    return $decay_factors[$days];
}

function calcularPuntosIdentifier($postId, $identifier, $datos)
{
    $resumen = [
        'post_id' => $postId,
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
    $post_content = !empty($datos['post_content'][$postId])
        ? strtolower($datos['post_content'][$postId])
        : '';

    $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$postId]->meta_value)
        ? json_decode($datos['datosAlgoritmo'][$postId]->meta_value, true)
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

function calcularPuntosSimilarTo($postId, $similarTo, $datos)
{

    $contenido_post_1 = isset($datos['post_content'][$postId]) ? strtolower($datos['post_content'][$postId]) : '';
    $contenido_post_2 = isset($datos['post_content'][$similarTo]) ? strtolower($datos['post_content'][$similarTo]) : '';

    $datosAlgoritmo_1 = isset($datos['datosAlgoritmo'][$postId]->meta_value)
        ? procesarMetaValue($datos['datosAlgoritmo'][$postId]->meta_value)
        : [];

    $datosAlgoritmo_2 = isset($datos['datosAlgoritmo'][$similarTo]->meta_value)
        ? procesarMetaValue($datos['datosAlgoritmo'][$similarTo]->meta_value)
        : procesarMetaValue(get_post_meta($similarTo, 'datosAlgoritmo', true));


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
    $set1 = array_unique($words_in_post_1);
    $set2 = array_unique($words_in_post_2);
    $intersection = array_intersect($set1, $set2);
    $union = array_unique(array_merge($set1, $set2));
    $contentWeight = 1.5;
    $contenidoMatches = count(array_intersect(extractWordsFromContent($contenido_post_1), extractWordsFromContent($contenido_post_2)));
    $similarity = (count($intersection) + $contenidoMatches * $contentWeight) / count($union);
    $puntosSimilarTo = $similarity * 150;
    return $puntosSimilarTo;
}



function procesarMetaValue($meta_value)
{
    if (is_array($meta_value)) {
        return $meta_value;
    }
    if (is_string($meta_value)) {
        $decoded_value = json_decode($meta_value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded_value;
        } else {
            return [];
        }
    }
    //error_log("meta_value no es un array ni una cadena, es de tipo: " . gettype($meta_value));
    return [];
}
/*
\[
p_{\text{final}}''(p_i) = 
\Bigg[
\Big(
w_u \cdot \delta_u + 
w_I \cdot \text{calcId}(p_i, I, d) + 
w_S \cdot \text{calcSim}(p_i, S, d) + 
w_L \cdot (30 + l(p_i)) + 
w_m \cdot (\delta_v + \delta_a)
\Big) \cdot f_t(d_i) \cdot (1 - r_v)^{v(p_i)}
\Big]
\cdot (1 + \frac{r}{100}) + r_e
\]
*/

function extractWordsFromDatosAlgoritmo($datosAlgoritmo)
{
    $words = [];

    // Verificar si $datosAlgoritmo es null o no es array
    if (!is_array($datosAlgoritmo)) {
        return $words;
    }

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

function extractWordsFromContent($content)
{
    $words = preg_split('/\s+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);
    $stemmedWords = array_map('stemWord', $words);
    return $stemmedWords;
}

function stemWord($word)
{
    return preg_replace('/(s|ed|ing)$/', '', $word);
}


#PASO 5

function obtenerYProcesarVistasPosts($userId)
{
    $vistas_posts = obtenerVistasPosts($userId);
    $vistas_posts_processed = [];

    if (!empty($vistas_posts)) {
        foreach ($vistas_posts as $postId => $view_data) {
            $vistas_posts_processed[$postId] = [
                'count'     => $view_data['count'],
                'last_view' => date('Y-m-d H:i:s', $view_data['last_view']),
            ];
        }
    }

    return $vistas_posts_processed;
}
