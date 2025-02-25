<?

function obtenerTagsFrecuentes(): array {
    $claveCache = 'tagsFrecuentes13';
    $tagsFrecuentes = obtenerCache($claveCache);
    $tiempoCache = 43200;

    if ($tagsFrecuentes !== false) {
        $tagsArray = array_keys($tagsFrecuentes);
        shuffle($tagsArray);
        return array_slice($tagsArray, 0, 32);
    }

    global $wpdb;
    $fechaLimite = date('Y-m-d', strtotime('-24 month'));

    $consulta = $wpdb->prepare(
        "SELECT pm.meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'datosAlgoritmo'
        AND p.post_type = 'social_post'
        AND p.post_date >= %s",
        $fechaLimite
    );

    $resultados = $wpdb->get_results($consulta, ARRAY_A);
    $conteoTags = [];
    $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio', 'artista_posible'];
    
    if (empty($resultados)) {
        error_log('obtenerTagsFrecuentes: No se encontraron resultados en la consulta a la base de datos.');
        return [];
    }

    foreach ($resultados as $resultado) {
        $valorMeta = $resultado['meta_value'];
        $datosMeta = json_decode($valorMeta, true);
    
        if (!is_array($datosMeta)) {
            error_log('obtenerTagsFrecuentes: Valor meta no es un array JSON válido. Valor: ' . $valorMeta);
            continue;
        }
    
        foreach ($campos as $campo) {
            if (isset($datosMeta[$campo]) && is_array($datosMeta[$campo]) && isset($datosMeta[$campo]['en']) && is_array($datosMeta[$campo]['en'])) {
                foreach ($datosMeta[$campo]['en'] as $tag) {
                    if (is_string($tag)) {
                        $tagNormalizado = strtolower(trim($tag));
                        if (!empty($tagNormalizado)) {
                            $conteoTags[$tagNormalizado] = ($conteoTags[$tagNormalizado] ?? 0) + 1;
                        }
                    }
                }
            }
        }
    }

    arsort($conteoTags);
    $top70Tags = array_slice($conteoTags, 0, 70, true);
    $claves = array_keys($top70Tags);
    shuffle($claves);
    $clavesSeleccionadas = array_slice($claves, 0, 32);
    guardarCache($claveCache, $top70Tags, $tiempoCache);

    if (empty($clavesSeleccionadas)) {
         error_log('obtenerTagsFrecuentes: No se encontraron tags frecuentes.');
    }

    return $clavesSeleccionadas;
}

function tagsPosts() {
    $tagsFrecuentes = obtenerTagsFrecuentes();

    if (!empty($tagsFrecuentes)) {
        echo '<div class="tags-frecuentes">';
        foreach ($tagsFrecuentes as $tag) {
            echo '<span class="postTag">' . esc_html(ucwords($tag)) . '</span> ';
        }
        echo '</div>';
    } else {
        echo '<div class="tags-frecuentes">No tags available.</div>';
    }
}