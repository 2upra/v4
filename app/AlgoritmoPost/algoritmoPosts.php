<?


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

//en datos ahora viene el valor nombreOriginal, lo puedes incluir aca para que lo tenga en cuenta, viene dentro de datos
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
