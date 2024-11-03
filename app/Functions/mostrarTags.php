<?

function tagsFrecuentes() {
    $cache_key = 'tagsFrecuentes4';
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
    $total_posts_limit = 50; // Limitar el número total de posts a procesar
    $total_posts_processed = 0; // Contador de posts procesados

    while ($total_posts_processed < $total_posts_limit) {
        $args = array(
            'post_type' => 'social_post', // Modifica si usas un custom post type
            'posts_per_page' => $posts_per_page, // Obtener los posts en lotes
            'paged' => $paged, // Paginación
            'meta_key' => 'datosAlgoritmo',
            'no_found_rows' => false, // Necesitamos el total de posts para la paginación
        );

        $query = new WP_Query($args);

        $posts_in_this_page = $query->found_posts;

        guardarLog("Número de posts encontrados en la página $paged: " . $posts_in_this_page);

        if ($posts_in_this_page == 0 || !$query->have_posts()) {
            guardarLog("No se encontraron más posts.");
            break; // Salir del bucle si no hay más posts
        }

        while ($query->have_posts() && $total_posts_processed < $total_posts_limit) {
            $query->the_post();
            $post_id = get_the_ID();
            $total_posts_processed++;

            guardarLog("Procesando post ID: $post_id (Post #$total_posts_processed)");

            // Obtener los metadatos
            $meta_datos = get_post_meta($post_id, 'datosAlgoritmo', true);
            if (!$meta_datos) {
                guardarLog("No se encontraron metadatos para el post ID: $post_id");
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
        $paged++; // Pasar a la siguiente página
    }

    guardarLog("Total de posts procesados: $total_posts_processed");

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