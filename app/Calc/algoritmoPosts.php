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
    $actualTimestamp = current_time('timestamp');
    $vistasPosts = obtenerYProcesarVistasPosts($userId);
    $esAdmin = in_array('administrator', (array)$usuario->roles);
    $decaimientoF = [];

    foreach ($datos['author_results'] as $post_data) {
        $postDate = $post_data->post_date;
        $postTimestamp = is_string($postDate) ? strtotime($postDate) : $postDate;
        $diasPubli = floor(($actualTimestamp - $postTimestamp) / (3600 * 24));
        if (!isset($decaimientoF[$diasPubli])) {
            $decaimientoF[$diasPubli] = getDecayFactor($diasPubli);
        }
    }

    $posts_data = $datos['author_results'];
    $puntos_por_post = calcularPuntosPostBatch(
        $posts_data,
        $datos,
        $esAdmin,
        $vistasPosts,
        $identifier,
        $similarTo,
        $actualTimestamp,
        $userId,
        $decaimientoF,
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
    $vistasPosts,
    $identifier = '',
    $similarTo = null,
    $actualTimestamp = null,
    $user_id = null,
    $decaimientoF = [],
    $tipoUsuario = null
) {
    if ($actualTimestamp === null) {
        $actualTimestamp = current_time('timestamp');
    }

    $posts_puntos = [];
    //error_log("TipoUsuario inicial={$tipoUsuario} calcularPuntosPostBatch");

    foreach ($posts_data as $postId => $post_data) {
        try {
            $pFinal = calcularPuntosParaPost(
                $postId,
                $post_data,
                $datos,
                $esAdmin,
                $vistasPosts,
                $identifier,
                $similarTo,
                $actualTimestamp,
                $decaimientoF,
                $tipoUsuario
            );

            if (is_numeric($pFinal) && $pFinal > 0) {
                $posts_puntos[$postId] = max($pFinal, 0);
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $posts_puntos;
}

function calcularPuntosParaPost(
    $postId,
    $post_data,
    $datos,
    $esAdmin,
    $vistasPosts,
    $identifier,
    $similarTo,
    $actualTimestamp,
    $decaimientoF,
    $tipoUsuario = null
) {
    $autorId = $post_data->post_author;
    $postDate = $post_data->post_date;

    $postTimestamp = is_string($postDate) ? strtotime($postDate) : $postDate;

    $diasPubli = floor(($actualTimestamp - $postTimestamp) / (3600 * 24));
    $factorTiempo = $decaimientoF[$diasPubli] ?? getDecayFactor($diasPubli);

    // Calculate puntosUsuario
    $siguiendo = $datos['siguiendo'];
    $pUsuario = in_array($autorId, $siguiendo) ? 20 : 0;

    $pIntereses = calcularPuntosIntereses($postId, $datos);

    // Calculate puntosIdentifier
    $pIdentifier = 0;
    if (!empty($identifier)) {
        $pIdentifier = calcularPuntosIdentifier($postId, $identifier, $datos);
    }
    $pesoIdentifier = 1.0;
    $pIdentifier *= $pesoIdentifier;

    // Calculate puntosSimilarTo
    $pSimilarTo = 0;
    if (!empty($similarTo)) {
        $pSimilarTo = calcularPuntosSimilarTo($postId, $similarTo, $datos);
    }
    // Calculate puntosLikes
    $likesPorPost = $datos['likes_by_post'];
    $likesData = $likesPorPost[$postId] ?? ['like' => 0, 'favorito' => 0, 'no_me_gusta' => 0];
    $puntosLikes = 5 + $likesData['like'] + 10 * $likesData['favorito'] - ($likesData['no_me_gusta'] * 10);

    // Access meta data
    $meta_data = $datos['meta_data'];
    $metaVerificado = isset($meta_data[$postId]['Verificado']) && ($meta_data[$postId]['Verificado'] === '1');
    $metaPostAut = isset($meta_data[$postId]['postAut']) && ($meta_data[$postId]['postAut'] === '1');

    $meta_roles = $datos['meta_roles'];

    if (!isset($meta_roles[$postId]) || !is_array($meta_roles[$postId])) {
        $meta_roles[$postId] = ['artista' => false, 'fan' => false];
    }

    $pArtistaFan = 0;

    if (empty($similarTo)) {
        $postParaArtistas = !empty($meta_roles[$postId]['artista']);
        $postParaFans = !empty($meta_roles[$postId]['fan']);

        if ($tipoUsuario === 'Fan') {
            $pArtistaFan = $postParaFans ? 999 : 0;
        } elseif ($tipoUsuario === 'Artista') {
            $pArtistaFan = $postParaFans ? -50 : 0; 
        }
    }

    // Calculate puntosFinal
    $pFinal = calcularPuntosFinales(
        $pUsuario,
        $pIntereses + $pSimilarTo + $pArtistaFan,
        $puntosLikes,
        $metaVerificado,
        $metaPostAut,
        $esAdmin
    );

    $pFinal += $pIdentifier;

    // Apply reduction based on views
    if (isset($vistasPosts[$postId])) {
        $vistas = $vistasPosts[$postId]['count'];
        $rVista = 0.01;
        $factorReduccion = pow(1 - $rVista, $vistas);
        $pFinal *= $factorReduccion;
    }

    // Adjust randomness outside tight loops if possible
    $aleatoriedad = mt_rand(0, 20);
    $ajusteExtra = mt_rand(-50, 50);
    $pFinal = ($pFinal * (1 + ($aleatoriedad / 100))) * $factorTiempo;
    $pFinal += $ajusteExtra;

    return $pFinal;
}

function calcularPuntosIntereses($postId, $datos)
{
    $pIntereses = 0;

    // Verificar si existen los índices necesarios
    if (
        !isset($datos['datosAlgoritmo'][$postId]) ||
        !isset($datos['datosAlgoritmo'][$postId]->meta_value)
    ) {
        return $pIntereses;
    }

    $datosAlgoritmo = json_decode($datos['datosAlgoritmo'][$postId]->meta_value, true);

    // Verificar si el json_decode fue exitoso
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosAlgoritmo)) {
        return $pIntereses;
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
                            $pIntereses += 10 + $datos['interesesUsuario'][$item]->intensity;
                        }
                    }
                }
            }
        } elseif (!empty($value) && isset($datos['interesesUsuario'][$value])) {
            $pIntereses += 10 + $datos['interesesUsuario'][$value]->intensity;
        }
    }

    if ($esOneShot) {
        $pIntereses *= 1;
    }

    return $pIntereses;
}

function calcularPuntosFinales($pUsuario, $pIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin)
{

    if ($esAdmin) {

        if (!$metaVerificado && $metaPostAut) {
            return ($pUsuario + $pIntereses + $puntosLikes) * 1;
        } elseif ($metaVerificado && !$metaPostAut) {
            return ($pUsuario + $pIntereses + $puntosLikes) * 1;
        }
    } else {
        if ($metaVerificado && $metaPostAut) {
            return ($pUsuario + $pIntereses + $puntosLikes) * 4;
        } elseif (!$metaVerificado && $metaPostAut) {
            return ($pUsuario + $pIntereses + $puntosLikes) * 1;
        }
    }

    return $pUsuario + $pIntereses + $puntosLikes;
}


// Helper function for decay factors

function getDecayFactor($days, $useDecay = false)
{
    static $decaimientoF = [];
    static $use_decay = false;

    if (func_num_args() > 1) {
        $use_decay = $useDecay;
    }

    if (!$use_decay) {
        return 1;
    }

    if (empty($decaimientoF)) {
        for ($d = 0; $d <= 365; $d++) {
            $decaimientoF[$d] = pow(0.99, $d);
        }
    }

    $days = min(max(0, (int) $days), 365);

    return $decaimientoF[$days];
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
    $pSimilarTo = $similarity * 150;
    return $pSimilarTo;
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
    $vistasPosts = [];

    if (!empty($vistas_posts)) {
        foreach ($vistas_posts as $postId => $view_data) {
            $vistasPosts[$postId] = [
                'count'     => $view_data['count'],
                'last_view' => date('Y-m-d H:i:s', $view_data['last_view']),
            ];
        }
    }

    return $vistasPosts;
}
