<?php
// Contiene funciones relacionadas con el cálculo de puntuaciones para posts (ej: basado en intereses).

// Refactor(Org): Funcion movida desde app/AlgoritmoPost/algoritmoPosts.php
function calcularPuntosIntereses($postId, $datos)
{
    $pIntereses = 0;

    // Verificar si existen los indices necesarios, si es un objeto y tiene la propiedad meta_value
    if (
        !isset($datos['datosAlgoritmo'][$postId]) ||
        !is_object($datos['datosAlgoritmo'][$postId]) || // Anadido is_object()
        !isset($datos['datosAlgoritmo'][$postId]->meta_value)
    ) {
        // Si alguna comprobacion falla, retornar los puntos actuales (0)
        return $pIntereses;
    }

    // Ahora es seguro acceder a meta_value
    $metaValue = $datos['datosAlgoritmo'][$postId]->meta_value;
    $datosAlgoritmo = json_decode($metaValue, true);

    // Verificar si el json_decode fue exitoso
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosAlgoritmo)) {
        // Si la decodificacion falla o no es un array, retornar puntos actuales
        return $pIntereses;
    }

    $oneshot = ['one shot', 'one-shot', 'oneshot'];
    $esOneShot = false;
    // $metaValue ya fue asignada y usada para json_decode, la reasignacion original era redundante
    // $metaValue = $datos['datosAlgoritmo'][$postId]->meta_value; // Linea original eliminada implicitamente al usar $metaValue de arriba

    // Usar la variable $metaValue ya existente
    if (!empty($metaValue) && is_string($metaValue)) { // Asegurar que es string para stripos
        foreach ($oneshot as $palabra) {
            if (stripos($metaValue, $palabra) !== false) {
                $esOneShot = true;
                break;
            }
        }
    }

    // Iterar sobre los datos decodificados
    foreach ($datosAlgoritmo as $key => $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        if (isset($datos['interesesUsuario'][$item])) {
                            // Asumiendo que $datos['interesesUsuario'][$item] es un objeto con propiedad intensity
                            // Idealmente, anadir aqui tambien is_object() y isset()->intensity
                            $pIntereses += 10 + $datos['interesesUsuario'][$item]->intensity;
                        }
                    }
                }
            }
        } elseif (!empty($value) && isset($datos['interesesUsuario'][$value])) {
            // Asumiendo que $datos['interesesUsuario'][$value] es un objeto con propiedad intensity
            // Idealmente, anadir aqui tambien is_object() y isset()->intensity
            $pIntereses += 10 + $datos['interesesUsuario'][$value]->intensity;
        }
    }

    if ($esOneShot) {
        $pIntereses *= 1;
    }

    return $pIntereses;
}

// Refactor(Org): Funciones movidas desde app/AlgoritmoPost/algoritmoPosts.php
// en datos ahora viene el valor nombreOriginal, lo puedes incluir aca para que lo tenga en cuenta, viene dentro de datos
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
    $log = '';

    // Normalizar identificadores
    $identifiers = is_array($identifier)
        ? array_unique(array_map('strtolower', $identifier))
        : array_unique(preg_split('/\s+/', strtolower($identifier), -1, PREG_SPLIT_NO_EMPTY));
    $resumen['identifiers'] = $identifiers;
    $totalIds = count($identifiers);

    if ($totalIds === 0) {
        return 0;
    }

    // Obtener contenido y datos
    $postContent = !empty($datos['post_content'][$postId])
        ? strtolower($datos['post_content'][$postId])
        : '';

    $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$postId]->meta_value)
        ? json_decode($datos['datosAlgoritmo'][$postId]->meta_value, true)
        : [];

    $nombreOriginal = !empty($datos['nombreOriginal'][$postId])
        ? strtolower($datos['nombreOriginal'][$postId])
        : '';

    // Inicializar arrays para tracking de coincidencias
    $contentMatches = [];
    $dataMatches = [];

    // Calcular coincidencias en contenido y nombre original
    foreach ($identifiers as $id) {
        if (strpos($postContent, $id) !== false) {
            $resumen['matches']['content']++;
            $contentMatches[] = $id;
        } elseif (strpos($nombreOriginal, $id) !== false) {
            $resumen['matches']['content']++;
            $contentMatches[] = $id;
        } else {
            // Comparacion difusa si no hay coincidencia exacta
            foreach (explode(" ", $postContent) as $word) {
                similar_text($id, $word, $percent);
                if ($percent > 75) {
                    $resumen['matches']['content']++;
                    $contentMatches[] = $id;
                    break;
                }
            }
            // Comparacion difusa en el nombre original
            foreach (explode(" ", $nombreOriginal) as $word) {
                similar_text($id, $word, $percent);
                if ($percent > 75) {
                    $resumen['matches']['content']++;
                    $contentMatches[] = $id;
                    break;
                }
            }
        }
    }

    // Procesar datosAlgoritmo
    $postWords = [];
    foreach ($datosAlgoritmo as $val) {
        if (is_array($val)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($val[$lang]) && is_array($val[$lang])) {
                    foreach ($val[$lang] as $item) {
                        $postWords[strtolower($item)] = true;
                    }
                }
            }
        } elseif (!empty($val)) {
            $postWords[strtolower($val)] = true;
        }
    }

    // Calcular coincidencias en datos
    foreach ($identifiers as $id) {
        if (isset($postWords[$id])) {
            $resumen['matches']['data']++;
            $dataMatches[] = $id;
        } else {
            // Comparacion difusa en caso de no coincidencia exacta
            foreach (array_keys($postWords) as $word) {
                similar_text($id, $word, $percent);
                if ($percent > 75) {
                    $resumen['matches']['data']++;
                    $dataMatches[] = $id;
                    break;
                }
            }
        }
    }

    // Calcular puntos
    $puntosBaseContenido = 1000;
    $puntosBaseDatos = 250;
    $bonus = 2000;

    $resumen['puntos']['contenido'] = $resumen['matches']['content'] * $puntosBaseContenido;
    $resumen['puntos']['datos'] = $resumen['matches']['data'] * $puntosBaseDatos;

    // Aplicar bonus
    if ($resumen['matches']['content'] === $totalIds) {
        $resumen['puntos']['bonus'] = $bonus;
    } elseif ($resumen['matches']['data'] === $totalIds) {
        $resumen['puntos']['bonus'] = $bonus * 0.5;
    }

    $resumen['puntos']['total'] = $resumen['puntos']['contenido'] +
        $resumen['puntos']['datos'] +
        $resumen['puntos']['bonus'];

    $log .= "calcularPuntosIdentifier: \n Post ID: $postId, \n Identifiers: " . implode(", ", $identifiers) . ", \n Coincidencias en contenido: " . $resumen['matches']['content'] . ", \n Coincidencias en datos: " . $resumen['matches']['data'] . ", \n Puntos de contenido: " . $resumen['puntos']['contenido'] . ", \n Puntos de datos: " . $resumen['puntos']['datos'] . ", \n Bonus: " . $resumen['puntos']['bonus'] . ", \n Puntos totales: " . $resumen['puntos']['total'];

    //guardarLog($log);
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
    // La funcion stemWord fue movida a app/Utils/StringUtils.php pero sigue disponible globalmente
    $stemmedWords = array_map('stemWord', $words);
    return $stemmedWords;
}

// Refactor(Exec): Funcion movida desde app/AlgoritmoPost/calcularPuntos.php
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

    $likesPorPost = $datos['likes_by_post'];

    if (!is_array($likesPorPost)) {
        $likesPorPost = [];
        guardarLog("likesPorPost no era un array, se ha forzado a serlo, en calcularPuntosParaPost. postId: $postId");
    }

    $likesData = $likesPorPost[$postId] ?? ['like' => 0, 'favorito' => 0, 'no_me_gusta' => 0];
    $puntosLikes = 5 + $likesData['like'] + 10 * $likesData['favorito'] - ($likesData['no_me_gusta'] * 10);

    // Access meta data
    $meta_data = $datos['meta_data'];
    $metaVerificado = false;
    $metaPostAut = false;

    if (isset($meta_data[$postId]['Verificado'])) {
        $metaVerificado = ($meta_data[$postId]['Verificado'] === '1');
    }
    if (isset($meta_data[$postId]['postAut'])) {
        $metaPostAut = ($meta_data[$postId]['postAut'] === '1');
    }

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
        // Si el ID del post actual existe en el array $vistasPosts, se obtiene el numero de vistas.
        $v = $vistasPosts[$postId]['count'];

        // Se calcula la reduccion de puntos en funcion del numero de vistas.
        $rPuntos = $v * 10;

        // Se resta la reduccion de puntos al puntaje final.
        $pFinal -= $rPuntos;
    }

    // Adjust randomness outside tight loops if possible
    $aleatoriedad = mt_rand(0, 20);
    $ajusteExtra = mt_rand(-50, 50);
    $pFinal = ($pFinal * (1 + ($aleatoriedad / 100))) * $factorTiempo;
    $pFinal += $ajusteExtra;

    return $pFinal;
}
