<?

function normalizar_tags_personalizados($limit = 10) {
    // Definir las normalizaciones manuales
    $normalizaciones = array(
        'one-shot' => 'one shot',
        'oneshot' => 'one shot',
        'percusion' => 'percusión',
        'hiphop' => 'hip hop',
        'hip-hop' => 'hip hop',
        'soul' => 'soul',
        'rnb' => 'r&b',
        'r&b' => 'r&b',
        'randb' => 'r&b',
        'rock&roll' => 'rock and roll',
        'rockandroll' => 'rock and roll',
        'rock-and-roll' => 'rock and roll'
        // Añade más normalizaciones según necesites
    );

    // Obtener los últimos posts
    $args = array(
        'post_type' => 'social_post',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_key' => 'datosAlgoritmo',
        'meta_query' => array(
            array(
                'key' => 'datosAlgoritmo',
                'compare' => 'EXISTS'
            )
        )
    );

    $posts = get_posts($args);
    $resultados = array(
        'procesados' => 0,
        'actualizados' => 0,
        'errores' => array()
    );

    foreach ($posts as $post) {
        $resultados['procesados']++;
        
        // Obtener los metadatos
        $meta_datos_json = get_post_meta($post->ID, 'datosAlgoritmo', true);
        
        if (empty($meta_datos_json)) {
            $resultados['errores'][] = "Post ID {$post->ID}: No se encontraron metadatos";
            continue;
        }

        $meta_datos = json_decode($meta_datos_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $resultados['errores'][] = "Post ID {$post->ID}: Error al decodificar JSON - " . json_last_error_msg();
            continue;
        }

        $fue_modificado = false;
        $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];

        foreach ($campos as $campo) {
            foreach (['es', 'en'] as $idioma) {
                if (!empty($meta_datos[$campo][$idioma]) && is_array($meta_datos[$campo][$idioma])) {
                    foreach ($meta_datos[$campo][$idioma] as &$tag) {
                        $tag_original = $tag;
                        $tag_lower = strtolower(trim($tag));
                        
                        if (isset($normalizaciones[$tag_lower])) {
                            $tag = $normalizaciones[$tag_lower];
                            $fue_modificado = true;
                        }
                    }
                }
            }
        }

        if ($fue_modificado) {
            $resultado = update_post_meta($post->ID, 'datosAlgoritmo', $meta_datos);
            if ($resultado) {
                $resultados['actualizados']++;
            } else {
                $resultados['errores'][] = "Post ID {$post->ID}: Error al actualizar metadatos";
            }
        }
    }

    // Registrar resultados
    guardarLog("Normalización de tags completada: " . 
              "Procesados: {$resultados['procesados']}, " .
              "Actualizados: {$resultados['actualizados']}, " .
              "Errores: " . count($resultados['errores']));

    if (!empty($resultados['errores'])) {
        guardarLog("Errores durante la normalización: " . print_r($resultados['errores'], true));
    }

    return $resultados;
}

// Uso de la función
$resultados = normalizar_tags_personalizados(10);



