<?

function tagsFrecuentes() {
    guardarLog("Iniciando recopilación de tags frecuentes.");

    $tags_conteo = array();
    $args = array(
        'post_type' => 'social_post', // Modifica si usas un custom post type
        'posts_per_page' => -1, // Obtener todos los posts
        'meta_key' => 'datosAlgoritmo'
    );

    $query = new WP_Query($args);

    guardarLog("Número de posts encontrados: " . $query->found_posts);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            guardarLog("Procesando post ID: " . $post_id);

            // Obtener los metadatos
            $meta_datos = get_post_meta($post_id, 'datosAlgoritmo', true);
            if (!$meta_datos) {
                guardarLog("No se encontraron metadatos para el post ID: " . $post_id);
                continue;
            }

            guardarLog("Metadatos encontrados para el post ID " . $post_id . ": " . json_encode($meta_datos));

            // Campos que vamos a procesar
            $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];

            foreach ($campos as $campo) {
                if (isset($meta_datos[$campo]['es'])) {
                    foreach ($meta_datos[$campo]['es'] as $tag) {
                        $tag_normalizado = strtolower($tag);
                        guardarLog("Tag encontrado: " . $tag_normalizado);
                        if (!isset($tags_conteo[$tag_normalizado])) {
                            $tags_conteo[$tag_normalizado] = 0;
                        }
                        $tags_conteo[$tag_normalizado]++;
                    }
                } else {
                    guardarLog("Campo no encontrado en metadatos para el post ID " . $post_id . ": " . $campo);
                }
            }
        }
        wp_reset_postdata();
    } else {
        guardarLog("No se encontraron posts.");
    }

    // Ordenar los tags por frecuencia
    arsort($tags_conteo);

    // Guardar los 12 tags más frecuentes
    $tags_frecuentes = array_slice($tags_conteo, 0, 12, true);

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