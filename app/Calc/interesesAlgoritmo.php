<?

function generarMetaDeIntereses($user_id) {
    // Validación inicial del user_id
    if (empty($user_id) || !is_numeric($user_id)) {
        error_log("ID de usuario inválido en generarMetaDeIntereses: " . print_r($user_id, true));
        return false;
    }

    // Verificar cache
    $cache_key = 'meta_intereses_' . $user_id;
    $cached_result = get_transient($cache_key);
    if ($cached_result !== false) {
        return $cached_result;
    }

    global $wpdb;
    $likePost = obtenerLikesDelUsuario($user_id, 500);
    if (empty($likePost) || !is_array($likePost)) {
        error_log("No se encontraron likes para el usuario: " . $user_id);
        return false;
    }

    // Obtener intereses actuales
    $interesesActuales = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM " . INTERES_TABLE . " WHERE user_id = %d",
        $user_id
    ), OBJECT_K);

    // Preparar la consulta para los posts
    try {
        // Convertir array de IDs a string de placeholders
        $placeholders = implode(',', array_map(function() { return '%d'; }, $likePost));
        
        $query = "
            SELECT p.ID, p.post_content, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
            WHERE p.ID IN ($placeholders)
        ";
        
        // Preparar la consulta con los valores
        $sql = $wpdb->prepare($query, $likePost);
        $post_data = $wpdb->get_results($sql);

        if (empty($post_data)) {
            error_log("No se encontraron datos de posts para los likes del usuario: " . $user_id);
            return false;
        }

        $tag_intensidad = [];

        foreach ($post_data as $post) {
            // Procesar datosAlgoritmo
            if (!empty($post->meta_value)) {
                $datosAlgoritmo = json_decode($post->meta_value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error al decodificar JSON para post ID " . $post->ID . ": " . json_last_error_msg());
                    continue;
                }

                if (is_array($datosAlgoritmo)) {
                    foreach ($datosAlgoritmo as $key => $value) {
                        if (is_array($value)) {
                            foreach (['es', 'en'] as $lang) {
                                if (isset($value[$lang]) && is_array($value[$lang])) {
                                    foreach ($value[$lang] as $item) {
                                        if (is_string($item)) {
                                            $item = normalizarTexto($item);
                                            $tag_intensidad[$item] = ($tag_intensidad[$item] ?? 0) + 1;
                                        }
                                    }
                                }
                            }
                        } elseif (is_string($value) && !empty($value)) {
                            $value = normalizarTexto($value);
                            $tag_intensidad[$value] = ($tag_intensidad[$value] ?? 0) + 1;
                        }
                    }
                }
            }

            // Procesar contenido del post
            if (!empty($post->post_content)) {
                $content = wp_strip_all_tags($post->post_content);
                $content = normalizarTexto($content);
                $palabras = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($palabras as $palabra) {
                    $palabra = trim($palabra);
                    if (!empty($palabra)) {
                        $tag_intensidad[$palabra] = ($tag_intensidad[$palabra] ?? 0) + 1;
                    }
                }
            }
        }

        if (empty($tag_intensidad)) {
            error_log("No se generaron tags de intensidad para el usuario: " . $user_id);
            return false;
        }

        arsort($tag_intensidad);
        $tag_intensidad = array_slice($tag_intensidad, 0, 200, true);

        $result = actualizarIntereses($user_id, $tag_intensidad, $interesesActuales);
        
        if ($result !== false) {
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
        }

        return $result;

    } catch (Exception $e) {
        error_log("Error en generarMetaDeIntereses: " . $e->getMessage());
        return false;
    }
}


function actualizarIntereses($user_id, $tag_intensidad, $interesesActuales)
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');

    try {
        $batch_values = [];
        $intereses_nuevos = array_keys($tag_intensidad);

        foreach ($tag_intensidad as $interest => $intensity) {
            $batch_values[] = $wpdb->prepare('(%d, %s, %d)', $user_id, $interest, $intensity);
        }

        if (!empty($batch_values)) {
            $values = implode(', ', $batch_values);
            $sql = "
                INSERT INTO " . INTERES_TABLE . " (user_id, interest, intensity)
                VALUES $values
                ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)
            ";
            $wpdb->query($sql);
        }

        $intereses_a_eliminar = array_diff_key($interesesActuales, $tag_intensidad);
        if (!empty($intereses_a_eliminar)) {
            $placeholders = implode(', ', array_fill(0, count($intereses_a_eliminar), '%s'));
            $sql = $wpdb->prepare(
                "DELETE FROM " . INTERES_TABLE . " WHERE user_id = %d AND interest IN ($placeholders)",
                array_merge([$user_id], array_keys($intereses_a_eliminar))
            );
            $wpdb->query($sql);
        }

        $wpdb->query('COMMIT');

        return true;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('Error al actualizar intereses: ' . $e->getMessage());
        return false;
    }
}

function calcularPuntosIntereses($post_id, $datos)
{
    $puntosIntereses = 0;
    
    // Verificar si existen los índices necesarios
    if (!isset($datos['datosAlgoritmo'][$post_id]) || 
        !isset($datos['datosAlgoritmo'][$post_id]->meta_value)) {
        return $puntosIntereses;
    }

    $datosAlgoritmo = json_decode($datos['datosAlgoritmo'][$post_id]->meta_value, true);
    
    // Verificar si el json_decode fue exitoso
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosAlgoritmo)) {
        return $puntosIntereses;
    }

    $oneshot = ['one shot', 'one-shot', 'oneshot'];
    $esOneShot = false;
    $metaValue = $datos['datosAlgoritmo'][$post_id]->meta_value;

    if (!empty($metaValue)) {
        foreach ($oneshot as $palabra) {
            if (stripos($metaValue, $palabra) !== false) {
                $esOneShot = true;
                break;
            }
        }
    }

    foreach ($datosAlgoritmo as $key => $value) {
        if (is_array($value)) {
            foreach (['es', 'en'] as $lang) {
                if (isset($value[$lang]) && is_array($value[$lang])) {
                    foreach ($value[$lang] as $item) {
                        if (isset($datos['interesesUsuario'][$item])) {
                            $puntosIntereses += 10 + $datos['interesesUsuario'][$item]->intensity;
                        }
                    }
                }
            }
        } elseif (!empty($value) && isset($datos['interesesUsuario'][$value])) {
            $puntosIntereses += 10 + $datos['interesesUsuario'][$value]->intensity;
        }
    }
    
    if ($esOneShot) {
        $puntosIntereses *= 1;
    }
    
    return $puntosIntereses;
}
