<?php

function aplicarFiltros($query_args, $args, $user_id, $current_user_id)
{
    $filtro = $args['identifier'] ?: $args['filtro'];

    // Si se proporciona un user_id, filtramos por autor
    if ($user_id !== null) {
        $query_args['author'] = $user_id;
    }

    switch ($filtro) {
        case 'siguiendo':
            $siguiendo = array_filter((array) get_user_meta($current_user_id, 'siguiendo', true));
            $query_args['author__in'] = $siguiendo;
            $query_args['meta_query'][] = [
                'key'     => 'rola',
                'value'   => '1',
                'compare' => '!=',
            ];
            break;

        case 'con_imagen_sin_audio':
            $query_args['meta_query'][] = [
                'key'     => 'post_audio',
                'compare' => 'NOT EXISTS',
            ];
            $query_args['meta_query'][] = [
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ];
            break;

        case 'solo_colab':
            $query_args['meta_query'][] = [
                'key'     => 'paraColab',
                'value'   => '1',
                'compare' => '=',
            ];
            break;

        case 'rolastatus':
            $query_args['author']      = $current_user_id;
            $query_args['post_status'] = ['publish', 'pending'];
            $query_args['meta_query'][] = [
                'key'     => 'rola',
                'value'   => '1',
                'compare' => '=',
            ];
            break;

        case 'rolasEliminadas':
            $query_args['author']      = $current_user_id;
            $query_args['post_status'] = ['pending_deletion'];
            $query_args['meta_query'][] = [
                'key'     => 'rola',
                'value'   => '1',
                'compare' => '=',
            ];
            break;

        case 'rolasRechazadas':
            $query_args['author']      = $current_user_id;
            $query_args['post_status'] = ['rejected'];
            $query_args['meta_query'][] = [
                'key'     => 'rola',
                'value'   => '1',
                'compare' => '=',
            ];
            break;

        case 'no_bloqueado':
            $query_args['meta_query'][] = [
                'key'     => 'esExclusivo',
                'value'   => '0',
                'compare' => '=',
            ];
            $query_args['meta_query'][] = [
                'key'     => 'post_price',
                'compare' => 'NOT EXISTS',
            ];
            $query_args['meta_query'][] = [
                'key'     => 'rola',
                'value'   => '1',
                'compare' => '!=',
            ];
            break;

        case 'likes':
            $user_liked_post_ids = obtenerLikesDelUsuario($current_user_id);
            if (empty($user_liked_post_ids)) {
                // Si no hay likes, no devolvemos resultados
                $query_args['posts_per_page'] = 0;
            } else {
                $query_args['post__in'] = $user_liked_post_ids;
                $query_args['meta_query'][] = [
                    'key'     => 'rola',
                    'value'   => '1',
                    'compare' => '=',
                ];
            }
            break;

        case 'bloqueado':
            $query_args['meta_query'][] = [
                'key'     => 'esExclusivo',
                'value'   => '1',
                'compare' => '=',
            ];
            break;

        case 'sample':
            $query_args['meta_query'][] = [
                'key'     => 'paraDescarga',
                'value'   => '1',
                'compare' => '=',
            ];
            break;

        case 'venta':
            $query_args['meta_query'][] = [
                'key'     => 'post_price',
                'value'   => '0',
                'compare' => '>',
                'type'    => 'NUMERIC',
            ];
            break;

        case 'rola':
            $query_args['post_status'] = 'publish';
            $query_args['meta_query'][] = [
                'key'     => 'rola',
                'value'   => '1',
                'compare' => '=',
            ];
            $query_args['meta_query'][] = [
                'key'     => 'post_audio',
                'compare' => 'EXISTS',
            ];
            break;

        case 'momento':
            $query_args['meta_query'][] = [
                'key'     => 'momento',
                'value'   => '1',
                'compare' => '=',
            ];
            $query_args['meta_query'][] = [
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ];
            break;

        case 'presentacion':
            $query_args['meta_query'][] = [
                'key'     => 'additional_search_data',
                'value'   => 'presentacion010101',
                'compare' => 'LIKE',
            ];
            break;

        // Si no hay filtro o no coincide, no aplicamos filtros adicionales
        default:
            break;
    }

    return $query_args;
}