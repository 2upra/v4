<?php

function aplicarFiltros($query_args, $args, $user_id, $current_user_id)
{
    $filtro = $args['identifier'] ?? $args['filtro'];

    $rolaEstado = function($status) use (&$query_args) {
        $query_args += [
            'author' => get_current_user_id(),
            'post_status' => [$status]
        ];
        $query_args['meta_query'][] = ['key' => 'rola', 'value' => '1', 'compare' => '='];
    };

    $meta_query_conditions = [

        /*
        FILTROS PARA PAGINA SELLO
        */
        'rolasEliminadas'  => fn() => $rolaEstado('pending_deletion'),
        'rolasRechazadas'  => fn() => $rolaEstado('rejected'),
        'rolasPendiente'   => fn() => $rolaEstado('pending'),

        /*
        FILTROS PAGINA DE MUSICA
        */
        'rola' => fn() => $query_args['meta_query'][] = [
            ['key' => 'rola', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio', 'compare' => 'EXISTS']
        ],

        'likesRolas' => fn() => ($user_liked_post_ids = obtenerLikesDelUsuario($current_user_id)) 
            ? $query_args['post__in'] = $user_liked_post_ids 
            : $query_args['posts_per_page'] = 0,

        /*
        FILTROS INICIO
        */
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

        /*
        FILTROS SAMPLES
        */
        'sample'   => ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],

        /*
        FILTROS COLAB
        */

        'colab' => fn() => $query_args['post_status'] = 'pending',
        'colabPendientes' => fn() => $query_args['meta_query'][] = [
            'author' => get_current_user_id(),
            'post_status' => ['pending']
        ],

    ];

    // Aplica el filtro si existe
    if (isset($meta_query_conditions[$filtro])) {
        $query_args['meta_query'][] = is_callable($meta_query_conditions[$filtro]) 
            ? $meta_query_conditions[$filtro]() 
            : $meta_query_conditions[$filtro];
    }

    // Si se proporciona un user_id, se filtra por autor
    if ($user_id !== null) {
        $query_args['author'] = $user_id;
    }

    return $query_args;
}