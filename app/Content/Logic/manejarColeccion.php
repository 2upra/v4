<?

function manejarColeccion($args, $paged)
{
    $cacheKey = 'coleccion_' . $args['colec'] . '_paged_' . $paged;

    $cachedData = obtenerCache($cacheKey);
    if ($cachedData !== false) {
        guardarLog("manejarColeccion: Cargando desde caché: {$args['colec']}");
        return $cachedData;
    }

    guardarLog("manejarColeccion: Cargando desde DB: {$args['colec']}");
    $samplesMeta = get_post_meta($args['colec'], 'samples', true);
    if (!is_array($samplesMeta)) {
        $samplesMeta = maybe_unserialize($samplesMeta);
    }

    if (is_array($samplesMeta)) {
        $queryArgs = [
            'post_type'      => $args['post_type'],
            'post__in'       => array_values($samplesMeta),
            'orderby'        => 'rand', // Cambiamos a orden aleatorio
            'posts_per_page' => 12,
            'paged'          => $paged,
        ];

        $cacheMasterKey = 'cache_colec_' . $args['colec'];
        $cacheKeys = obtenerCache($cacheMasterKey) ?: [];
        $cacheKeys[] = $cacheKey;
        guardarCache($cacheMasterKey, $cacheKeys, 86400);
        guardarCache($cacheKey, $queryArgs, 86400);

        return $queryArgs;
    } else {
        return false;
    }
}
