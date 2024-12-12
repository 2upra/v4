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

        $all_similar_posts = [];
        foreach ($samples_meta as $post_id) {
            $similar_to_cache_key = "similar_to_$post_id";
            $cached_similars = obtenerCache($similar_to_cache_key);

            if ($cached_similars) {
                arsort($cached_similars);
                $posts_similares = array_keys($cached_similars);
            } else {
                $posts_similares = calcularFeedPersonalizado(44, '', $post_id);

                if (!$posts_similares) {
                    continue;
                }

                if (is_array($posts_similares)) {
                    $posts_similares_ids = array_keys($posts_similares);
                    guardarCache($similar_to_cache_key, $posts_similares, 15 * DAY_IN_SECONDS);
                    $posts_similares = $posts_similares_ids;
                } else {
                    guardarCache($similar_to_cache_key, $posts_similares, 15 * DAY_IN_SECONDS);
                }
            }

            $posts_similares = array_diff($posts_similares, [$post_id], $samples_meta, $all_similar_posts);
            $posts_similares = array_slice($posts_similares, 0, 5);
            $all_similar_posts = array_merge($all_similar_posts, $posts_similares);
        }

        $all_similar_posts = array_unique($all_similar_posts);

        if (count($all_similar_posts) > 620) {
            $all_similar_posts = array_slice($all_similar_posts, 0, 620);
        }

        if (count($all_similar_posts) > 1) {
            $randomize_count = min(ceil(count($all_similar_posts) * 0.2), count($all_similar_posts));
            $random_indices = array_rand($all_similar_posts, $randomize_count);
            $random_indices = is_array($random_indices) ? $random_indices : [$random_indices];

            $random_posts = array_map(fn($index) => $all_similar_posts[$index], $random_indices);
            shuffle($random_posts);

            array_walk($random_indices, fn($index, $key) => $all_similar_posts[$index] = $random_posts[$key]);
        }

        return [
            'post_type'      => $args['post_type'],
            'post__in'       => $all_similar_posts,
            'orderby'        => 'post__in',
            'posts_per_page' => 12,
            'paged'          => $paged,
        ];

    } catch (Exception $e) {
        return false;
    }
}