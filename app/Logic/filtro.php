<?

function aplicarFiltros($query_args, $args, $user_id, $current_user_id)
{
    $filtro = $args['identifier'] ?? $args['filtro'];
    postLog("---------------------------------------");
    postLog("INICIO FILTRO EN APLICARFILTROS: $filtro");

    $rolaEstado = function ($status) use (&$query_args) {
        $query_args['author'] = get_current_user_id();
        $query_args['post_status'] = $status;
        $query_args['meta_query'][] = ['key' => 'rola', 'value' => '1', 'compare' => '='];
    };

    $meta_query_conditions = [
        'rolasEliminadas'  => fn() => $rolaEstado('pending_deletion'),
        'rolasRechazadas'  => fn() => $rolaEstado('rejected'),
        'rolasPendiente'   => fn() => $rolaEstado('pending'),
        'rola' => fn() => $query_args['meta_query'][] = [
            ['key' => 'rola', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio', 'compare' => 'EXISTS']
        ],
        'likesRolas' => fn() => ($user_liked_post_ids = obtenerLikesDelUsuario($current_user_id))
            ? $query_args['post__in'] = $user_liked_post_ids
            : $query_args['posts_per_page'] = 0,
        'nada'     => fn() => $query_args['post_status'] = 'publish',
        'colabs'   => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'libres'   => [
            ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!=']
        ],
        'momento'  => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'sample'   => [
            ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ],
        'sampleList'   => ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
        'colab' => fn() => $query_args['post_status'] = 'publish',
        'colabPendiente' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = 'pending';
        },
    ];

    if (isset($meta_query_conditions[$filtro])) {
        $result = $meta_query_conditions[$filtro];
        if (is_callable($result)) {
            $result();
        } else {
            $query_args['meta_query'][] = $result;
        }
    }

    if ($user_id !== null) {
        $query_args['author'] = $user_id;
    }

    postLog("FINAL FILTRO EN APLICARFILTROS: $filtro");
    postLog("---------------------------------------");
    return $query_args;
}
