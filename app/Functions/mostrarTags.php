<?

/*
despues que se cachean, no se muestran aleatoreamente, y ya no quiero que muestre la cantidad 
*/


function tagsFrecuentes() {
    $cache_key = 'tagsFrecuentes12';
    $tags_frecuentes = get_transient($cache_key);

    if ($tags_frecuentes !== false) {
        // Mezclar aleatoriamente las etiquetas almacenadas en caché y seleccionar 32
        $tags_array = array_keys($tags_frecuentes);
        shuffle($tags_array);
        $tags_frecuentes = array_slice($tags_array, 0, 32); // Solo obtenemos las etiquetas sin conteo
        return $tags_frecuentes;
    }

    global $wpdb;

    $query = "
        SELECT pm.meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'datosAlgoritmo'
        AND p.post_type = 'social_post'
        LIMIT 50000
    ";

    $resultados = $wpdb->get_col($query);
    $tags_conteo = [];

    $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio', 'artista_posible'];

    foreach ($resultados as $meta_value) {
        $meta_datos = json_decode($meta_value, true);
        
        if (!is_array($meta_datos)) {
            continue;
        }

        foreach ($campos as $campo) {
            if (!empty($meta_datos[$campo]['en']) && is_array($meta_datos[$campo]['en'])) {
                foreach ($meta_datos[$campo]['en'] as $tag) {
                    if (is_string($tag)) {
                        $tag_normalizado = strtolower(trim($tag));
                        if (!empty($tag_normalizado)) {
                            $tags_conteo[$tag_normalizado] = ($tags_conteo[$tag_normalizado] ?? 0) + 1;
                        }
                    }
                }
            }
        }
    }

    // Ordenar los tags por frecuencia
    arsort($tags_conteo);

    // Tomar los 64 más frecuentes
    $top_64_tags = array_slice($tags_conteo, 0, 70, true);

    // Seleccionar aleatoriamente 32 tags de los 64 más frecuentes
    $keys = array_keys($top_64_tags);
    shuffle($keys);
    $selected_keys = array_slice($keys, 0, 32);

    // Solo las etiquetas sin conteo
    $tags_frecuentes = array_values($selected_keys);

    // Guardar en caché los 64 tags más frecuentes
    set_transient($cache_key, $top_64_tags, 12 * HOUR_IN_SECONDS);

    return $tags_frecuentes;
}

function tagsPosts() {
    $tags_frecuentes = tagsFrecuentes();

    if (!empty($tags_frecuentes)) {
        echo '<div class="tags-frecuentes">';
        foreach ($tags_frecuentes as $tag) {
            echo '<span class="postTag">' . esc_html(ucwords($tag)) . '</span> ';
        }
        echo '</div>';
    } else {
        echo '<div class="tags-frecuentes">No tags available.</div>';
    }
}
