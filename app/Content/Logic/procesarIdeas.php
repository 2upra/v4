<?php

function procesarIdeas(array $args, int $paged): array|false
{
    try {
        error_log("Inicio de la función procesarIdeas");
        error_log("Argumentos recibidos: " . print_r($args, true));
        error_log("Página actual: " . $paged);

        if (empty($args['colec']) || !is_numeric($args['colec'])) {
            error_log("Error: El ID de la colección no es válido o está vacío.");
            return false;
        }

        $samples_meta = get_post_meta($args['colec'], 'samples', true);
        error_log("Valor de samples_meta obtenido: " . print_r($samples_meta, true));
        $samples_meta = is_array($samples_meta) ? $samples_meta : maybe_unserialize($samples_meta);
        error_log("Valor de samples_meta después de maybe_unserialize: " . print_r($samples_meta, true));

        if (!is_array($samples_meta)) {
            error_log("Error: samples_meta no es un array después de unserialize.");
            return false;
        }

        $userId = get_current_user_id();
        error_log("ID de usuario actual: " . $userId);
        $vistasUsuario = $userId ? get_user_meta($userId, 'vistas_posts', true) : [];
        error_log("Vistas del usuario obtenidas: " . print_r($vistasUsuario, true));
        $vistasUsuario = is_array($vistasUsuario) ? $vistasUsuario : [];
        error_log("Vistas del usuario después de la verificación de array: " . print_r($vistasUsuario, true));

        $all_similar_posts = [];
        $post_weights = [];

        foreach ($samples_meta as $post_id) {
            error_log("Procesando post_id: " . $post_id);
            $posts_similares = calcularFeedPersonalizado(44, '', $post_id);
            error_log("Posts similares obtenidos para " . $post_id . ": " . print_r($posts_similares, true));

            if (!$posts_similares) {
                error_log("No se encontraron posts similares para " . $post_id . ", continuando con el siguiente.");
                continue;
            }

            if (is_array($posts_similares)) {
                $posts_similares = array_keys($posts_similares);
                error_log("IDs de posts similares después de obtener las claves: " . print_r($posts_similares, true));
            }

            $posts_similares = array_diff($posts_similares, [$post_id], $samples_meta, $all_similar_posts);
            error_log("Posts similares después de filtrar: " . print_r($posts_similares, true));

            $posts_similares = array_slice($posts_similares, 0, 5);
            error_log("Posts similares después de limitar a 5: " . print_r($posts_similares, true));

            $all_similar_posts = array_merge($all_similar_posts, $posts_similares);
            error_log("Posts similares totales hasta ahora: " . print_r($all_similar_posts, true));
        }

        $all_similar_posts = array_unique($all_similar_posts);
        error_log("Posts similares totales únicos: " . print_r($all_similar_posts, true));

        foreach ($all_similar_posts as $post_id) {
            $view_count = isset($vistasUsuario[$post_id]) ? $vistasUsuario[$post_id]['count'] : 0;
            $post_weights[$post_id] = $view_count;
            error_log("Peso inicial para post_id " . $post_id . " basado en vistas: " . $view_count);
        }

        if (!empty($post_weights)) {
            $max_views = max($post_weights);
            error_log("Máximo número de vistas entre los posts: " . $max_views);
            foreach ($post_weights as $post_id => $view_count) {
                $post_weights[$post_id] = ($max_views - $view_count + 1) / ($max_views + 1);
                error_log("Peso ajustado para post_id " . $post_id . ": " . $post_weights[$post_id]);
            }
        }

        uksort($all_similar_posts, function ($a, $b) use ($post_weights) {
            $randomA = mt_rand() / mt_getrandmax();
            $randomB = mt_rand() / mt_getrandmax();
            error_log("Números aleatorios generados para " . $a . " y " . $b . ": " . $randomA . ", " . $randomB);

            $scoreA = ($randomA <= $post_weights[$a]) ? 1 : 0;
            $scoreB = ($randomB <= $post_weights[$b]) ? 1 : 0;
            error_log("Scores ajustados para " . $a . " y " . $b . ": " . $scoreA . ", " . $scoreB);

            return $scoreB - $scoreA;
        });
        error_log("Posts similares después de reordenar: " . print_r($all_similar_posts, true));

        if (count($all_similar_posts) > 620) {
            $all_similar_posts = array_slice($all_similar_posts, 0, 620);
            error_log("Posts similares después de limitar a 620: " . print_r($all_similar_posts, true));
        }

        $result = [
            'post_type'      => $args['post_type'],
            'post__in'       => $all_similar_posts,
            'orderby'        => 'post__in',
            'posts_per_page' => 12,
            'paged'          => $paged,
        ];

        error_log("Resultado final: " . print_r($result, true));
        error_log("Fin de la función procesarIdeas");

        return $result;

    } catch (Exception $e) {
        error_log("Excepción capturada en procesarIdeas: " . $e->getMessage());
        return false;
    }
}