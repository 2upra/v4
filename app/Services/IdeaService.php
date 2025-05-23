<?php
// Servicio para manejar la lógica relacionada con las 'Ideas' (posts similares a colecciones).

// Refactor(Org): Mueve función procesarIdeas() de app/Content/Logic/procesarIdeas.php a app/Services/IdeaService.php
function procesarIdeas($args, $paged)
{
    try {
        //error_log("[procesarIdeas] Iniciando procesamiento con args: (oculto)");

        // Validar que 'colec' es un número válido
        if (empty($args['colec']) || !is_numeric($args['colec'])) {
            //error_log("[procesarIdeas] 'colec' no es válido. Valor recibido: (oculto)");
            return false;
        }

        //error_log("[procesarIdeas] 'colec' es válido: " . $args['colec']);

        // Obtener meta 'samples' del post
        $samples_meta = get_post_meta($args['colec'], 'samples', true);
        //error_log("[procesarIdeas] Obtención de meta 'samples' para colec {$args['colec']}");

        if (!is_array($samples_meta)) {
            $samples_meta = maybe_unserialize($samples_meta);
            //error_log("[procesarIdeas] Intentando deserializar 'samples'");
        }

        if (is_array($samples_meta)) {
            //error_log("[procesarIdeas] 'samples_meta' es un array con " . count($samples_meta) . " elementos.");
            $all_similar_posts = [];
            foreach ($samples_meta as $post_id) {
                //error_log("[procesarIdeas] Procesando post_id: $post_id");

                // Usar cache para obtener posts similares
                $similar_to_cache_key = "similar_to_$post_id";
                $cached_similars = obtenerCache($similar_to_cache_key);

                // Log adicional para inspeccionar el contenido del cache
                //error_log("[procesarIdeas] Cache obtenido para post_id $post_id");

                if ($cached_similars) {
                    // Ensure the cached similars are sorted if needed
                    arsort($cached_similars);

                    // Extract post IDs from the cached similars
                    $posts_similares = array_keys($cached_similars);

                    //error_log("[procesarIdeas] Usando cache para post_id $post_id. Posts similares obtenidos");
                } else {
                    //error_log("[procesarIdeas] Cache no encontrado para post_id $post_id. Calculando posts similares.");
                    $posts_similares = calcularFeedPersonalizado(44, '', $post_id);

                    if ($posts_similares) {
                        // If calcularFeedPersonalizado returns an associative array, extract the keys
                        if (is_array($posts_similares)) {
                            $posts_similares_ids = array_keys($posts_similares);
                            guardarCache($similar_to_cache_key, $posts_similares, 15 * DAY_IN_SECONDS);
                            //error_log("[procesarIdeas] Cache guardado para post_id $post_id");
                            $posts_similares = $posts_similares_ids; // Update for consistency
                        } else {
                            // Handle case where it returns an indexed array of post IDs
                            guardarCache($similar_to_cache_key, $posts_similares, 15 * DAY_IN_SECONDS);
                            //error_log("[procesarIdeas] Cache guardado para post_id $post_id con posts");
                        }
                    } else {
                        //error_log("[procesarIdeas] No se pudieron calcular posts similares para post_id $post_id.");
                        continue;
                    }
                }

                // Excluir repetidos y los mismos samples
                $original_count = count($posts_similares);
                $posts_similares = array_diff($posts_similares, [$post_id], $samples_meta, $all_similar_posts);
                $filtered_count = count($posts_similares);
                //error_log("[procesarIdeas] Filtrados posts similares para post_id $post_id. Antes: $original_count, Después: $filtered_count");

                // Asegurar que tengamos al menos 5 posts similares
                $posts_similares = array_slice($posts_similares, 0, 10);
                //error_log("[procesarIdeas] Limitados a 5 posts similares para post_id $post_id");

                 // Añadir a la lista total de posts
                 $all_similar_posts = array_merge($all_similar_posts, $posts_similares);
                 //error_log("[procesarIdeas] Total de posts similares acumulados");
            }

            // Eliminar duplicados y limitar a 620 posts
            $prev_unique_count = count($all_similar_posts);
            $all_similar_posts = array_unique($all_similar_posts);
            $unique_count = count($all_similar_posts);
            //error_log("[procesarIdeas] Eliminados duplicados. Antes: $prev_unique_count, Después: $unique_count");

             if ($unique_count > 620) {
                 $all_similar_posts = array_slice($all_similar_posts, 0, 620);
                  //error_log("[procesarIdeas] Limitados a 620 posts (después de eliminar duplicados)");
             }

             // Aplicar puntuación y ordenamiento por vistas
             // Refactor(Org): Función movida a app/AlgoritmoPost/calcularPuntos.php
             $all_similar_posts_scored = asignarPuntuacionPorVistas($all_similar_posts);

            if (count($all_similar_posts_scored) > 1) {
                $total_posts = count($all_similar_posts_scored);
                $randomize_count = ceil($total_posts * 0.2);
                //error_log("[procesarIdeas] Aplicando aleatoriedad. Total posts: $total_posts, Cantidad a randomizar: $randomize_count");
            
                if ($randomize_count > 1) {
                    $keys = array_keys($all_similar_posts_scored);
                    $random_indices = array_rand($keys, $randomize_count);
            
                    if (!is_array($random_indices)) {
                        $random_indices = [$random_indices];
                    }
            
                    $random_posts = [];
                    foreach ($random_indices as $index) {
                        $random_posts[] = $keys[$index];
                    }
                    shuffle($random_posts);
                   //error_log("[procesarIdeas] Posts seleccionados para aleatorizar: (oculto)");
            
            
                    $i = 0;
                    foreach ($random_indices as $index) {
                        $all_similar_posts_scored[$keys[$index]] = $all_similar_posts_scored[$random_posts[$i]];
                        $i++;
                    }
                  
                }
            }
             // Extraer los IDs de los posts ordenados
            
            $all_similar_posts_sorted = array_keys($all_similar_posts_scored);
            //error_log("[procesarIdeas] Posts después de ordenar por vistas y aleatorizar: (oculto)");
            
            //error_log("[procesarIdeas] Total final de posts similares: " . count($all_similar_posts_sorted));

            // Configurar argumentos de la consulta
            $query_args = [
                'post_type'      => $args['post_type'],
                'post__in'       => $all_similar_posts_sorted,
                'orderby'        => 'post__in',
                'posts_per_page' => 12,
                'paged'          => $paged,
            ];
            
            //error_log("[procesarIdeas] Query args configurados: (oculto)");

            return $query_args;
        } else {
            //error_log("[procesarIdeas] El meta 'samples' no es un array válido. Valor recibido: (oculto)");
            return false;
        }
    } catch (Exception $e) {
        //error_log("[procesarIdeas] Error crítico: " . $e->getMessage());
        return false;
    }
}
