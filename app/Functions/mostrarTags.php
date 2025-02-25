<?php

/**
 * Obtiene y muestra tags frecuentes basados en metadatos de posts.
 */
function mostrarTagsFrecuentes()
{
    $tags = obtenerTagsFrecuentes();

    if (!empty($tags)) {
        echo '<div class="tags-frecuentes">';
        foreach ($tags as $tag) {
            echo '<span class="postTag">' . esc_html(ucwords($tag)) . '</span> ';
        }
        echo '</div>';
    } else {
        error_log('No se encontraron tags disponibles en mostrarTagsFrecuentes().');
        echo '<div class="tags-frecuentes">No tags available.</div>';
    }
}

/**
 *  Función principal para obtener los tags frecuentes.
 *  @return array Array de tags frecuentes.
 */
function obtenerTagsFrecuentes()
{
    $claveCache = 'tagsFrecuentes12';
    $tiempoCache = 43200;

    $tags = obtenerTagsCache($claveCache);

    if ($tags !== false) {
        $tags = mezclarYCortarTags($tags, 32);
        return $tags;
    }

    $tags = consultarYProcesarTags();

    if (empty($tags)) {
        error_log('No se pudieron obtener tags de la base de datos en obtenerTagsFrecuentes().');
        return [];
    }

    $tagsTop = obtenerTopTags($tags, 70);
    guardarCache($claveCache, $tagsTop, $tiempoCache);

    $tagsFinal = mezclarYCortarTags($tagsTop, 32);
    return $tagsFinal;
}

/**
 *  Intenta obtener los tags del cache.
 *  @param string $claveCache Clave para el cache.
 *  @return mixed Array de tags si existe en el cache, false si no.
 */
function obtenerTagsCache(string $claveCache)
{
    $tags = obtenerCache($claveCache);
    return $tags;
}

/**
 *  Mezcla un array de tags y retorna una porción.
 *  @param array $tags Array de tags.
 *  @param int $cantidad Cantidad de tags a retornar.
 *  @return array Array mezclado y cortado.
 */
function mezclarYCortarTags(array $tags, int $cantidad)
{
    $arrTags = array_keys($tags);
    shuffle($arrTags);
    return array_slice($arrTags, 0, $cantidad);
}

/**
 *  Realiza la consulta a la base de datos y procesa los resultados para obtener los tags.
 *  @return array Array asociativo con el conteo de cada tag.
 */
function consultarYProcesarTags()
{
    global $wpdb;

    $fechaLimite = date('Y-m-d', strtotime('-1 month'));
    $consulta = $wpdb->prepare(
        "SELECT pm.meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'datosAlgoritmo'
        AND p.post_type = 'social_post'
        AND p.post_date >= %s
        LIMIT 20000",
        $fechaLimite
    );

    $resultados = $wpdb->get_col($consulta);

    if (empty($resultados)) {
        error_log('No se encontraron resultados en la consulta a la base de datos en consultarYProcesarTags().');
        return [];
    }

    return contarTags($resultados);
}

/**
 *  Cuenta la frecuencia de cada tag en los resultados.
 *  @param array $resultados Array de resultados de la consulta a la base de datos.
 *  @return array Array asociativo con el conteo de cada tag.
 */
function contarTags(array $resultados)
{
    $conteoTags = [];
    $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio', 'artista_posible'];

    foreach ($resultados as $valorMeta) {
        $datosMeta = json_decode($valorMeta, true);

        if (!is_array($datosMeta)) {
            error_log('No se pudo decodificar el JSON: ' . $valorMeta . ' en contarTags().');
            continue;
        }

        foreach ($campos as $campo) {
            if (!empty($datosMeta[$campo]['en']) && is_array($datosMeta[$campo]['en'])) {
                foreach ($datosMeta[$campo]['en'] as $tag) {
                    if (is_string($tag)) {
                        $tagNormalizado = strtolower(trim($tag));
                        if (!empty($tagNormalizado)) {
                            $conteoTags[$tagNormalizado] = ($conteoTags[$tagNormalizado] ?? 0) + 1;
                        }
                    }
                }
            }
        }
    }

    return $conteoTags;
}

/**
 *  Obtiene los tags más frecuentes.
 *  @param array $conteoTags Array asociativo con el conteo de cada tag.
 *  @param int $cantidad Cantidad de tags a retornar.
 *  @return array Array asociativo con los tags más frecuentes.
 */
function obtenerTopTags(array $conteoTags, int $cantidad)
{
    if (empty($conteoTags)) {
        return [];
    }

    arsort($conteoTags);
    return array_slice($conteoTags, 0, $cantidad, true);
}
