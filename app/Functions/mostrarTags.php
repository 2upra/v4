<?

/*
2024-11-03 01:35:39 - Iniciando recopilación de tags frecuentes.
2024-11-03 01:35:39 - Número de posts encontrados en la página 1: 8809
2024-11-03 01:35:39 - Procesando post ID: 275396 (Post #1)
2024-11-03 01:35:39 - Procesando post ID: 275393 (Post #2)
2024-11-03 01:35:39 - Procesando post ID: 275390 (Post #3)
2024-11-03 01:35:39 - Procesando post ID: 275387 (Post #4)
2024-11-03 01:35:39 - Procesando post ID: 275384 (Post #5)
2024-11-03 01:35:39 - Procesando post ID: 275381 (Post #6)
2024-11-03 01:35:39 - Procesando post ID: 275378 (Post #7)
2024-11-03 01:35:39 - Procesando post ID: 275375 (Post #8)
2024-11-03 01:35:39 - Procesando post ID: 275372 (Post #9)

2024-11-03 01:35:39 - Total de posts procesados: 9
2024-11-03 01:35:39 - Tags más frecuentes: []
*/

function tagsFrecuentes() {
    $cache_key = 'tagsFrecuentes6';
    $tags_frecuentes = get_transient($cache_key);

    if ($tags_frecuentes !== false) {
        guardarLog("Usando tags frecuentes desde caché.");
        return $tags_frecuentes;
    }

    guardarLog("Iniciando recopilación de tags frecuentes.");

    $tags_conteo = array();
    $posts_per_page = 9;
    $paged = 1;
    $total_posts_limit = 9;
    $total_posts_processed = 0;

    while ($total_posts_processed < $total_posts_limit) {
        $args = array(
            'post_type' => 'social_post',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'meta_key' => 'datosAlgoritmo',
            'no_found_rows' => false,
        );

        $query = new WP_Query($args);
        
        guardarLog("Número de posts encontrados en la página $paged: " . $query->found_posts);

        if (!$query->have_posts()) {
            guardarLog("No se encontraron más posts.");
            break;
        }

        while ($query->have_posts() && $total_posts_processed < $total_posts_limit) {
            $query->the_post();
            $post_id = get_the_ID();
            $total_posts_processed++;

            guardarLog("Procesando post ID: $post_id (Post #$total_posts_processed)");

            $meta_datos_json = get_post_meta($post_id, 'datosAlgoritmo', true);
            
            // Decodificar el JSON
            $meta_datos = json_decode($meta_datos_json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                guardarLog("Error al decodificar JSON para post ID: $post_id - " . json_last_error_msg());
                continue;
            }

            // Campos a procesar
            $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];

            foreach ($campos as $campo) {
                if (isset($meta_datos[$campo]['es']) && is_array($meta_datos[$campo]['es'])) {
                    foreach ($meta_datos[$campo]['es'] as $tag) {
                        if (is_string($tag)) {
                            $tag_normalizado = strtolower(trim($tag));
                            if (!empty($tag_normalizado)) {
                                if (!isset($tags_conteo[$tag_normalizado])) {
                                    $tags_conteo[$tag_normalizado] = 0;
                                }
                                $tags_conteo[$tag_normalizado]++;
                            }
                        }
                    }
                }
            }
        }

        wp_reset_postdata();
        $paged++;
    }

    guardarLog("Total de posts procesados: $total_posts_processed");
    guardarLog("Tags encontrados antes de ordenar: " . print_r($tags_conteo, true));

    // Ordenar los tags por frecuencia
    arsort($tags_conteo);

    // Tomar los 12 más frecuentes
    $tags_frecuentes = array_slice($tags_conteo, 0, 12, true);

    // Guardar en caché
    set_transient($cache_key, $tags_frecuentes, 12 * HOUR_IN_SECONDS);

    guardarLog("Tags más frecuentes finales: " . print_r($tags_frecuentes, true));

    return $tags_frecuentes;
}

function tagsPosts() {
    $tags_frecuentes = tagsFrecuentes();

    if (!empty($tags_frecuentes)) {
        echo '<div class="tags-frecuentes">';
        foreach ($tags_frecuentes as $tag => $cantidad) {
            echo '<span class="postTag">' . esc_html(ucwords($tag)) . ' (' . intval($cantidad) . ')</span> ';
        }
        echo '</div>';
    } else {
        echo '<div class="tags-frecuentes">No tags available.</div>';
    }
}