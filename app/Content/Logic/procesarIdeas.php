<?php

function procesarIdeas(array $args, int $paged): array|false
{
    try {
        if (empty($args['colec']) || !is_numeric($args['colec'])) {
            return false;
        }

        $samples_meta = get_post_meta($args['colec'], 'samples', true);
        $samples_meta = is_array($samples_meta) ? $samples_meta : maybe_unserialize($samples_meta);

        if (!is_array($samples_meta)) {
            return false;
        }

        $userId = get_current_user_id();
        $vistasUsuario = $userId ? get_user_meta($userId, 'vistas_posts', true) : [];
        $vistasUsuario = is_array($vistasUsuario) ? $vistasUsuario : [];

        $all_similar_posts = [];
        $post_weights = []; // Array para guardar el peso de cada post

        foreach ($samples_meta as $post_id) {
            // La caché está desactivada, así que siempre calculamos los similares
            $posts_similares = calcularFeedPersonalizado(44, '', $post_id);

            if (!$posts_similares) {
                continue;
            }

            // Si es un array, obtenemos las claves (IDs de los posts)
            if (is_array($posts_similares)) {
                $posts_similares = array_keys($posts_similares);
            }

            // Filtrar posts: el mismo post, los que ya están en $samples_meta y los que ya están en $all_similar_posts
            $posts_similares = array_diff($posts_similares, [$post_id], $samples_meta, $all_similar_posts);
            
            // Limitar a 5 posts similares
            $posts_similares = array_slice($posts_similares, 0, 5);
            
            // Añadir los posts similares al array general
            $all_similar_posts = array_merge($all_similar_posts, $posts_similares);
        }

        $all_similar_posts = array_unique($all_similar_posts);

        // Ajustar pesos basados en vistas
        foreach ($all_similar_posts as $post_id) {
            $view_count = isset($vistasUsuario[$post_id]) ? $vistasUsuario[$post_id]['count'] : 0;
            $post_weights[$post_id] = $view_count; // Inicializar el peso con el número de vistas
        }

        // Normalizar pesos para que los posts menos vistos tengan mayor probabilidad
        if (!empty($post_weights)) {
            $max_views = max($post_weights);
            foreach ($post_weights as $post_id => $view_count) {
                // A más vistas, menor peso. A menos vistas, mayor peso.
                // Sumamos 1 a $max_views para evitar división por cero en caso de que todos tengan 0 vistas.
                $post_weights[$post_id] = ($max_views - $view_count + 1) / ($max_views + 1);
            }
        }

        // Reordenar basado en pesos
        uksort($all_similar_posts, function ($a, $b) use ($post_weights) {
            // Generar un número aleatorio entre 0 y 1
            $randomA = mt_rand() / mt_getrandmax();
            $randomB = mt_rand() / mt_getrandmax();

            // Si el número aleatorio es menor que el peso, se considera "true" en la comparación
            $scoreA = ($randomA <= $post_weights[$a]) ? 1 : 0;
            $scoreB = ($randomB <= $post_weights[$b]) ? 1 : 0;

            return $scoreB - $scoreA; // Orden descendente basado en el score ajustado por el peso
        });

        if (count($all_similar_posts) > 620) {
            $all_similar_posts = array_slice($all_similar_posts, 0, 620);
        }

        return [
            'post_type'      => $args['post_type'],
            'post__in'       => $all_similar_posts,
            'orderby'        => 'post__in', // Se mantiene el orden basado en el array $all_similar_posts
            'posts_per_page' => 12,
            'paged'          => $paged,
        ];

    } catch (Exception $e) {
        return false;
    }
}