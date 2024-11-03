<?

function tagsFrecuentes() {
    $cache_key = 'tagsFrecuentes3';
    $tags_frecuentes = get_transient($cache_key);

    // Si los tags ya están en caché, devolverlos
    if ($tags_frecuentes !== false) {
        guardarLog("Usando tags frecuentes desde caché.");
        return $tags_frecuentes;
    }

    guardarLog("Iniciando recopilación de tags frecuentes.");

    $tags_conteo = array();
    $posts_per_page = 50; // Número de posts por página
    $paged = 1;  // Empezamos en la página 1
    $total_posts = 0;
    $max_num_pages = null;

    do {
        $args = array(
            'post_type' => 'social_post', // Modifica si usas un custom post type
            'posts_per_page' => $posts_per_page, // Obtener los posts en lotes
            'paged' => $paged, // Paginación
            'meta_key' => 'datosAlgoritmo',
            'no_found_rows' => false, // Necesitamos el total de posts para la paginación
        );

        $query = new WP_Query($args);

        if ($max_num_pages === null) {
            $max_num_pages = $query->max_num_pages;
            guardarLog("Número máximo de páginas: $max_num_pages");
        }

        guardarLog("Número de posts encontrados en la página $paged: " . $query->found_posts);
        $total_posts += $query->found_posts;

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                // Obtener los metadatos
                $meta_datos = get_post_meta($post_id, 'datosAlgoritmo', true);
                if (!$meta_datos) {
                    continue; // Saltar si no hay metadatos
                }

                // Campos que vamos a procesar
                $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];

                foreach ($campos as $campo) {
                    if (isset($meta_datos[$campo]['es'])) {
                        foreach ($meta_datos[$campo]['es'] as $tag) {
                            $tag_normalizado = strtolower($tag);
                            if (!isset($tags_conteo[$tag_normalizado])) {
                                $tags_conteo[$tag_normalizado] = 0;
                            }
                            $tags_conteo[$tag_normalizado]++;
                        }
                    }
                }
            }
            wp_reset_postdata();
        }

        // Incrementar la página para la siguiente iteración
        $paged++;
    } while ($paged <= $max_num_pages);

    guardarLog("Total de posts procesados: $total_posts");

    // Ordenar los tags por frecuencia
    arsort($tags_conteo);

    // Guardar los 12 tags más frecuentes
    $tags_frecuentes = array_slice($tags_conteo, 0, 12, true);

    // Guardar los resultados en caché por 12 horas
    set_transient($cache_key, $tags_frecuentes, 12 * HOUR_IN_SECONDS);

    guardarLog("Tags más frecuentes: " . json_encode($tags_frecuentes));

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