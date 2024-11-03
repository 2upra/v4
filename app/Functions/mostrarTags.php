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
    $cache_key = 'tagsFrecuentes7';
    $tags_frecuentes = get_transient($cache_key);

    if ($tags_frecuentes !== false) {
        return $tags_frecuentes;
    }

    global $wpdb;
    
    // Consulta SQL directa para mejor rendimiento
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
    
    // Campos a procesar
    $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];

    foreach ($resultados as $meta_value) {
        $meta_datos = json_decode($meta_value, true);
        
        if (!is_array($meta_datos)) {
            continue;
        }

        foreach ($campos as $campo) {
            if (!empty($meta_datos[$campo]['es']) && is_array($meta_datos[$campo]['es'])) {
                foreach ($meta_datos[$campo]['es'] as $tag) {
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

    // Tomar los 12 más frecuentes
    $tags_frecuentes = array_slice($tags_conteo, 0, 12, true);

    // Guardar en caché
    set_transient($cache_key, $tags_frecuentes, 12 * HOUR_IN_SECONDS);

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