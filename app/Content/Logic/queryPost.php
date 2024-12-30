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
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;

    //error_log("[publicacionAjax] Received identifier: " . $data_identifier);

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
            'id' => $id


        ),
        true,
        $paged
    );
}
add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');

function publicaciones($args = [], $isAjax = false, $paged = 1)
{
    try {
        $usu = get_current_user_id();
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
            'id' => '',
        ];

        if (!$isAjax && isset($_GET['busqueda'])) {
            $args['identifier'] = sanitize_text_field($_GET['busqueda']);
        }

        $userId = isset($args['user_id']) ? $args['user_id'] : '';
        $tipoUsuario = isset($args['tipoUsuario']) && !empty($args['tipoUsuario'])
            ? $args['tipoUsuario']
            : get_user_meta($usu, 'tipoUsuario', true);
        $args = array_merge($defaults, $args);
        $log = "Funcion publicaciones \n";
        if (!empty($args['id'])) {
            $log .= "Se procesara la publicacion con ID: " . $args['id'] . " \n";
            $queryArgs = [
                'post_type' => $args['post_type'],
                'p' => $args['id'],
            ];
        } else if (filter_var($args['idea'], FILTER_VALIDATE_BOOLEAN)) {
            $queryArgs = manejarIdea($args, $paged);
            if (!$queryArgs) {
                return false;
            }
        } else if (!empty($args['colec']) && is_numeric($args['colec'])) {
            $queryArgs = manejarColeccion($args, $paged);
            if (!$queryArgs) {
                return false;
            }
        } else {
            $queryArgs = configuracionQueryArgs($args, $paged, $userId, $usu, $tipoUsuario);
        }
        $colecciones = obtenerColeccionesParaMomento($args, $usu);
        $output = procesarPublicaciones($queryArgs, $args, $isAjax);
        if ($args['filtro'] === 'momento') {
            $output = $colecciones . $output;
        }

        if ($isAjax) {
            $log .= "Es una peticion ajax \n";
            echo $output;
            wp_die();
        }
        $log .= "Retornando output";
        guardarLog($log);
        return $output;
    } catch (Exception $e) {
        $log .= "Error: " . $e->getMessage();
        guardarLog($log);
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
            $queryArgs = [
                'post_type' => $args['post_type'],
                'posts_per_page' => $args['posts'],
                'paged' => $paged,
                'ignore_sticky_posts' => true,
                'suppress_filters' => false,
                'orderby' => 'date',
                'order' => 'DESC',
                'author' => $userId,
            ];

            $queryArgs = aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId);
            return $queryArgs;
        }

        $posts = $args['posts'];
        $similarTo = $args['similar_to'] ?? null;

        $filtroTiempo = (int)get_user_meta($usuarioActual, 'filtroTiempo', true);

        $queryArgs = preOrdenamiento($args, $paged, $usuarioActual, $identifier, $isAdmin, $posts, $filtroTiempo, $similarTo, $tipoUsuario);

        if ($args['post_type'] === 'social_post' && in_array($args['filtro'], ['sampleList', 'sample'])) {
            if ($tipoUsuario !== 'Fan') {
                $queryArgs = aplicarFiltrosUsuario($queryArgs, $usuarioActual);
            }
        }

        $queryArgs = aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario);


        return $queryArgs;
    } catch (Exception $e) {
        return false;
    }
}

function obtenerColeccionesParaMomento($args, $usuarioActual)
{
    $coleccionesOutput = '';
    if ($args['filtro'] === 'momento' && $args['tipoUsuario'] !== 'Fan') {
        $coleccionesQueryArgsForOrdering = [
            'post_type' => 'colecciones',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];
        $orderedColeccionesArgs = ordenamientoColecciones($coleccionesQueryArgsForOrdering, 'momento', $usuarioActual);

        if (!empty($orderedColeccionesArgs['post__in'])) {
            $topTwoColeccionesIds = array_slice($orderedColeccionesArgs['post__in'], 0, 6);

            if (!empty($topTwoColeccionesIds)) {
                $coleccionesQueryArgs = [
                    'post_type' => 'colecciones',
                    'post__in' => $topTwoColeccionesIds,
                    'orderby' => 'post__in',
                    'order' => 'ASC',
                    'post_status' => 'publish',
                    'posts_per_page' => 6,
                ];
                $coleccionesOutput = procesarPublicaciones($coleccionesQueryArgs, $args, false);
            }
        }
    }
    return $coleccionesOutput;
}

function preOrdenamiento($args, $paged, $usuarioActual, $identifier, $isAdmin, $posts, $filtroTiempo, $similarTo, $tipoUsuario = null)
{
    try {
        global $wpdb;
        if (!$wpdb) {
            return false;
        }
        $queryArgs = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'ignore_sticky_posts' => true,
            'suppress_filters' => false,
        ];

        if (!empty($identifier)) {
            $queryArgs = prefiltrarIdentifier($identifier, $queryArgs);
        }

        if ($args['post_type'] === 'social_post' && (!isset($args['filtro']) || !in_array($args['filtro'], ['rola', 'momento', 'tiendaPerfil', 'rolaListLike']))) {
            $queryArgs = ordenamiento($queryArgs, $filtroTiempo, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario);
        }

        if ($args['post_type'] === 'colecciones') {
            $queryArgs = ordenamientoColecciones($queryArgs, $filtroTiempo, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario);
        }

        if ($args['post_type'] === 'tarea') {
            if ($args['filtro'] === 'tareaPrioridad') {
                $queryArgs = ordenamientoTareasPorPrioridad($queryArgs, $usuarioActual);
            } else {
                $queryArgs = ordenamientoTareas($queryArgs, $usuarioActual, $args);
            }
        }

        return $queryArgs;
    } catch (Exception $e) {
        guardarLog("preOrdenamiento: Error" . $e->getMessage());
        return false;
    }
}



function ordenamientoColecciones($queryArgs, $filtroTiempo, $usuarioActual)
{
    global $wpdb;
    $likes_table = $wpdb->prefix . 'post_likes';

    $cache_key = 'colecciones_ordenadas_' . $usuarioActual . '_' . $filtroTiempo . '_' . mt_rand();
    $cached_data = obtenerCache($cache_key);

    if ($cached_data) {
        $queryArgs['post__in'] = $cached_data;
        $queryArgs['orderby'] = 'post__in';
        return $queryArgs;
    }

    $excluded_titles = ['Usar más tarde', 'Favoritos', 'test'];
    $excluded_ids = [];

    $title_conditions = array_map(function ($title) use ($wpdb) {
        return $wpdb->prepare("post_title LIKE %s", '%' . $wpdb->esc_like($title) . '%');
    }, $excluded_titles);
    $where_title = implode(' OR ', $title_conditions);

    if ($usuarioActual) {
        $excluded_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND ({$where_title}) AND post_author != %d",
                $usuarioActual
            )
        );
    } else {
        $excluded_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND ({$where_title})"
        );
    }

    $interval = 30;
    $popular_ids = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT p.ID, COUNT(pl.post_id) as like_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$likes_table} pl ON p.ID = pl.post_id
            WHERE p.post_type = 'colecciones'
            AND p.post_status = 'publish'
            AND pl.like_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY p.ID
            ORDER BY like_count DESC, p.post_date DESC",
            $interval
        ),
        ARRAY_A
    );

    $all_ids = [];
    $popular_ids_list = [];
    $weights = [];

    foreach ($popular_ids as $post) {
        $popular_ids_list[] = $post['ID'];
        $weights[$post['ID']] = $post['like_count'] * 2;
    }

    $excluded_ids = is_array($excluded_ids) ? $excluded_ids : [];
    $popular_ids_list = is_array($popular_ids_list) ? $popular_ids_list : [];

    if (empty($excluded_ids) && empty($popular_ids_list)) {
        $all_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish'"
        );
    } else {
        $placeholder = implode(',', array_fill(0, count(array_merge($excluded_ids, $popular_ids_list)), '%d'));
        $all_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'colecciones' AND post_status = 'publish' AND ID NOT IN ({$placeholder})",
                array_merge($excluded_ids, $popular_ids_list)
            )
        );
        $all_ids = array_unique(array_merge($popular_ids_list, $all_ids));
    }

    foreach ($all_ids as $id) {
        if (!isset($weights[$id])) {
            $weights[$id] = 1;
        }
    }

    function weighted_random_select($weighted_items)
    {
        $suma = array_sum($weighted_items);
        $rand = mt_rand(1, $suma);
        $acumulado = 0;
        foreach ($weighted_items as $item => $peso) {
            $acumulado += $peso;
            if ($rand <= $acumulado) {
                return $item;
            }
        }
    }

    $ordered_ids = [];
    $temp_weights = $weights;
    while (count($ordered_ids) < count($all_ids)) {
        $selected_id = weighted_random_select($temp_weights);
        $ordered_ids[] = $selected_id;
        unset($temp_weights[$selected_id]);
    }

    guardarCache($cache_key, $ordered_ids, 3600);

    $queryArgs['post__in'] = $ordered_ids;
    $queryArgs['orderby'] = 'post__in';
    $queryArgs['post__not_in'] = $excluded_ids;

    return $queryArgs;
}

function ordenamientoTareasPorPrioridad($queryArgs, $usu)
{
    global $wpdb;
    $tareasPendientes = [];
    $tareasNoPendientes = [];
    $log = "ordenamientoTareasPorPrioridad: \n";
    
    $todasTareasArgs = [
        'post_type'      => 'tarea',
        'author'         => $usu,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $todasTareas = get_posts($todasTareasArgs);

    if (empty($todasTareas) || !is_array($todasTareas)) {
        $log .= "No hay tareas para actualizar el orden del usuario $usu";
        guardarLog($log);
        return $queryArgs;
    }

    foreach ($todasTareas as $tareaId) {
        $estado = get_post_meta($tareaId, 'estado', true);
        if ($estado === 'pendiente') {
            $tareasPendientes[] = $tareaId;
        } else {
            $tareasNoPendientes[] = $tareaId;
        }
    }

    usort($tareasPendientes, function ($a, $b) use ($wpdb) {
        $impnumA = get_post_meta($a, 'impnum', true);
        $impnumB = get_post_meta($b, 'impnum', true);
        return $impnumB - $impnumA; // Ordenar de mayor a menor
    });

    $tareasOrdenadas = array_merge($tareasPendientes, $tareasNoPendientes);

    update_user_meta($usu, 'ordenTareas', $tareasOrdenadas);
    $log .= "Se ha actualizado el orden de todas las tareas del usuario $usu \n";
    $log .= "Nuevo orden de todas las tareas: " . implode(", ", $tareasOrdenadas);

    $queryArgs['post__in'] = $tareasOrdenadas;
    $queryArgs['orderby'] = 'post__in'; 
    unset($queryArgs['meta_key']);
    unset($queryArgs['meta_query']);
    unset($queryArgs['order']);
    unset($queryArgs['meta_key']);
    unset($queryArgs['orderby']);
    
    guardarLog($log);
    return $queryArgs;
}

function ordenamientoTareas($queryArgs, $usu, $args)
{
    $orden = get_user_meta($usu, 'ordenTareas', true);

    if (!is_array($orden)) {
        $orden = [];
    }

    $todasTareasArgs = [
        'post_type'      => 'tarea',
        'author'         => $usu,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    $todasTareas = get_posts($todasTareasArgs);

    $log = "ordenamientoTareas: \n";
    $log .= "  Usuario: $usu, \n";
    $log .= "  Orden actual: " . (empty($orden) ? "Vacío" : implode(", ", $orden)) . ", \n";
    $log .= "  IDs de todas las tareas: " . (empty($todasTareas) ? "Ninguna" : implode(", ", $todasTareas));

    if (!empty($todasTareas) && is_array($todasTareas)) {
        $nuevasTareas = array_diff($todasTareas, $orden);

        if (!empty($nuevasTareas)) {
            $orden = array_merge($nuevasTareas, $orden);

            $log .= ", \n  Nuevas tareas: " . implode(", ", $nuevasTareas);
        }

        $validas = [];
        $completadas = [];
        $incompletas = [];
        $archivadas = [];

        foreach ($orden as $id) {
            $post = get_post($id);

            $log .= ", \n  Tarea ID: $id";

            if (!empty($post)) {
                $estado = get_post_meta($id, 'estado', true);
                $log .= ", Estado: " . (empty($estado) ? "Vacío" : $estado) . ", Post Status: " . $post->post_status;

                if ($post->post_status !== 'publish' || $estado === 'completada') {
                    $completadas[] = $id;
                    $log .= " (Agregada a completadas)";
                } elseif ($estado === 'archivado') {
                    $archivadas[] = $id;
                    $log .= " (Agregada a archivadas)";
                } else {
                    $incompletas[] = $id;
                    $log .= " (Agregada a incompletas)";
                }
                $validas[] = $id;
            } else {
                $log .= ", Post no encontrado (Agregada a completadas)";
                $completadas[] = $id;
            }
        }

        $log .= ", \n  Incompletas: " . (empty($incompletas) ? "Ninguna" : implode(", ", $incompletas));
        $log .= ", \n  Completadas: " . (empty($completadas) ? "Ninguna" : implode(", ", $completadas));
        $log .= ", \n  Archivadas: " . (empty($archivadas) ? "Ninguna" : implode(", ", $archivadas));

        $ordenFinal = array_merge($incompletas, $completadas, $archivadas);

        $log .= ", \n  Orden propuesto: " . implode(", ", $ordenFinal);

        if ($ordenFinal !== $orden) {
            update_user_meta($usu, 'ordenTareas', $ordenFinal);
            $log .= ", \n  Se actualizaron las IDs de ordenTareas para el usuario $usu,  Nuevo orden: " . implode(", ", $ordenFinal);
        } else {
            $log .= ", \n  El orden de las tareas no ha cambiado";
        }

        if (count($validas) !== count($orden)) {
            $log .= ", \n  IDs inválidas encontradas en el orden, \n  Nuevo orden válido: " . implode(", ", $validas);
        }

        $queryArgs['post__in'] = $validas;
        $queryArgs['orderby'] = 'post__in';
    } else {
        $log .= ", \n  No hay tareas para el usuario $usu";
    }

    //guardarLog($log);
    return $queryArgs;
}

function ordenamiento($queryArgs, $filtroTiempo, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario = null)
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
                $queryArgs['post__in'] = $feed_result['post_ids'];
                $queryArgs['orderby'] = 'post__in';

                if (count($feed_result['post_ids']) > POSTINLIMIT) {
                    $feed_result['post_ids'] = array_slice($feed_result['post_ids'], 0, POSTINLIMIT);
                }

                if (!empty($feed_result['post_not_in'])) {
                    $queryArgs['post__not_in'] = $feed_result['post_not_in'];
                }
            } else {
                // Si el feed personalizado está vacío, usar ordenamiento por fecha por defecto
                $queryArgs['orderby'] = 'date';
                $queryArgs['order'] = 'DESC';
            }

            return $queryArgs;
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
            //return $queryArgs; // Si pide default, regresar la query sin modificaciones
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
        if (!is_array($queryArgs)) {
            $queryArgs = array();
        }

        switch ($filtroTiempo) {
            case 1: // Recientes
                //error_log("[ordenamiento] caso reciente!!");
                $queryArgs['orderby'] = 'date';
                $queryArgs['order'] = 'DESC';
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
                        $queryArgs['post__in'] = $post_ids;
                        $queryArgs['orderby'] = 'post__in';
                    }
                } else {
                    $queryArgs['orderby'] = 'date';
                    $queryArgs['order'] = 'DESC';
                }
                break;

            default: // Feed personalizado
                //error_log("[ordenamiento] caso default!");

                $feed_result = obtenerFeedPersonalizado($usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario, $filtrosUsuario);

                if (!empty($feed_result['post_ids'])) {
                    $queryArgs['post__in'] = $feed_result['post_ids'];
                    $queryArgs['orderby'] = 'post__in';

                    if (count($feed_result['post_ids']) > POSTINLIMIT) {
                        $feed_result['post_ids'] = array_slice($feed_result['post_ids'], 0, POSTINLIMIT);
                    }

                    if (!empty($feed_result['post_not_in'])) {
                        $queryArgs['post__not_in'] = $feed_result['post_not_in'];
                    }
                } else {
                    $queryArgs['orderby'] = 'date';
                    $queryArgs['order'] = 'DESC';
                }

                break;
        }

        if (empty($queryArgs['orderby'])) {
            $queryArgs['orderby'] = 'date';
            $queryArgs['order'] = 'DESC';
        }

        return $queryArgs;
    } catch (Exception $e) {
        return false;
    }
}

function aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario = null)
{
    if (!empty($userId)) {
        $queryArgs['author'] = $userId;
        $filtro = $args['filtro'] ?? 'nada';
        if ($filtro === 'imagenesPerfil') {
            $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
                ['key' => 'post_audio_lite', 'compare' => 'NOT EXISTS'],
            ]);
        } elseif ($filtro === 'tiendaPerfil') {
            $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                ['key' => 'tienda', 'value' => '1', 'compare' => '='],
                ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
            ]);
        }

        return $queryArgs;
    }

    $filtrosUsuario = get_user_meta($usuarioActual, 'filtroPost', true);
    $filtro = $args['filtro'] ?? 'nada';

    if ($filtro === 'sampleList' && is_array($filtrosUsuario) && in_array('misPost', $filtrosUsuario)) {
        $queryArgs['author'] = $usuarioActual;
    }

    if ($filtro === 'notas') {
        $queryArgs['author'] = $usuarioActual;
    }

    if ($filtro === 'colecciones' && is_array($filtrosUsuario) && in_array('misColecciones', $filtrosUsuario)) {
        $queryArgs['author'] = $usuarioActual;
    }

    if ($filtro === 'tarea' || $filtro === 'tareaPrioridad') {
        $queryArgs['author'] = $usuarioActual;
        if (is_array($filtrosUsuario) && in_array('ocultarCompletadas', $filtrosUsuario)) {
            $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                [
                    'key' => 'estado',
                    'value' => 'completada',
                    'compare' => '!=',
                ]
            ]);
        }
    }

    $metaQueryConditions = [
        'rolasEliminadas' => fn() => $queryArgs['post_status'] = 'pending_deletion',
        'rolasRechazadas' => fn() => $queryArgs['post_status'] = 'rejected',
        'rolasPendiente' => fn() => $queryArgs['post_status'] = 'pending',
        'likesRolas' => fn() => ($userLikedPostIds = obtenerLikesDelUsuario($usuarioActual))
            ? $queryArgs['post__in'] = $userLikedPostIds
            : $queryArgs['posts_per_page'] = 0,
        'nada' => fn() => $queryArgs['post_status'] = 'publish',
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
        'sample' => function () use ($tipoUsuario, &$queryArgs) {
            if ($tipoUsuario === 'Fan') {
                $queryArgs['post_status'] = 'publish';
            } else {
                $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                    ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                    ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
                ]);
            }
        },
        'rolaListLike' => function () use ($usuarioActual, &$queryArgs) {
            $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
            if (empty($userLikedPostIds)) {
                $queryArgs['posts_per_page'] = 0;
                return;
            }

            $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                'relation' => 'AND',
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

            $queryArgs['post__in'] = $userLikedPostIds;
        },
        'sampleList' => [
            'relation' => 'AND',
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
            [
                'relation' => 'OR',
                ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                ['key' => 'tienda', 'value' => '1', 'compare' => '='],
            ],
        ],
        'colab' => fn() => $queryArgs['post_status'] = 'publish',
        'colabPendiente' => function () use (&$queryArgs) {
            $queryArgs['author'] = get_current_user_id();
            $queryArgs['post_status'] = 'pending';
        },
        'rola' => [
            ['key' => 'rola', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],

        ],
    ];

    if (isset($metaQueryConditions[$filtro])) {
        $result = $metaQueryConditions[$filtro];

        if (is_callable($result)) {
            $result();
        } else {
            $queryArgs['post_status'] = 'publish';
            $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], $result);
        }
    }

    return $queryArgs;
}



function aplicarFiltrosUsuario($queryArgs, $usuarioActual)
{
    //guardarLog("Iniciando aplicarFiltrosUsuario para el usuario $usuarioActual");
    $filtrosUsuario = get_user_meta($usuarioActual, 'filtroPost', true);

    //guardarLog("Filtros del usuario: " . print_r($filtrosUsuario, true));

    if (empty($filtrosUsuario) || !is_array($filtrosUsuario)) {
        //guardarLog("No hay filtros aplicables o el formato es incorrecto.");
        return $queryArgs;
    }

    // Inicializar variables para mantener los IDs a incluir y excluir
    $post_not_in = $queryArgs['post__not_in'] ?? [];
    $post_in = $queryArgs['post__in'] ?? [];

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
                $queryArgs['posts_per_page'] = 0;
                //guardarLog("No hay posts que mostrar después de aplicar mostrarMeGustan.");
            }
        } else {
            $queryArgs['posts_per_page'] = 0;
            //guardarLog("No hay posts que le gusten al usuario, posts_per_page se establece en 0.");
        }
    }

    // Eliminar los IDs en post_not_in de post_in para evitar conflictos
    if (!empty($post_in) && !empty($post_not_in)) {
        $post_in = array_diff($post_in, $post_not_in);
        //guardarLog("Post__in después de eliminar IDs en post__not_in: " . print_r($post_in, true));

        if (empty($post_in)) {
            $queryArgs['posts_per_page'] = 0;
            //guardarLog("No hay posts que mostrar después de aplicar los filtros.");
        }
    }


    if (!empty($post_in)) {
        $queryArgs['post__in'] = $post_in;
    } else {
        unset($queryArgs['post__in']);
    }

    if (!empty($post_not_in)) {
        $queryArgs['post__not_in'] = $post_not_in;
    } else {
        unset($queryArgs['post__not_in']);
    }

    return $queryArgs;
}


function prefiltrarIdentifier($identifier, $queryArgs)
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
    $queryArgs['s'] = $identifier;

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

    return $queryArgs;
}

function procesarPublicaciones($queryArgs, $args, $is_ajax)
{
    ob_start();
    $userId = get_current_user_id();

    if (empty($queryArgs) || !is_array($queryArgs)) {
        return '';
    }

    try {
        $query = new WP_Query($queryArgs);
        if (!is_a($query, 'WP_Query') || !is_object($query) || !method_exists($query, 'have_posts')) {
            return '';
        }
    } catch (Exception $e) {
        return '';
    }


    $filtro = !empty($args['filtro']) ? $args['filtro'] : '';
    $tipoPost = $args['post_type'];

    if ($query->have_posts()) { // Abrimos <ul> solo si hay posts
        $claseExtra = '';

        if (!wp_doing_ajax()) {
            $claseExtra = 'clase-' . esc_attr($filtro);
            if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
                $claseExtra = 'clase-rolastatus';
            }

            // Agregar la clase "masonary" si el filtro es "notas"
            if ($filtro === 'notas') {
                $claseExtra .= ' masonary';
            }

            echo '<ul class="social-post-list ' . esc_attr($claseExtra) . '" 
                  data-filtro="' . esc_attr($filtro) . '" 
                  data-posttype="' . esc_attr($tipoPost) . '" 
                  data-tab-id="' . esc_attr($args['tab_id']) . '">';
        }

        if ($filtro === 'notas') {
            echo formNotas();
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
                case 'tarea':
                    echo htmlTareas($filtro);
                    break;
                case 'notas':
                    echo htmlNotas($filtro);
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
    } else { // Si no hay posts
        if ($filtro === 'notas') {
            echo formNotasUL();
        } else {
            echo nohayPost($filtro, $is_ajax);
        }
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