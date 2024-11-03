<?

function crearRespaldoYNormalizar($batch_size = 100) {
    global $wpdb;
    
    // Normalizaciones
    $normalizaciones = array(
        'one-shot' => 'one shot',
        'oneshot' => 'one shot',
        'percusión' => 'percusión',
        'hiphop' => 'hip hop',
        'hip-hop' => 'hip hop',
        'rnb' => 'r&b',
        'r&b' => 'r&b',
        'randb' => 'r&b',
        'rock&roll' => 'rock and roll',
        'rockandroll' => 'rock and roll',
        'rock-and-roll' => 'rock and roll',
        'campana de vaca' => 'cowbell',
    );

    $offset = 0;
    $total_procesados = 0;

    do {
        // Obtener posts que no tienen respaldo
        $query = $wpdb->prepare("
            SELECT p.ID, pm.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'datosAlgoritmo_respaldo'
            WHERE p.post_type = 'social_post'
            AND pm.meta_key = 'datosAlgoritmo'
            AND pm2.meta_id IS NULL
            LIMIT %d, %d
        ", $offset, $batch_size);

        $resultados = $wpdb->get_results($query);
        
        if (empty($resultados)) {
            break;
        }

        foreach ($resultados as $row) {
            $meta_datos = json_decode($row->meta_value, true);
            
            if (!is_array($meta_datos)) {
                continue;
            }

            // Crear respaldo
            add_post_meta($row->ID, 'datosAlgoritmo_respaldo', $row->meta_value);

            // Normalizar tags
            $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];
            $fue_modificado = false;

            foreach ($campos as $campo) {
                foreach (['es', 'en'] as $idioma) {
                    if (!empty($meta_datos[$campo][$idioma]) && is_array($meta_datos[$campo][$idioma])) {
                        foreach ($meta_datos[$campo][$idioma] as &$tag) {
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
                update_post_meta($row->ID, 'datosAlgoritmo', $meta_datos);
            }

            $total_procesados++;
        }

        $offset += $batch_size;
        
        // Pequeña pausa para no sobrecargar el servidor
        if ($total_procesados % 1000 === 0) {
            sleep(1);
        }

    } while (count($resultados) === $batch_size);

    return $total_procesados;
}

// Para revertir los cambios si algo sale mal
function revertirNormalizacion($batch_size = 100) {
    global $wpdb;
    
    $offset = 0;
    $total_revertidos = 0;

    do {
        $query = $wpdb->prepare("
            SELECT p.ID, pm.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'social_post'
            AND pm.meta_key = 'datosAlgoritmo_respaldo'
            LIMIT %d, %d
        ", $offset, $batch_size);

        $resultados = $wpdb->get_results($query);
        
        if (empty($resultados)) {
            break;
        }

        foreach ($resultados as $row) {
            update_post_meta($row->ID, 'datosAlgoritmo', $row->meta_value);
            delete_post_meta($row->ID, 'datosAlgoritmo_respaldo');
            $total_revertidos++;
        }

        $offset += $batch_size;
        
        if ($total_revertidos % 1000 === 0) {
            sleep(1);
        }

    } while (count($resultados) === $batch_size);

    return $total_revertidos;
}

// Uso:
$total = crearRespaldoYNormalizar(100);

// Si algo sale mal:
// $revertidos = revertirNormalizacion(100);
// echo "Total de posts revertidos: " . $revertidos;


