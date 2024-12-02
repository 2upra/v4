<?
function normalizarNuevoPost($post_id, $post, $update) {
    // Verificar si es el tipo de post correcto
    if ('social_post' !== get_post_type($post_id)) {
        return;
    }

    // Obtener los datos del algoritmo
    $meta_datos = get_post_meta($post_id, 'datosAlgoritmo', true);
    
    if (!is_array($meta_datos)) {
        return;
    }

    // Normalizaciones
    $normalizaciones = array(
        'one-shot' => 'one shot',
        'oneshot' => 'one shot',
        'percusión' => 'percusión',
        'hiphop' => 'hip hop',
        'hip-hop' => 'hip hop',
        'rnb' => 'r&b',
        'vocal' => 'vocals',
        'r&b' => 'r&b',
        'randb' => 'r&b',
        'rock&roll' => 'rock and roll',
        'rockandroll' => 'rock and roll',
        'rock-and-roll' => 'rock and roll',
        'campana de vaca' => 'cowbell',
        'cowbells' => 'cowbell',
        'drums' => 'drum',
    );

    // Verificar si ya existe un respaldo antes de crear uno nuevo
    $respaldo_existente = get_post_meta($post_id, 'datosAlgoritmo_respaldo', true);
    if (empty($respaldo_existente)) {
        add_post_meta($post_id, 'datosAlgoritmo_respaldo', $meta_datos, true);
    }

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
        update_post_meta($post_id, 'datosAlgoritmo', $meta_datos);
    }

    // Verificar y restaurar después de la normalización
    verificarYRestaurarDatos($post_id);
}
add_action('wp_insert_post', 'normalizarNuevoPost', 10, 3);


function normalizarPostActualizado($post_id, $post_after, $post_before) {
    if ('social_post' !== get_post_type($post_id)) {
        return;
    }
    if ($post_before->post_content === $post_after->post_content) {
        return;
    }
    normalizarNuevoPost($post_id, $post_after, true);
    verificarYRestaurarDatos($post_id);
}

add_action('post_updated', 'normalizarPostActualizado', 10, 3);

// Función para verificar y restaurar datos
function verificarYRestaurarDatos($post_id) {
    // Verificar si datosAlgoritmo existe
    $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
    
    if (empty($datos_algoritmo)) {
        // Intentar restaurar desde el respaldo
        $respaldo = get_post_meta($post_id, 'datosAlgoritmo_respaldo', true);
        
        if (!empty($respaldo)) {
            // Restaurar los datos desde el respaldo
            update_post_meta($post_id, 'datosAlgoritmo', $respaldo);
            
            // Opcional: Registrar la restauración
            error_log("Datos restaurados para el post ID: " . $post_id);
        }
    }
}
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
        'vocal' => 'vocals',
        'r&b' => 'r&b',
        'randb' => 'r&b',
        'rock&roll' => 'rock and roll',
        'rockandroll' => 'rock and roll',
        'rock-and-roll' => 'rock and roll',
        'campana de vaca' => 'cowbell',
        'cowbells' => 'cowbell',
        'drums' => 'drum',
    );

    $offset = 0;
    $total_procesados = 0;

    do {
        // Obtener posts que no tienen respaldo
        $query = $wpdb->prepare("
            SELECT p.ID, pm.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
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
// $total = crearRespaldoYNormalizar(100);

// Si algo sale mal:
// $revertidos = revertirNormalizacion(100);
// echo "Total de posts revertidos: " . $revertidos;


