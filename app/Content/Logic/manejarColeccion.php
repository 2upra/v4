<? 

function manejarColeccion($args, $paged)
{
    // Crear una clave de caché única basada en la colección y la paginación
    $cache_key = 'coleccion_' . $args['colec'] . '_paged_' . $paged;

    // Intentar obtener datos desde la caché
    $cached_data = obtenerCache($cache_key);
    if ($cached_data !== false) {
        guardarLog("Cargando colección desde la caché para colección {$args['colec']}");
        return $cached_data;
    }

    guardarLog("Cargando posts de la colección desde la base de datos para colección {$args['colec']}");
    $samples_meta = get_post_meta($args['colec'], 'samples', true);
    if (!is_array($samples_meta)) {
        $samples_meta = maybe_unserialize($samples_meta);
    }

    if (is_array($samples_meta)) {
        $query_args = [
            'post_type' => $args['post_type'],
            'post__in' => array_values($samples_meta),
            'orderby' => 'post__in',
            'posts_per_page' => 12,
            'paged' => $paged,
        ];

        // Guardamos la clave de la caché en una lista asociada a la colección, para facilitar su eliminación
        $cache_master_key = 'cache_colec_' . $args['colec'];
        $cache_keys = obtenerCache($cache_master_key) ?: [];
        $cache_keys[] = $cache_key;
        guardarCache($cache_master_key, $cache_keys, 86400); // Guardar lista de claves de caché

        // Guardar los resultados en la caché con una expiración de 1 día
        guardarCache($cache_key, $query_args, 86400);

        return $query_args;
    } else {
        //error_log("[manejarColeccion] El meta 'samples' no es un array válido.");
        return false;
    }
}
