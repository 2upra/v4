<?

function aplicarFiltroPorAutor($queryArgs, $userId, $filtro)
{
    $queryArgs['author'] = $userId;
    $metaQuery = $queryArgs['meta_query'] ?? [];

    if ($filtro === 'imagenesPerfil') {
        $metaQuery = array_merge($metaQuery, [
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
            ['key' => 'post_audio_lite', 'compare' => 'NOT EXISTS'],
        ]);
    } elseif ($filtro === 'tiendaPerfil') {
        $metaQuery = array_merge($metaQuery, [
            ['key' => 'tienda', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ]);
    }

    $queryArgs['meta_query'] = $metaQuery;
    return $queryArgs;
}

function aplicarFiltrosDeUsuario($queryArgs, $usu, $filtro)
{
    $filtrosUsuario = get_user_meta($usu, 'filtroPost', true);

    if (in_array($filtro, ['sampleList', 'notas', 'colecciones'])) {
        if ($filtro === 'sampleList' && is_array($filtrosUsuario) && in_array('misPost', $filtrosUsuario)) {
            $queryArgs['author'] = $usu;
        } elseif ($filtro === 'notas') {
            $queryArgs['author'] = $usu;
        } elseif ($filtro === 'colecciones' && is_array($filtrosUsuario) && in_array('misColecciones', $filtrosUsuario)) {
            $queryArgs['author'] = $usu;
        }
    }
    if (($filtro === 'tarea' || $filtro === 'tareaPrioridad') && is_array($filtrosUsuario) && in_array('ocultarCompletadas', $filtrosUsuario)) {
        $queryArgs['author'] = $usu;
        $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
            [
                'key' => 'estado',
                'value' => 'completada',
                'compare' => '!=',
            ],
           /* [
                'key' => 'estado',
                'value' => 'archivado',
                'compare' => '!=',
            ] */
        ]);
    }

    return $queryArgs;
}

function aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario)
{
    $metaQueryConditions = obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario);

    if (isset($metaQueryConditions[$filtro])) {
        $result = $metaQueryConditions[$filtro];
        if (is_callable($result)) {
            $result($queryArgs);
        } else {
            $queryArgs['post_status'] = 'publish';
            $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], $result);
        }
    }

    return $queryArgs;
}

function obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario)
{
    return [
        'rolasEliminadas' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'pending_deletion';
        },
        'rolasRechazadas' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'rejected';
        },
        'rolasPendiente' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'pending';
        },
        'likesRolas' => function (&$queryArgs) use ($usuarioActual) {
            $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
            if ($userLikedPostIds) {
                $queryArgs['post__in'] = $userLikedPostIds;
            } else {
                $queryArgs['posts_per_page'] = 0;
            }
        },
        'nada' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'publish';
        },
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
        'sample' => function (&$queryArgs) use ($tipoUsuario) {
            if ($tipoUsuario === 'Fan') {
                $queryArgs['post_status'] = 'publish';
            } else {
                $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                    ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                    ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
                ]);
            }
        },
        'rolaListLike' => function (&$queryArgs) use ($usuarioActual) {
            $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
            if (!empty($userLikedPostIds)) {
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
            } else {
                $queryArgs['posts_per_page'] = 0;
            }
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
        'colab' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'publish';
        },
        'colabPendiente' => function (&$queryArgs) {
            $queryArgs['author'] = get_current_user_id();
            $queryArgs['post_status'] = 'pending';
        },
        'rola' => [
            ['key' => 'rola', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ],
    ];
}

function aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario = null)
{
    $log = "Inicio aplicarFiltroGlobal \n";
    $filtro = $args['filtro'] ?? 'nada';
    $log .= "Filtro recibido: $filtro \n";
    if (!empty($userId)) {
        $log .= "Aplicando filtro por autor, userId: $userId \n";
        $queryArgs = aplicarFiltroPorAutor($queryArgs, $userId, $filtro);
    } else {
        $log .= "Aplicando filtros de usuario, usuarioActual: $usuarioActual \n";
        $queryArgs = aplicarFiltrosDeUsuario($queryArgs, $usuarioActual, $filtro);
        $log .= "Aplicando condiciones de meta query, filtro: $filtro, tipoUsuario: $tipoUsuario \n";
        $queryArgs = aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario);
    }
    guardarLog("aplicarFiltroGlobal, $log, queryArgs resultantes: " . print_r($queryArgs, true));
    return $queryArgs;
}
