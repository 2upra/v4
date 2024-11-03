<?

function tagsFrecuentes() {
    /*$cache_key = 'tags_mas_frecuentes';
    $tags_frecuentes = get_transient($cache_key);

    // Si los tags ya están en caché, devolverlos
    if ($tags_frecuentes !== false) {
        return $tags_frecuentes;
    } */

    // Si no están en caché, procesar y contar los tags
    $tags_conteo = array();
    $args = array(
        'post_type' => 'social_post', // Modifica si usas un custom post type
        'posts_per_page' => -1, // Obtener todos los posts
        'meta_key' => 'datosAlgoritmo'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $meta_datos = get_post_meta($post_id, 'datosAlgoritmo', true);

            if ($meta_datos) {
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
        }
        wp_reset_postdata();
    }

    // Ordenar los tags por frecuencia
    arsort($tags_conteo);

    // Guardar los 12 tags más frecuentes en caché por 12 horas
    $tags_frecuentes = array_slice($tags_conteo, 0, 12, true);
    // set_transient($cache_key, $tags_frecuentes, 12 * HOUR_IN_SECONDS);

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