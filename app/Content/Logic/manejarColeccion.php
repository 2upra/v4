<?
function manejarColeccion($args, $paged)
{
    $cacheKeyIds = 'coleccion_ids_' . $args['colec']; // Clave para los IDs
    $cacheTimeIds = 86400;  // Tiempo de vida largo para los IDs (1 día)
    $cacheKeyQuery = 'coleccion_query_' . $args['colec'] . '_paged_' . $paged; //Clave para la query
    $cacheTimeQuery = 300;  //Tiempo de vida corto para la query

    $ids = obtenerCache($cacheKeyIds);

    if ($ids === false) {
        guardarLog("manejarColeccion: Cargando IDs desde DB: {$args['colec']}");
        $samplesMeta = get_post_meta($args['colec'], 'samples', true);
        if (!is_array($samplesMeta)) {
            $samplesMeta = maybe_unserialize($samplesMeta);
        }

        if (is_array($samplesMeta)) {
            $ids = array_values($samplesMeta);
            guardarCache($cacheKeyIds, $ids, $cacheTimeIds); // Guardar los IDs
        } else {
            return false;
        }
    }


    shuffle($ids); // *Barajar* los IDs en PHP

    //Limitar ids a mostrar por pagina
    $offset = ($paged - 1) * 12;
    $limitedIds = array_slice($ids, $offset, 12);

    $queryArgs = [
        'post_type'      => 'post',  // Asumiendo que siempre es 'post'.  Si no, usa $args['post_type']
        'post__in'       => $limitedIds,
        'orderby'        => 'post__in', // Importante: Mantener el orden de $limitedIds
        'posts_per_page' => 12,
        'paged'          => $paged,
        'ignore_sticky_posts' => true, // Añadido para evitar problemas con sticky posts
    ];
    return $queryArgs;
}
