<?php

/**
 * Wrappers deprecados para el módulo de algoritmo.
 * 
 * DEPRECADO: Usar las clases en src/Services/ directamente.
 * 
 * @deprecated 1.0.0 Usar Kamples\Services\AlgoritmoService y Kamples\Services\InteresService
 * @see \Kamples\Services\AlgoritmoService
 * @see \Kamples\Services\InteresService
 */

use Kamples\Services\AlgoritmoService;
use Kamples\Services\InteresService;

/**
 * Calcula el feed personalizado para un usuario.
 * 
 * @deprecated Usar AlgoritmoService::calcularFeedPersonalizado()
 * @param int $userId ID del usuario
 * @param string $identifier Identificador
 * @param int|null $similarTo Post de referencia
 * @param string|null $tipoUsuario Tipo de usuario
 * @return array Puntuaciones
 */
function calcularFeedPersonalizado($userId, $identifier = '', $similarTo = null, $tipoUsuario = null)
{
    $servicio = AlgoritmoService::obtenerInstancia();
    return $servicio->calcularFeedPersonalizado(
        (int)$userId,
        (string)$identifier,
        $similarTo !== null ? (int)$similarTo : null,
        $tipoUsuario
    );
}

/**
 * Obtiene datos de un usuario.
 * 
 * @deprecated Función interna del algoritmo
 * @param int $userId ID del usuario
 * @return \WP_User|array
 */
function obtenerUsuario($userId)
{
    $usuario = get_userdata($userId);
    if (!$usuario || !is_object($usuario)) {
        return [];
    }
    return $usuario;
}

/**
 * Obtiene las vistas de un usuario.
 * 
 * @deprecated Función interna del algoritmo
 * @param int $userId ID del usuario
 * @return array
 */
function obtenerVistas($userId)
{
    return obtenerYProcesarVistasPosts($userId);
}

/**
 * Verifica si un usuario es administrador.
 * 
 * @deprecated Función interna del algoritmo
 * @param \WP_User $usuario Usuario
 * @return bool
 */
function esUsuarioAdmin($usuario)
{
    return in_array('administrator', (array)$usuario->roles);
}

/**
 * Calcula el decaimiento temporal.
 * 
 * @deprecated Función interna del algoritmo
 * @param array $datos Datos del feed
 * @return array
 */
function calcularDecaimiento($datos)
{
    $actual = current_time('timestamp');
    $decaimiento = [];

    if (!isset($datos['author_results'])) {
        return $decaimiento;
    }

    foreach ($datos['author_results'] as $post) {
        $fecha = is_string($post->post_date) ? strtotime($post->post_date) : $post->post_date;
        $dias = floor(($actual - $fecha) / (3600 * 24));
        if (!isset($decaimiento[$dias])) {
            $decaimiento[$dias] = getDecayFactor($dias);
        }
    }
    return $decaimiento;
}

/**
 * Calcula puntos para los posts.
 * 
 * @deprecated Función interna del algoritmo
 */
function calcularPuntos($datos, $esAdmin, $vistas, $identifier, $similarTo, $userId, $decaimiento, $tipoUsuario)
{
    return calcularPuntosPostBatch(
        $datos['author_results'] ?? [],
        $datos,
        $esAdmin,
        $vistas,
        $identifier,
        $similarTo,
        null,
        $userId,
        $decaimiento,
        $tipoUsuario
    );
}

/**
 * Ordena y limita los puntos.
 * 
 * @deprecated Función interna del algoritmo
 * @param array $puntos Puntuaciones
 * @return array
 */
function ordenarYLimitarPuntos($puntos)
{
    if (!empty($puntos)) {
        arsort($puntos);
        $limite = defined('POSTINLIMIT') ? POSTINLIMIT : 1000;
        $puntos = array_slice($puntos, 0, $limite, true);
    }
    return $puntos;
}

/**
 * Calcula puntos para un lote de posts.
 * 
 * @deprecated Función interna del algoritmo
 */
function calcularPuntosPostBatch(
    $posts,
    $datos,
    $esAdmin,
    $vistas,
    $identifier = '',
    $similarTo = null,
    $actual = null,
    $usu = null,
    $decaimiento = [],
    $tipoUsuario = null
) {
    if ($actual === null) {
        $actual = current_time('timestamp');
    }

    $puntos = [];
    foreach ($posts as $id => $post) {
        try {
            $pFinal = calcularPuntosParaPost(
                $id,
                $post,
                $datos,
                $esAdmin,
                $vistas,
                $identifier,
                $similarTo,
                $actual,
                $decaimiento,
                $tipoUsuario
            );

            if (is_numeric($pFinal) && $pFinal > 0) {
                $puntos[$id] = max($pFinal, 0);
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $puntos;
}

/**
 * Calcula puntos para un post individual.
 * 
 * @deprecated Función interna del algoritmo
 */
function calcularPuntosParaPost(
    $postId,
    $postData,
    $datos,
    $esAdmin,
    $vistasPosts,
    $identifier,
    $similarTo,
    $actualTimestamp,
    $decaimientoF,
    $tipoUsuario = null
) {
    $autorId = $postData->post_author;
    $postDate = $postData->post_date;

    $postTimestamp = is_string($postDate) ? strtotime($postDate) : $postDate;
    $diasPubli = floor(($actualTimestamp - $postTimestamp) / (3600 * 24));
    $factorTiempo = $decaimientoF[$diasPubli] ?? getDecayFactor($diasPubli);

    $siguiendo = $datos['siguiendo'] ?? [];
    $pUsuario = in_array($autorId, $siguiendo) ? 20 : 0;

    $pIntereses = calcularPuntosIntereses($postId, $datos);

    $pIdentifier = 0;
    if (!empty($identifier)) {
        $pIdentifier = calcularPuntosIdentifier($postId, $identifier, $datos);
    }

    $pSimilarTo = 0;
    if (!empty($similarTo)) {
        $pSimilarTo = calcularPuntosSimilarTo($postId, $similarTo, $datos);
    }

    $likesPorPost = $datos['likes_by_post'] ?? [];
    if (!is_array($likesPorPost)) {
        $likesPorPost = [];
    }

    $likesData = $likesPorPost[$postId] ?? ['like' => 0, 'favorito' => 0, 'no_me_gusta' => 0];
    $puntosLikes = 5 + $likesData['like'] + 10 * $likesData['favorito'] - ($likesData['no_me_gusta'] * 10);

    $metaData = $datos['meta_data'] ?? [];
    $metaVerificado = isset($metaData[$postId]['Verificado']) && ($metaData[$postId]['Verificado'] === '1');
    $metaPostAut = isset($metaData[$postId]['postAut']) && ($metaData[$postId]['postAut'] === '1');

    $metaRoles = $datos['meta_roles'] ?? [];
    if (!isset($metaRoles[$postId]) || !is_array($metaRoles[$postId])) {
        $metaRoles[$postId] = ['artista' => false, 'fan' => false];
    }

    $pArtistaFan = 0;
    if (empty($similarTo)) {
        $postParaFans = !empty($metaRoles[$postId]['fan']);

        if ($tipoUsuario === 'Fan') {
            $pArtistaFan = $postParaFans ? 999 : 0;
        } elseif ($tipoUsuario === 'Artista') {
            $pArtistaFan = $postParaFans ? -50 : 0;
        }
    }

    $pFinal = calcularPuntosFinales(
        $pUsuario,
        $pIntereses + $pSimilarTo + $pArtistaFan,
        $puntosLikes,
        $metaVerificado,
        $metaPostAut,
        $esAdmin
    );

    $pFinal += $pIdentifier;

    if (isset($vistasPosts[$postId])) {
        $v = $vistasPosts[$postId]['count'];
        $rPuntos = $v * 10;
        $pFinal -= $rPuntos;
    }

    $aleatoriedad = mt_rand(0, 20);
    $ajusteExtra = mt_rand(-50, 50);
    $pFinal = ($pFinal * (1 + ($aleatoriedad / 100))) * $factorTiempo;
    $pFinal += $ajusteExtra;

    return $pFinal;
}

/**
 * Calcula puntos por intereses.
 * 
 * @deprecated Función interna del algoritmo
 */
function calcularPuntosIntereses($postId, $datos)
{
    $pIntereses = 0;

    if (
        !isset($datos['datosAlgoritmo'][$postId]) ||
        !isset($datos['datosAlgoritmo'][$postId]->meta_value)
    ) {
        return $pIntereses;
    }

    $datosAlgoritmo = json_decode($datos['datosAlgoritmo'][$postId]->meta_value, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosAlgoritmo)) {
        return $pIntereses;
    }

    $interesesUsuario = $datos['interesesUsuario'] ?? [];

    foreach ($datosAlgoritmo as $key => $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        if (isset($interesesUsuario[$item])) {
                            $pIntereses += 10 + $interesesUsuario[$item]->intensity;
                        }
                    }
                }
            }
        } elseif (!empty($value) && isset($interesesUsuario[$value])) {
            $pIntereses += 10 + $interesesUsuario[$value]->intensity;
        }
    }

    return $pIntereses;
}

/**
 * Calcula puntos finales combinados.
 * 
 * @deprecated Función interna del algoritmo
 */
function calcularPuntosFinales($pUsuario, $pIntereses, $puntosLikes, $metaVerificado, $metaPostAut, $esAdmin)
{
    $base = $pUsuario + $pIntereses + $puntosLikes;

    if ($esAdmin) {
        if (!$metaVerificado && $metaPostAut) {
            return $base * 1;
        } elseif ($metaVerificado && !$metaPostAut) {
            return $base * 1;
        }
    } else {
        if ($metaVerificado && $metaPostAut) {
            return $base * 4;
        } elseif (!$metaVerificado && $metaPostAut) {
            return $base * 1;
        }
    }

    return $base;
}

/**
 * Obtiene el factor de decaimiento temporal.
 * 
 * @deprecated Usar AlgoritmoService::getDecayFactor()
 * @param int $days Días desde publicación
 * @param bool $useDecay Si usar decaimiento
 * @return float
 */
function getDecayFactor($days, $useDecay = false)
{
    static $decaimiento = [];
    static $useDecayStatic = false;

    if (func_num_args() > 1) {
        $useDecayStatic = $useDecay;
    }

    if (!$useDecayStatic) {
        return 1;
    }

    if (empty($decaimiento)) {
        for ($d = 0; $d <= 365; $d++) {
            $decaimiento[$d] = pow(0.99, $d);
        }
    }

    $days = min(max(0, (int)$days), 365);
    return $decaimiento[$days];
}

/**
 * Calcula puntos por identificador de búsqueda.
 * 
 * @deprecated Función interna del algoritmo
 */
function calcularPuntosIdentifier($postId, $identifier, $datos)
{
    $resumen = [
        'matches' => ['content' => 0, 'data' => 0],
        'puntos' => ['contenido' => 0, 'datos' => 0, 'bonus' => 0, 'total' => 0]
    ];

    $identifiers = is_array($identifier)
        ? array_unique(array_map('strtolower', $identifier))
        : array_unique(preg_split('/\s+/', strtolower($identifier), -1, PREG_SPLIT_NO_EMPTY));

    $totalIds = count($identifiers);
    if ($totalIds === 0) {
        return 0;
    }

    $postContent = !empty($datos['post_content'][$postId])
        ? strtolower($datos['post_content'][$postId])
        : '';

    $datosAlgoritmo = !empty($datos['datosAlgoritmo'][$postId]->meta_value)
        ? json_decode($datos['datosAlgoritmo'][$postId]->meta_value, true)
        : [];

    $nombreOriginal = !empty($datos['nombreOriginal'][$postId])
        ? strtolower($datos['nombreOriginal'][$postId])
        : '';

    foreach ($identifiers as $id) {
        if (strpos($postContent, $id) !== false || strpos($nombreOriginal, $id) !== false) {
            $resumen['matches']['content']++;
        } else {
            foreach (array_merge(explode(" ", $postContent), explode(" ", $nombreOriginal)) as $word) {
                similar_text($id, $word, $percent);
                if ($percent > 75) {
                    $resumen['matches']['content']++;
                    break;
                }
            }
        }
    }

    $postWords = [];
    if (is_array($datosAlgoritmo)) {
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
    }

    foreach ($identifiers as $id) {
        if (isset($postWords[$id])) {
            $resumen['matches']['data']++;
        } else {
            foreach (array_keys($postWords) as $word) {
                similar_text($id, $word, $percent);
                if ($percent > 75) {
                    $resumen['matches']['data']++;
                    break;
                }
            }
        }
    }

    $puntosBaseContenido = 1000;
    $puntosBaseDatos = 250;
    $bonus = 2000;

    $resumen['puntos']['contenido'] = $resumen['matches']['content'] * $puntosBaseContenido;
    $resumen['puntos']['datos'] = $resumen['matches']['data'] * $puntosBaseDatos;

    if ($resumen['matches']['content'] === $totalIds) {
        $resumen['puntos']['bonus'] = $bonus;
    } elseif ($resumen['matches']['data'] === $totalIds) {
        $resumen['puntos']['bonus'] = (int)($bonus * 0.5);
    }

    $resumen['puntos']['total'] = $resumen['puntos']['contenido'] +
        $resumen['puntos']['datos'] +
        $resumen['puntos']['bonus'];

    return $resumen['puntos']['total'];
}

/**
 * Calcula puntos de similitud entre posts.
 * 
 * @deprecated Función interna del algoritmo
 */
function calcularPuntosSimilarTo($postId, $similarTo, $datos)
{
    $contenidoPost1 = isset($datos['post_content'][$postId]) ? strtolower($datos['post_content'][$postId]) : '';
    $contenidoPost2 = isset($datos['post_content'][$similarTo]) ? strtolower($datos['post_content'][$similarTo]) : '';

    $datosAlgoritmo1 = isset($datos['datosAlgoritmo'][$postId]->meta_value)
        ? procesarMetaValue($datos['datosAlgoritmo'][$postId]->meta_value)
        : [];

    $datosAlgoritmo2 = isset($datos['datosAlgoritmo'][$similarTo]->meta_value)
        ? procesarMetaValue($datos['datosAlgoritmo'][$similarTo]->meta_value)
        : procesarMetaValue(get_post_meta($similarTo, 'datosAlgoritmo', true));

    $wordsPost1 = array_merge(
        extractWordsFromDatosAlgoritmo($datosAlgoritmo1),
        extractWordsFromContent($contenidoPost1)
    );

    $wordsPost2 = array_merge(
        extractWordsFromDatosAlgoritmo($datosAlgoritmo2),
        extractWordsFromContent($contenidoPost2)
    );

    if (empty($wordsPost1) || empty($wordsPost2)) {
        return 0;
    }

    $set1 = array_unique($wordsPost1);
    $set2 = array_unique($wordsPost2);
    $intersection = array_intersect($set1, $set2);
    $union = array_unique(array_merge($set1, $set2));

    $contentWeight = 1.5;
    $contenidoMatches = count(array_intersect(
        extractWordsFromContent($contenidoPost1),
        extractWordsFromContent($contenidoPost2)
    ));

    $similarity = (count($intersection) + $contenidoMatches * $contentWeight) / count($union);

    return $similarity * 150;
}

/**
 * Procesa un valor de meta.
 * 
 * @deprecated Función interna del algoritmo
 */
function procesarMetaValue($metaValue)
{
    if (is_array($metaValue)) {
        return $metaValue;
    }
    if (is_string($metaValue)) {
        $decoded = json_decode($metaValue, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return [];
}

/**
 * Extrae palabras de datosAlgoritmo.
 * 
 * @deprecated Función interna del algoritmo
 */
function extractWordsFromDatosAlgoritmo($datosAlgoritmo)
{
    $words = [];

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

/**
 * Extrae palabras de contenido.
 * 
 * @deprecated Función interna del algoritmo
 */
function extractWordsFromContent($content)
{
    $words = preg_split('/\s+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);
    return array_map('stemWord', $words);
}

/**
 * Aplica stemming simple.
 * 
 * @deprecated Función interna del algoritmo
 */
function stemWord($word)
{
    return preg_replace('/(s|ed|ing)$/', '', $word);
}

/**
 * Obtiene y procesa vistas de posts.
 * 
 * @deprecated Función interna del algoritmo
 */
function obtenerYProcesarVistasPosts($userId)
{
    if (!function_exists('obtenerVistasPosts')) {
        return [];
    }

    $vistasPosts = obtenerVistasPosts($userId);
    $resultado = [];

    if (!empty($vistasPosts)) {
        foreach ($vistasPosts as $postId => $viewData) {
            $resultado[$postId] = [
                'count' => $viewData['count'],
                'last_view' => date('Y-m-d H:i:s', $viewData['last_view']),
            ];
        }
    }

    return $resultado;
}

/**
 * Genera meta de intereses para un usuario.
 * 
 * @deprecated Usar InteresService::generarMetaDeIntereses()
 * @param int $userId ID del usuario
 * @return bool
 */
function generarMetaDeIntereses($userId)
{
    $servicio = InteresService::obtenerInstancia();
    return $servicio->generarMetaDeIntereses((int)$userId);
}

/**
 * Obtiene los likes del usuario.
 * 
 * @deprecated Usar InteresService::obtenerLikesDelUsuario()
 * @param int $userId ID del usuario
 * @param int $limit Límite
 * @return array
 */
if (!function_exists('obtenerLikesDelUsuario')) {
    function obtenerLikesDelUsuario($userId, $limit = 500)
    {
        $servicio = InteresService::obtenerInstancia();
        return $servicio->obtenerLikesDelUsuario((int)$userId, (int)$limit);
    }
}

/**
 * Actualiza intereses del usuario.
 * 
 * @deprecated Función interna del servicio de intereses
 */
function actualizarIntereses($userId, $tagIntensidad, $interesesActuales)
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');

    try {
        $batchValues = [];

        foreach ($tagIntensidad as $interest => $intensity) {
            $batchValues[] = $wpdb->prepare('(%d, %s, %d)', $userId, $interest, $intensity);
        }

        if (!empty($batchValues)) {
            $values = implode(', ', $batchValues);
            $sql = "
                INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity)
                VALUES $values
                ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)
            ";
            $wpdb->query($sql);
        }

        if (!empty($interesesActuales)) {
            $interesesAEliminar = array_diff_key((array)$interesesActuales, $tagIntensidad);

            if (!empty($interesesAEliminar)) {
                $placeholders = implode(', ', array_fill(0, count($interesesAEliminar), '%s'));
                $sql = $wpdb->prepare(
                    "DELETE FROM " . INTERES_TABLE . " WHERE user_id = %d AND interest IN ($placeholders)",
                    array_merge([$userId], array_keys($interesesAEliminar))
                );
                $wpdb->query($sql);
            }
        }

        $wpdb->query('COMMIT');
        return true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return false;
    }
}
