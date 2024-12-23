<?

function publicacionAjax()
{
    $paged = isset($_POST['paged']) ? (int) $_POST['paged'] : 1;
    $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $tipoPost = isset($_POST['posttype']) ? sanitize_text_field($_POST['posttype']) : '';
    $data_identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
    $tab_id = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
    $userId = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
    $publicacionesCargadas = isset($_POST['cargadas']) && is_array($_POST['cargadas'])
        ? array_map('intval', $_POST['cargadas'])
        : array();
    $similarTo = isset($_POST['similar_to']) ? intval($_POST['similar_to']) : null;
    $colec = isset($_POST['colec']) ? intval($_POST['colec']) : null;
    $idea = isset($_POST['idea']) ? filter_var($_POST['idea'], FILTER_VALIDATE_BOOLEAN) : false;

    ////error_log("[publicacionAjax] Received identifier: " . $data_identifier);

    publicaciones(
        array(
            'filtro' => $filtro,
            'post_type' => $tipoPost,
            'tab_id' => $tab_id,
            'user_id' => $userId,
            'identifier' => $data_identifier,
            'exclude' => $publicacionesCargadas,
            'similar_to' => $similarTo,
            'colec' => $colec,
            'idea' => $idea,

        ),
        true,
        $paged
    );
}
add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');

function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    try {
        //$userId = obtenerUserId($is_ajax);
        $usuarioActual = get_current_user_id();

        $defaults = [
            'filtro' => '',
            'tab_id' => '',
            'posts' => 12,
            'exclude' => [],
            'post_type' => 'social_post',
            'similar_to' => null,
            'colec' => null,
            'idea' => null,
            'user_id' => null,
            'identifier' => '',
            'tipoUsuario' => '',
        ];

        if (!$is_ajax && isset($_GET['busqueda'])) {
            $args['identifier'] = sanitize_text_field($_GET['busqueda']);
        }

        $userId = isset($args['user_id']) ? $args['user_id'] : '';
        $tipoUsuario = isset($args['tipoUsuario']) && !empty($args['tipoUsuario'])
            ? $args['tipoUsuario']
            : get_user_meta($usuarioActual, 'tipoUsuario', true);
        $args = array_merge($defaults, $args);

        if (filter_var($args['idea'], FILTER_VALIDATE_BOOLEAN)) {
            $query_args = manejarIdea($args, $paged);
            if (!$query_args) {
                return false;
            }
        } else if (!empty($args['colec']) && is_numeric($args['colec'])) {
            $query_args = manejarColeccion($args, $paged);
            if (!$query_args) {
                return false;
            }
        } else {
            $query_args = configuracionQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario);
        }

        guardarLog("valor $query_args");
        
        $output = procesarPublicaciones($query_args, $args, $is_ajax);

        if ($is_ajax) {
            echo $output;
            wp_die();
        }

        return $output;
    } catch (Exception $e) {
        return false;
    }
}

function configuracionQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario)
{
    try {
        $FALLBACK_USER_ID = 44;
        $is_authenticated = $usuarioActual && $usuarioActual != 0;
        $isAdmin = current_user_can('administrator');

        if (!$is_authenticated) {
            $usuarioActual = $FALLBACK_USER_ID;
        }

        $identifier = isset($args['identifier']) ? $args['identifier'] : '';

        if (!empty($userId)) {
            $query_args = [
                'post_type' => $args['post_type'],
                'posts_per_page' => $args['posts'],
                'paged' => $paged,
                'ignore_sticky_posts' => true,
                'suppress_filters' => false,
                'orderby' => 'date',
                'order' => 'DESC',
                'author' => $userId,
            ];

            $query_args = aplicarFiltroGlobal($query_args, $args, $usuarioActual, $userId);
            return $query_args;
        }

        $posts = $args['posts'];
        $similarTo = $args['similar_to'] ?? null;

        $filtroTiempo = (int)get_user_meta($usuarioActual, 'filtroTiempo', true);

        $query_args = construirQueryArgs($args, $paged, $usuarioActual, $identifier, $isAdmin, $posts, $filtroTiempo, $similarTo, $tipoUsuario);

        if ($args['post_type'] === 'social_post' && in_array($args['filtro'], ['sampleList', 'sample'])) {
            if ($tipoUsuario !== 'Fan') {
                $query_args = aplicarFiltrosUsuario($query_args, $usuarioActual);
            }
        }

        $query_args = aplicarFiltroGlobal($query_args, $args, $usuarioActual, $userId, $tipoUsuario);

        return $query_args;
    } catch (Exception $e) {
        return false;
    }
}

function construirQueryArgs($args, $paged, $usuarioActual, $identifier, $isAdmin, $posts, $filtroTiempo, $similarTo, $tipoUsuario = null)
{
    try {
        global $wpdb;
        if (!$wpdb) {

            return false;
        }
        //error_log("[construirQueryArgs] construirQueryArgs!!");

        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'ignore_sticky_posts' => true,
            'suppress_filters' => false,
        ];

        if (!empty($identifier)) {
            $query_args = prefiltrarIdentifier($identifier, $query_args);
            if (!$query_args) {
                ////error_log("[construirQueryArgs] Error: Falló el filtrado por identifier: " . $identifier);
            }
        }

        // Only apply ordenamiento if post_type is social_post AND filtro is not 'rola'
        if ($args['post_type'] === 'social_post' && (!isset($args['filtro']) || $args['filtro'] !== 'rola')) {
            //error_log("[construirQueryArgs] ordenamiento!!");
            $query_args = ordenamiento($query_args, $filtroTiempo, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario);
            if (!$query_args) {
                ////error_log("[construirQueryArgs] Error: Falló el ordenamiento de la consulta para post_type social_post");
            }
        }

        if ($args['post_type'] === 'colecciones') {
            //error_log("[construirQueryArgs] ordenamiento!! ordenamientoColecciones");
            $query_args = ordenamientoColecciones($query_args, $filtroTiempo, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario);
            if (!$query_args) {
                ////error_log("[construirQueryArgs] Error: Falló el ordenamiento de la consulta para post_type social_post");
            }
        }

        return $query_args;
    } catch (Exception $e) {
        ////error_log("[construirQueryArgs] Error crítico: " . $e->getMessage());
        return false;
    }
}

function ordenamientoColecciones($query_args, $filtroTiempo, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario = null)
{
    global $wpdb;
    $likes_table = $wpdb->prefix . 'post_likes';

    /*
      try {
        global $wpdb;
        if (!$wpdb) {
            return false;
        }

        $likes_table = $wpdb->prefix . 'post_likes';

        // Validación de query_args
        if (!is_array($query_args)) {
            $query_args = array();
        }

        switch ($filtroTiempo) {
            case 1: // Recientes
                //error_log("[ordenamiento] caso reciente!!");
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;

            case 2: // Top semanal
            case 3: // Top mensual
                //error_log("[ordenamiento] caso mensual!!");
                $interval = ($filtroTiempo === 2) ? '1 WEEK' : '1 MONTH';

                $sql = "
                    SELECT p.ID, 
                           COUNT(pl.post_id) as like_count 
                    FROM {$wpdb->posts} p 
                    LEFT JOIN {$likes_table} pl ON p.ID = pl.post_id 
                    WHERE p.post_type = 'social_post' 
                    AND p.post_status = 'publish'
                    AND p.post_date >= DATE_SUB(NOW(), INTERVAL $interval)  
                    AND pl.like_date >= DATE_SUB(NOW(), INTERVAL $interval) 
                    GROUP BY p.ID
                    HAVING like_count > 0
                    ORDER BY like_count DESC, p.post_date DESC
                ";

                $posts_with_likes = $wpdb->get_results($sql, ARRAY_A);

                if ($wpdb->last_error) {
                    // Log de error si es necesario
                }

                if (!empty($posts_with_likes)) {
                    $post_ids = wp_list_pluck($posts_with_likes, 'ID');
                    if (!empty($post_ids)) {
                        $query_args['post__in'] = $post_ids;
                        $query_args['orderby'] = 'post__in';
                    }
                } else {
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;
    */

    // 1. Cache Key
    $cache_key = 'colecciones_ordenadas_' . $usuarioActual . '_' . $filtroTiempo;
    //$cached_data = obtenerCache($cache_key);

    /* if ($cached_data) {
        //error_log("[ordenamientoColecciones] Retornando datos desde cache: " . $cache_key);
        $query_args['post__in'] = $cached_data;
        $query_args['orderby'] = 'post__in';
        return $query_args;
    } */

    // 2. Filtrar "Usar más tarde" y "Favoritos" (a menos que sean del usuario actual)
    $excluded_titles = ['Usar más tarde', 'Favoritos'];
    $excluded_ids = [];

    if ($usuarioActual) {
        $excluded_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND (post_title LIKE '%%%s%%' OR post_title LIKE '%%%s%%') AND post_author != %d",
            $excluded_titles[0],
            $excluded_titles[1],
            $usuarioActual
        ));
    } else {
        $excluded_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND (post_title LIKE '%%%s%%' OR post_title LIKE '%%%s%%')",
            $excluded_titles[0],
            $excluded_titles[1]
        ));
    }

    // 3. Obtener IDs de colecciones con más likes en los últimos 30 días
    $interval = 30;
    $popular_ids = $wpdb->get_results(
        "SELECT p.ID, COUNT(pl.post_id) as like_count 
        FROM {$wpdb->posts} p 
        LEFT JOIN {$likes_table} pl ON p.ID = pl.post_id 
        WHERE p.post_type = 'colecciones' 
        AND p.post_status = 'publish'
        AND pl.like_date >= DATE_SUB(NOW(), INTERVAL {$interval} DAY)
        GROUP BY p.ID
        ORDER BY like_count DESC, p.post_date DESC",
        ARRAY_A // Get results as an associative array
    );

    // 4. Combinar y aleatorizar
    $all_ids = [];
    $popular_ids_list = [];

    foreach ($popular_ids as $post) {
        $popular_ids_list[] = $post['ID'];
    }

    $all_ids = array_unique(array_merge($popular_ids_list, $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND ID NOT IN (%s)",
            implode(',', array_merge($excluded_ids, $popular_ids_list ? $popular_ids_list : [0]))
        )
    )));

    // Mezclar las IDs que no son populares para agregar aleatoriedad.
    $non_popular_ids = array_diff($all_ids, $popular_ids_list);
    shuffle($non_popular_ids);

    // Combinar IDs populares con IDs no populares mezcladas.
    $ordered_ids = array_merge($popular_ids_list, $non_popular_ids);

    // 5. Guardar en caché
    //guardarCache($cache_key, $ordered_ids, 3600); // 1 hora

    // 6. Actualizar $query_args
    $query_args['post__in'] = $ordered_ids;
    $query_args['orderby'] = 'post__in';
    $query_args['post__not_in'] = $excluded_ids;

    //error_log("[ordenamientoColecciones] IDs ordenadas: " . implode(', ', $ordered_ids));

    return $query_args;
}

function aplicarFiltroGlobal($query_args, $args, $usuarioActual, $userId, $tipoUsuario = null)
{
    if (!empty($userId)) {
        $query_args['author'] = $userId;
        // Mover las condiciones específicas de los nuevos filtros aquí
        $filtro = $args['filtro'] ?? 'nada';
        if ($filtro === 'imagenesPerfil') {
            $query_args['meta_query'] = array_merge($query_args['meta_query'] ?? [], [
                ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
                ['key' => 'post_audio_lite', 'compare' => 'NOT EXISTS'],
            ]);
        } elseif ($filtro === 'tiendaPerfil') {
            $query_args['meta_query'] = array_merge($query_args['meta_query'] ?? [], [
                ['key' => 'tienda', 'value' => '1', 'compare' => '='],
                ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
            ]);
        }

        return $query_args;
    }

    $filtrosUsuario = get_user_meta($usuarioActual, 'filtroPost', true);
    $filtro = $args['filtro'] ?? 'nada';

    if ($filtro === 'sampleList' && is_array($filtrosUsuario) && in_array('misPost', $filtrosUsuario)) {
        $query_args['author'] = $usuarioActual;
    }

    if ($filtro === 'colecciones' && is_array($filtrosUsuario) && in_array('misColecciones', $filtrosUsuario)) {
        $query_args['author'] = $usuarioActual;
    }



    $meta_query_conditions = [
        'rolasEliminadas' => fn() => $query_args['post_status'] = 'pending_deletion',
        'rolasRechazadas' => fn() => $query_args['post_status'] = 'rejected',
        'rolasPendiente' => fn() => $query_args['post_status'] = 'pending',
        'likesRolas' => fn() => ($userLikedPostIds = obtenerLikesDelUsuario($usuarioActual))
            ? $query_args['post__in'] = $userLikedPostIds
            : $query_args['posts_per_page'] = 0,
        'nada' => fn() => $query_args['post_status'] = 'publish',
        'colabs' => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'libres' => [
            ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!='],
        ],
        'momento' => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
        ],
        'sample' => function () use ($tipoUsuario, &$query_args) {
            if ($tipoUsuario === 'Fan') {
                $query_args['post_status'] = 'publish';
            } else {
                $query_args['meta_query'] = array_merge($query_args['meta_query'] ?? [], [
                    ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                    ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
                ]);
            }
        },
        'rolaListLike' => function () use ($usuarioActual, &$query_args) {
            $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
            if (empty($userLikedPostIds)) {
                $query_args['posts_per_page'] = 0;
                return;
            }

            $query_args['meta_query'] = array_merge($query_args['meta_query'] ?? [], [
                'relation' => 'AND', // Asegurarse de que se cumplan ambas condiciones.
                [
                    'key'     => 'rola',
                    'value'   => '1',
                    'compare' => '=',
                ],
                [
                    'key'     => 'post_audio_lite',
                    'compare' => 'EXISTS',
                ],

            ]);

            $query_args['post__in'] = $userLikedPostIds;
        },
        'sampleList' => [
            'relation' => 'AND', // Importante cambiar a AND
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
            [
                'relation' => 'OR',
                ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                ['key' => 'tienda', 'value' => '1', 'compare' => '='],
            ],
        ],
        'colab' => fn() => $query_args['post_status'] = 'publish',
        'colabPendiente' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = 'pending';
        },
        'rola' => [
            ['key' => 'rola', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ],

    ];

    if (isset($meta_query_conditions[$filtro])) {
        $result = $meta_query_conditions[$filtro];

        if (is_callable($result)) {
            $result();
        } else {
            $query_args['post_status'] = 'publish';
            $query_args['meta_query'] = array_merge($query_args['meta_query'] ?? [], $result);
        }
    }

    return $query_args;
}

function ordenamiento($query_args, $filtroTiempo, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario = null)
{
    // Si el tipo de usuario es "Fan", forzamos el caso default
    if ($tipoUsuario === 'Fan') {
        try {
            global $wpdb;
            if (!$wpdb) {
                return false;
            }

            // Caso default: Feed personalizado
            $feed_result = obtenerFeedPersonalizado($usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario);

            if (!empty($feed_result['post_ids'])) {
                $query_args['post__in'] = $feed_result['post_ids'];
                $query_args['orderby'] = 'post__in';

                if (count($feed_result['post_ids']) > POSTINLIMIT) {
                    $feed_result['post_ids'] = array_slice($feed_result['post_ids'], 0, POSTINLIMIT);
                }

                if (!empty($feed_result['post_not_in'])) {
                    $query_args['post__not_in'] = $feed_result['post_not_in'];
                }
            } else {
                // Si el feed personalizado está vacío, usar ordenamiento por fecha por defecto
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
            }

            return $query_args;
        } catch (Exception $e) {
            return false;
        }
    }

    // Obtener los filtros del usuario
    $filtrosUsuario = get_user_meta($usuarioActual, 'filtroPost', true);

    // Verificar si los filtros del usuario tienen algún valor diferente a `a:0:{}`
    if (!empty($filtrosUsuario) && $filtrosUsuario !== 'a:0:{}') {
        // No usar el caso default si existen filtros específicos
        if ($filtroTiempo === 0) {
            //return $query_args; // Si pide default, regresar la query sin modificaciones
        }
    }

    error_log("[ordenamiento] aplicando ordenamiento");

    try {
        global $wpdb;
        if (!$wpdb) {
            return false;
        }

        $likes_table = $wpdb->prefix . 'post_likes';

        // Validación de query_args
        if (!is_array($query_args)) {
            $query_args = array();
        }

        switch ($filtroTiempo) {
            case 1: // Recientes
                //error_log("[ordenamiento] caso reciente!!");
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;

            case 2: // Top semanal
            case 3: // Top mensual
                //error_log("[ordenamiento] caso mensual!!");
                $interval = ($filtroTiempo === 2) ? '1 WEEK' : '1 MONTH';

                $sql = "
                    SELECT p.ID, 
                           COUNT(pl.post_id) as like_count 
                    FROM {$wpdb->posts} p 
                    LEFT JOIN {$likes_table} pl ON p.ID = pl.post_id 
                    WHERE p.post_type = 'social_post' 
                    AND p.post_status = 'publish'
                    AND p.post_date >= DATE_SUB(NOW(), INTERVAL $interval)  
                    AND pl.like_date >= DATE_SUB(NOW(), INTERVAL $interval) 
                    GROUP BY p.ID
                    HAVING like_count > 0
                    ORDER BY like_count DESC, p.post_date DESC
                ";

                $posts_with_likes = $wpdb->get_results($sql, ARRAY_A);

                if ($wpdb->last_error) {
                    // Log de error si es necesario
                }

                if (!empty($posts_with_likes)) {
                    $post_ids = wp_list_pluck($posts_with_likes, 'ID');
                    if (!empty($post_ids)) {
                        $query_args['post__in'] = $post_ids;
                        $query_args['orderby'] = 'post__in';
                    }
                } else {
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;

            default: // Feed personalizado
                //error_log("[ordenamiento] caso default!");

                $feed_result = obtenerFeedPersonalizado($usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario, $filtrosUsuario);

                if (!empty($feed_result['post_ids'])) {
                    $query_args['post__in'] = $feed_result['post_ids'];
                    $query_args['orderby'] = 'post__in';

                    if (count($feed_result['post_ids']) > POSTINLIMIT) {
                        $feed_result['post_ids'] = array_slice($feed_result['post_ids'], 0, POSTINLIMIT);
                    }

                    if (!empty($feed_result['post_not_in'])) {
                        $query_args['post__not_in'] = $feed_result['post_not_in'];
                    }
                } else {
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }

                break;
        }

        if (empty($query_args['orderby'])) {
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
        }

        return $query_args;
    } catch (Exception $e) {
        return false;
    }
}

function aplicarFiltrosUsuario($query_args, $usuarioActual)
{
    //guardarLog("Iniciando aplicarFiltrosUsuario para el usuario $usuarioActual");
    $filtrosUsuario = get_user_meta($usuarioActual, 'filtroPost', true);

    //guardarLog("Filtros del usuario: " . print_r($filtrosUsuario, true));

    if (empty($filtrosUsuario) || !is_array($filtrosUsuario)) {
        //guardarLog("No hay filtros aplicables o el formato es incorrecto.");
        return $query_args;
    }

    // Inicializar variables para mantener los IDs a incluir y excluir
    $post_not_in = $query_args['post__not_in'] ?? [];
    $post_in = $query_args['post__in'] ?? [];

    // Filtro para ocultar posts descargados
    if (in_array('ocultarDescargados', $filtrosUsuario)) {
        $descargasAnteriores = get_user_meta($usuarioActual, 'descargas', true) ?: [];
        //guardarLog("Descargas anteriores: " . print_r($descargasAnteriores, true));
        if (!empty($descargasAnteriores)) {
            $post_not_in = array_merge(
                $post_not_in,
                array_keys($descargasAnteriores)
            );
            //guardarLog("Post__not_in después de ocultar descargados: " . print_r($post_not_in, true));
        }
    }

    // Filtro para ocultar posts en colección
    if (in_array('ocultarEnColeccion', $filtrosUsuario)) {
        $samplesGuardados = get_user_meta($usuarioActual, 'samplesGuardados', true) ?: [];
        //guardarLog("Samples guardados: " . print_r($samplesGuardados, true));
        if (!empty($samplesGuardados)) {
            $guardadosIDs = array_keys($samplesGuardados);
            $post_not_in = array_merge(
                $post_not_in,
                $guardadosIDs
            );
            //guardarLog("Post__not_in después de ocultar en colección: " . print_r($post_not_in, true));
        }
    }

    // Filtro para mostrar solo los posts que le han gustado al usuario
    if (in_array('mostrarMeGustan', $filtrosUsuario)) {
        $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
        //guardarLog("Post IDs que le gustan al usuario: " . print_r($userLikedPostIds, true));
        if (!empty($userLikedPostIds)) {
            if (!empty($post_in)) {
                $post_in = array_intersect($post_in, $userLikedPostIds);
            } else {
                $post_in = $userLikedPostIds;
            }

            //guardarLog("Post__in después de aplicar mostrarMeGustan: " . print_r($post_in, true));

            if (empty($post_in)) {
                $query_args['posts_per_page'] = 0;
                //guardarLog("No hay posts que mostrar después de aplicar mostrarMeGustan.");
            }
        } else {
            $query_args['posts_per_page'] = 0;
            //guardarLog("No hay posts que le gusten al usuario, posts_per_page se establece en 0.");
        }
    }

    // Eliminar los IDs en post_not_in de post_in para evitar conflictos
    if (!empty($post_in) && !empty($post_not_in)) {
        $post_in = array_diff($post_in, $post_not_in);
        //guardarLog("Post__in después de eliminar IDs en post__not_in: " . print_r($post_in, true));

        if (empty($post_in)) {
            $query_args['posts_per_page'] = 0;
            //guardarLog("No hay posts que mostrar después de aplicar los filtros.");
        }
    }


    if (!empty($post_in)) {
        $query_args['post__in'] = $post_in;
    } else {
        unset($query_args['post__in']);
    }

    if (!empty($post_not_in)) {
        $query_args['post__not_in'] = $post_not_in;
    } else {
        unset($query_args['post__not_in']);
    }

    return $query_args;
}


function prefiltrarIdentifier($identifier, $query_args)
{
    global $wpdb;

    $identifier = strtolower(trim($identifier));

    // Separar términos positivos y negativos
    $parts = explode('-', $identifier);
    $positive_terms = array();
    $negative_terms = array();

    // Primer parte son términos positivos
    $terms = explode(' ', trim($parts[0]));
    foreach ($terms as $term) {
        $term = trim($term);
        if (empty($term)) continue;
        $positive_terms[] = $term;
    }

    // Siguientes partes son términos negativos
    for ($i = 1; $i < count($parts); $i++) {
        $terms = explode(' ', trim($parts[$i]));
        foreach ($terms as $term) {
            $term = trim($term);
            if (empty($term)) continue;
            $negative_terms[] = $term;
        }
    }

    // Normalizar términos positivos
    $normalized_positive_terms = array();
    foreach ($positive_terms as $term) {
        $normalized_positive_terms[] = $term;
        if (substr($term, -1) === 's') {
            $normalized_positive_terms[] = substr($term, 0, -1);
        } else {
            $normalized_positive_terms[] = $term . 's';
        }
    }
    $normalized_positive_terms = array_unique($normalized_positive_terms);

    // Normalizar términos negativos
    $normalized_negative_terms = array();
    foreach ($negative_terms as $term) {
        $normalized_negative_terms[] = $term;
        if (substr($term, -1) === 's') {
            $normalized_negative_terms[] = substr($term, 0, -1);
        } else {
            $normalized_negative_terms[] = $term . 's';
        }
    }
    $normalized_negative_terms = array_unique($normalized_negative_terms);

    // Mantener el valor original en 's'
    $query_args['s'] = $identifier;

    add_filter('posts_search', function ($search, $wp_query) use ($normalized_positive_terms, $normalized_negative_terms, $wpdb) {
        if (empty($normalized_positive_terms) && empty($normalized_negative_terms)) {
            return $search;
        }

        $search = '';
        $search_conditions = array();

        // Condiciones para términos positivos
        if (!empty($normalized_positive_terms)) {
            $term_conditions = array();
            foreach ($normalized_positive_terms as $term) {
                $like_term = '%' . $wpdb->esc_like($term) . '%';
                $term_conditions[] = $wpdb->prepare("
                    (
                        {$wpdb->posts}.post_title LIKE %s OR
                        {$wpdb->posts}.post_content LIKE %s OR
                        EXISTS (
                            SELECT 1 FROM {$wpdb->postmeta}
                            WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                            AND {$wpdb->postmeta}.meta_key = 'datosAlgoritmo'
                            AND {$wpdb->postmeta}.meta_value LIKE %s
                        )
                    )
                ", $like_term, $like_term, $like_term);
            }
            $search_conditions[] = '(' . implode(' OR ', $term_conditions) . ')';
        }

        // Condiciones para términos negativos
        if (!empty($normalized_negative_terms)) {
            $term_conditions = array();
            foreach ($normalized_negative_terms as $term) {
                $like_term = '%' . $wpdb->esc_like($term) . '%';
                $term_conditions[] = $wpdb->prepare("
                    (
                        {$wpdb->posts}.post_title NOT LIKE %s AND
                        {$wpdb->posts}.post_content NOT LIKE %s AND
                        NOT EXISTS (
                            SELECT 1 FROM {$wpdb->postmeta}
                            WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                            AND {$wpdb->postmeta}.meta_key = 'datosAlgoritmo'
                            AND {$wpdb->postmeta}.meta_value LIKE %s
                        )
                    )
                ", $like_term, $like_term, $like_term);
            }
            $search_conditions[] = '(' . implode(' AND ', $term_conditions) . ')';
        }

        if (!empty($search_conditions)) {
            $search .= ' AND ' . implode(' AND ', $search_conditions);
        }

        return $search;
    }, 10, 2);

    return $query_args;
}

function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();
    $userId = get_current_user_id();

    if (empty($query_args) || !is_array($query_args)) {
        return '';
    }

    try {
        $query = new WP_Query($query_args);
        if (!is_a($query, 'WP_Query') || !is_object($query) || !method_exists($query, 'have_posts')) {
            return '';
        }
    } catch (Exception $e) {
        return '';
    }


    $filtro = !empty($args['filtro']) ? $args['filtro'] : $args['filtro'];
    if ($query->have_posts()) {
        $tipoPost = $args['post_type'];
        if (!wp_doing_ajax()) {
            $clase_extra = 'clase-' . esc_attr($filtro);
            if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
                $clase_extra = 'clase-rolastatus';
            }
            echo '<ul class="social-post-list ' . esc_attr($clase_extra) . '" 
                  data-filtro="' . esc_attr($filtro) . '" 
                  data-posttype="' . esc_attr($tipoPost) . '" 
                  data-tab-id="' . esc_attr($args['tab_id']) . '">';
        }

        while ($query->have_posts()) {
            $query->the_post();

            switch ($tipoPost) {
                case 'social_post':
                    if ($filtro === 'rola' || $filtro === 'tiendaPerfil') {
                        echo htmlColec($filtro);
                    } else {
                        echo htmlPost($filtro);
                    }
                    break;
                case 'colab':
                    echo htmlColab($filtro);
                    break;
                case 'colecciones':
                    echo htmlColec($filtro);
                    break;
                case 'post':
                    echo htmlArticulo($filtro);
                    break;
                default:
                    echo '<p>Tipo de publicación no reconocido.</p>';
            }
        }

        if (!wp_doing_ajax()) {
            echo '</ul>';
        }
    } else {
        echo nohayPost($filtro, $is_ajax);
    }
    wp_reset_postdata();
    return ob_get_clean();
}

function obtenerUserId($is_ajax)
{
    if ($is_ajax && isset($_POST['user_id'])) {
        return sanitize_text_field($_POST['user_id']);
    }

    $url_segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    $indices = ['perfil', 'music', 'author', 'sello'];
    foreach ($indices as $index) {
        $pos = array_search($index, $url_segments);
        if ($pos !== false) {
            if ($index === 'sello') {
                return get_current_user_id();
            } elseif (isset($url_segments[$pos + 1])) {
                $usuario = get_user_by('slug', $url_segments[$pos + 1]);
                if ($usuario) return $usuario->ID;
            }
            break;
        }
    }

    return null;
}

/*
TEST 
function publicar_en_threads($post_id) {
    // Obtener el contenido del post de WordPress
    $post = get_post($post_id);
    
    if (!$post) {
        return 'Post no encontrado.';
    }

    // Obtener el texto del post
    $texto = wp_strip_all_tags($post->post_content); // Limpiar HTML
    $titulo = get_the_title($post_id); // Título del post

    // Obtener la URL de la imagen destacada (si existe)
    $image_url = get_the_post_thumbnail_url($post_id, 'full');

    // Definir el tipo de media (texto o imagen)
    $media_type = $image_url ? 'IMAGE' : 'TEXT';

    // Access token y user ID (debes ajustarlo)
    $access_token = 'TU_ACCESS_TOKEN';
    $threads_user_id = 'TU_THREADS_USER_ID';

    // Construir la URL de la API para crear el contenedor de medios
    $url = "https://graph.threads.net/v1.0/{$threads_user_id}/threads?access_token={$access_token}";

    // Preparar los datos para la solicitud
    $data = array(
        'media_type' => $media_type,
        'text' => $titulo . "\n" . $texto,
    );

    // Si hay una imagen, agregarla a los datos
    if ($image_url) {
        $data['image_url'] = $image_url;
    }

    // Hacer la solicitud cURL para crear el contenedor de medios
    $response = wp_remote_post($url, array(
        'method' => 'POST',
        'body' => $data,
    ));

    if (is_wp_error($response)) {
        return 'Error en la solicitud: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['id'])) {
        // Contenedor creado exitosamente
        $media_container_id = $body['id'];

        // Esperar 30 segundos antes de publicar el contenedor
        sleep(30);

        // Ahora publicar el contenedor
        $publish_url = "https://graph.threads.net/v1.0/{$threads_user_id}/threads_publish?access_token={$access_token}";
        $publish_data = array(
            'creation_id' => $media_container_id,
        );

        $publish_response = wp_remote_post($publish_url, array(
            'method' => 'POST',
            'body' => $publish_data,
        ));

        if (is_wp_error($publish_response)) {
            return 'Error en la publicación: ' . $publish_response->get_error_message();
        }

        $publish_body = json_decode(wp_remote_retrieve_body($publish_response), true);

        if (isset($publish_body['id'])) {
            return 'Publicación exitosa en Threads con ID: ' . $publish_body['id'];
        } else {
            return 'Error al publicar en Threads.';
        }
    } else {
        return 'Error al crear el contenedor de medios en Threads.';
    }
}
*/