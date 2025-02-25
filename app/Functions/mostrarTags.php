<?

function obtenerTagsFrecuentes(): array {
    error_log('obtenerTagsFrecuentes: Calculando (caché desactivada).');

    global $wpdb;
    $fechaLimite = date('Y-m-d', strtotime('-1 month'));
    $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio', 'artista_posible'];

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

    if ($wpdb->last_error) {
        error_log('obtenerTagsFrecuentes: Error en consulta SQL: ' . $wpdb->last_error);
        return [];
    }

    if (empty($resultados)) {
        error_log('obtenerTagsFrecuentes: No resultados para la consulta.');
        return [];
    }
     error_log('obtenerTagsFrecuentes: Resultados consulta: ' . count($resultados));


    $conteoTags = [];

    foreach ($resultados as $resultado) {
        error_log('obtenerTagsFrecuentes: Resultado: ' . print_r($resultado, true)); // Log del resultado COMPLETO
        $valorMeta = $resultado['meta_value'];
        $datosMeta = json_decode($valorMeta, true);

        if (!is_array($datosMeta)) {
            error_log('obtenerTagsFrecuentes: Error al decodificar JSON: ' . $valorMeta);
            continue;
        }
        error_log('obtenerTagsFrecuentes: datosMeta: ' . print_r($datosMeta, true));

        foreach ($campos as $campo) {
           error_log('obtenerTagsFrecuentes: Campo actual: ' . $campo);
            if (isset($datosMeta[$campo]) && is_array($datosMeta[$campo]) && isset($datosMeta[$campo]['en']) && is_array($datosMeta[$campo]['en'])) {
                error_log('obtenerTagsFrecuentes: Entrando al if del campo: ' . $campo);
                foreach ($datosMeta[$campo]['en'] as $tag) {
                    error_log('obtenerTagsFrecuentes: Tag encontrado: ' . $tag);
                    if (is_string($tag)) {
                        $tagNormalizado = strtolower(trim($tag));
                        if (!empty($tagNormalizado)) {
                            $conteoTags[$tagNormalizado] = ($conteoTags[$tagNormalizado] ?? 0) + 1;
                        }
                    }
                }
            } else {
                error_log('obtenerTagsFrecuentes: No se cumple la condición del if para el campo: ' . $campo);
            }
        }
    }

    error_log('obtenerTagsFrecuentes: Conteo de tags: ' . print_r($conteoTags, true)); // Log del conteo de tags

    arsort($conteoTags);
    $top70 = array_slice($conteoTags, 0, 70, true);

    if (empty($top70)) {
        error_log('obtenerTagsFrecuentes: No se encontraron tags despues del conteo.');
        return [];
    }
    $claves = array_keys($top70);
    shuffle($claves);
    $clavesSel = array_slice($claves, 0, 32);


    error_log('obtenerTagsFrecuentes: Tags calculados: ' . count($clavesSel));
    error_log('obtenerTagsFrecuentes: Tags: ' . print_r($clavesSel,true));
    return $clavesSel;
}



function tagsPosts() {
    $tagsFrec = obtenerTagsFrecuentes();

    if (empty($tagsFrec)) {
        echo '<div class="tags-frecuentes">No tags available.</div>';
        return;
    }

    echo '<div class="tags-frecuentes">';
    foreach ($tagsFrec as $tag) {
        echo '<span class="postTag">' . esc_html(ucwords($tag)) . '</span> ';
    }
    echo '</div>';
}

