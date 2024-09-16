<?php

function aplicarFiltros($query_args, $args, $user_id, $current_user_id)
{
    $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];

    // Definimos las condiciones de los filtros.
    $meta_query_conditions = [
        'siguiendo' => function () use ($current_user_id, &$query_args) {
            $query_args['author__in'] = array_filter((array) get_user_meta($current_user_id, 'siguiendo', true));
            return ['key' => 'rola', 'value' => '1', 'compare' => '!='];
        },
        'con_imagen_sin_audio' => [
            ['key' => 'post_audio', 'compare' => 'NOT EXISTS'],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'solo_colab' => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'rolastatus' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['publish', 'pending'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'nada' => function () use (&$query_args) {
            $query_args['post_status'] = 'publish';
            return [];
        },
        'rolasEliminadas' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['pending_deletion'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'rolasRechazadas' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['rejected'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'no_bloqueado' => [
            ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!=']
        ],
        'likes' => function () use ($current_user_id, &$query_args) {
            $user_liked_post_ids = obtenerLikesDelUsuario($current_user_id);
            if (empty($user_liked_post_ids)) {
                $query_args['posts_per_page'] = 0;
                return null;
            }
            $query_args['post__in'] = $user_liked_post_ids;
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'bloqueado' => ['key' => 'esExclusivo', 'value' => '1', 'compare' => '='],
        'sample' => ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
        'venta' => ['key' => 'post_price', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'],
        'rola' => function () use (&$query_args) {
            $query_args['post_status'] = 'publish';
            return [
                ['key' => 'rola', 'value' => '1', 'compare' => '='],
                ['key' => 'post_audio', 'compare' => 'EXISTS']
            ];
        },
        'momento' => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'presentacion' => ['key' => 'additional_search_data', 'value' => 'presentacion010101', 'compare' => 'LIKE'],
    ];

    // Si el filtro existe, aplicamos la condición correspondiente.
    if (isset($meta_query_conditions[$filtro])) {
        $condition = $meta_query_conditions[$filtro];
        $query_args['meta_query'][] = is_callable($condition) ? $condition() : $condition;
    }

    // Aplicamos el filtro por autor si existe un $user_id
    if ($user_id !== null) {
        $query_args['author'] = $user_id;
    }

    return $query_args;
}