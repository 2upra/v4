<?
function tagsFrecuentes()
{
    $claveCache = 'tagsFrecuentes12';
    $tags = obtenerCache($claveCache);
    $tiempoCache = 43200;

    if ($tags !== false) {
        $arrTags = array_keys($tags);
        shuffle($arrTags);
        $tags = array_slice($arrTags, 0, 32);
        return $tags;
    }

    global $wpdb;

    $fechaLimite = date('Y-m-d', strtotime('-1 month'));
    $consulta = $wpdb->prepare(
        "SELECT pm.meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'datosAlgoritmo'
        AND p.post_type = 'social_post'
        AND p.post_date >= %s
        LIMIT 20000",
        $fechaLimite
    );

    $resultados = $wpdb->get_col($consulta);

    if (empty($resultados)) {
        return [];
    }

    $conteoTags = [];
    $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio', 'artista_posible'];

    foreach ($resultados as $valorMeta) {
        $datosMeta = json_decode($valorMeta, true);

        if (!is_array($datosMeta)) {
            continue;
        }

        foreach ($campos as $campo) {
            if (!empty($datosMeta[$campo]['en']) && is_array($datosMeta[$campo]['en'])) {
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

    if (empty($conteoTags)) {
        return [];
    }

    arsort($conteoTags);
    $top70Tags = array_slice($conteoTags, 0, 70, true);
    $claves = array_keys($top70Tags);
    shuffle($claves);
    $clavesSeleccionadas = array_slice($claves, 0, 32);
    $tags = array_values($clavesSeleccionadas);

    guardarCache($claveCache, $top70Tags, $tiempoCache);

    return $tags;
}

function tagsPosts()
{
    $tags = tagsFrecuentes();

    if (!empty($tags)) {
        echo '<div class="tags-frecuentes">';
        foreach ($tags as $tag) {
            echo '<span class="postTag">' . esc_html(ucwords($tag)) . '</span> ';
        }
        echo '</div>';
    } else {
        echo '<div class="tags-frecuentes">No tags available.</div>';
    }
}
