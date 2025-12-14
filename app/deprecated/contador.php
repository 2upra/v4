<?php

/**
 * Funciones wrapper de compatibilidad para conteo y manejo de colecciones.
 * 
 * @deprecated Usar los servicios correspondientes
 * @see \Kamples\Services\ContadorService
 * @see \Kamples\Services\ColeccionService
 */

use Kamples\Services\CacheService;

/**
 * Maneja la carga de una colección.
 *
 * @deprecated Esta función debería migrarse a ColeccionService
 */
function manejarColeccion($args, $paged)
{
    $cache = CacheService::obtenerInstancia('feed');
    $cacheKey = 'coleccion_' . $args['colec'] . '_paged_' . $paged;

    $cachedData = $cache->obtener($cacheKey);
    if ($cachedData !== false) {
        return $cachedData;
    }

    $samplesMeta = get_post_meta($args['colec'], 'samples', true);
    if (!is_array($samplesMeta)) {
        $samplesMeta = maybe_unserialize($samplesMeta);
    }

    if (is_array($samplesMeta)) {
        $queryArgs = [
            'post_type' => $args['post_type'],
            'post__in' => array_values($samplesMeta),
            'orderby' => 'rand',
            'posts_per_page' => 12,
            'paged' => $paged,
        ];

        $cacheMasterKey = 'cache_colec_' . $args['colec'];
        $cacheKeys = $cache->obtener($cacheMasterKey) ?: [];
        $cacheKeys[] = $cacheKey;
        $cache->guardar($cacheMasterKey, $cacheKeys, 86400);
        $cache->guardar($cacheKey, $queryArgs, 86400);

        return $queryArgs;
    }

    return false;
}
