<?php
// Funciones movidas desde app/Functions/mostrarTags.php

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

// Funciones movidas desde app/Functions/normalizarTags.php
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
        // Se usa add_post_meta con $meta_datos que es un array, WP lo serializará
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
                // Liberar la referencia
                unset($tag);
            }
        }
    }

    if ($fue_modificado) {
        // Se usa update_post_meta con $meta_datos que es un array, WP lo serializará
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
    // Solo ejecutar si el contenido del post ha cambiado
    if ($post_before->post_content === $post_after->post_content) {
        // Podríamos querer normalizar incluso si el contenido no cambia, 
        // por si los metadatos se actualizaron por otra vía, pero la lógica original lo evita.
        // Considerar si esta condición es realmente necesaria o si siempre debe normalizar.
        return;
    }
    normalizarNuevoPost($post_id, $post_after, true);
    // La llamada a verificarYRestaurarDatos ya está dentro de normalizarNuevoPost
    // llamarla aquí de nuevo es redundante.
    // verificarYRestaurarDatos($post_id); 
}

add_action('post_updated', 'normalizarPostActualizado', 10, 3);

// Función para verificar y restaurar datos si 'datosAlgoritmo' está vacío
function verificarYRestaurarDatos($post_id) {
    // Verificar si datosAlgoritmo existe y no está vacío
    $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
    
    if (empty($datos_algoritmo)) {
        // Intentar restaurar desde el respaldo
        $respaldo = get_post_meta($post_id, 'datosAlgoritmo_respaldo', true);
        
        if (!empty($respaldo)) {
            // Restaurar los datos desde el respaldo
            // $respaldo ya debería ser un array si se guardó correctamente
            update_post_meta($post_id, 'datosAlgoritmo', $respaldo);
            
            // Opcional: Registrar la restauración
            error_log("Datos 'datosAlgoritmo' restaurados desde respaldo para el post ID: " . $post_id);
        }
    }
}

// Función para crear respaldo y normalizar en lote
function crearRespaldoYNormalizar($batch_size = 100) {
    global $wpdb;
    
    // Normalizaciones (repetidas de normalizarNuevoPost, considerar centralizar)
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
        // Obtener posts que tienen 'datosAlgoritmo' pero no 'datosAlgoritmo_respaldo'
        // La consulta original tenía un LEFT JOIN incorrecto, corregido aquí:
        $query = $wpdb->prepare("
            SELECT p.ID, pm.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'datosAlgoritmo_respaldo'
            WHERE p.post_type = 'social_post'
            AND pm2.meta_id IS NULL
            LIMIT %d OFFSET %d
        ", $batch_size, $offset);

        $resultados = $wpdb->get_results($query);
        
        if (empty($resultados)) {
            break;
        }

        foreach ($resultados as $row) {
            // El valor meta ya viene serializado de la BD
            $meta_datos_serializados = $row->meta_value;
            // Deserializar para trabajar con él
            $meta_datos = maybe_unserialize($meta_datos_serializados);
            
            if (!is_array($meta_datos)) {
                error_log("Error al deserializar datosAlgoritmo para post ID: {$row->ID}");
                continue;
            }

            // Crear respaldo usando el valor serializado original
            add_post_meta($row->ID, 'datosAlgoritmo_respaldo', $meta_datos_serializados, true);

            // Normalizar tags (usando el array deserializado)
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
                        unset($tag); // Liberar referencia
                    }
                }
            }

            if ($fue_modificado) {
                // Guardar el array modificado, WP lo serializará
                update_post_meta($row->ID, 'datosAlgoritmo', $meta_datos);
            }

            $total_procesados++;
        }

        // El offset se incrementa por el tamaño del lote para la siguiente página
        $offset += $batch_size; 
        
        // Pequeña pausa para no sobrecargar el servidor
        if ($total_procesados > 0 && $total_procesados % 1000 === 0) {
            sleep(1);
        }

    } while (count($resultados) === $batch_size);

    error_log("Proceso de respaldo y normalización completado. Total procesados: " . $total_procesados);
    return $total_procesados;
}

// Para revertir los cambios si algo sale mal
function revertirNormalizacion($batch_size = 100) {
    global $wpdb;
    
    $offset = 0;
    $total_revertidos = 0;

    do {
        // Obtener posts que tienen respaldo
        $query = $wpdb->prepare("
            SELECT p.ID, pm.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'social_post'
            AND pm.meta_key = 'datosAlgoritmo_respaldo'
            LIMIT %d OFFSET %d
        ", $batch_size, $offset);

        $resultados = $wpdb->get_results($query);
        
        if (empty($resultados)) {
            break;
        }

        foreach ($resultados as $row) {
            // $row->meta_value contiene el valor serializado del respaldo
            // Restaurar 'datosAlgoritmo' con el valor del respaldo
            // Es importante pasar el valor tal cual (serializado) a update_post_meta
            // o deserializarlo si se espera un array.
            // Dado que add_post_meta guardó el valor serializado, lo pasamos tal cual.
            update_post_meta($row->ID, 'datosAlgoritmo', $row->meta_value);
            // Eliminar el respaldo
            delete_post_meta($row->ID, 'datosAlgoritmo_respaldo');
            $total_revertidos++;
        }

        $offset += $batch_size;
        
        if ($total_revertidos > 0 && $total_revertidos % 1000 === 0) {
            sleep(1);
        }

    } while (count($resultados) === $batch_size);

    error_log("Proceso de reversión completado. Total revertidos: " . $total_revertidos);
    return $total_revertidos;
}

// Uso (ejemplos, no ejecutar automáticamente):
// // Para iniciar el proceso:
// // add_action('admin_init', function() { 
// //     if (isset($_GET['ejecutar_normalizacion']) && $_GET['ejecutar_normalizacion'] == '1') {
// //         $total = crearRespaldoYNormalizar(100);
// //         echo "Total de posts procesados para normalización: " . $total;
// //         exit;
// //     }
// // });
//
// // Si algo sale mal:
// // add_action('admin_init', function() { 
// //     if (isset($_GET['revertir_normalizacion']) && $_GET['revertir_normalizacion'] == '1') {
// //         $revertidos = revertirNormalizacion(100);
// //         echo "Total de posts revertidos: " . $revertidos;
// //         exit;
// //     }
// // });

// Función para restaurar 'datosAlgoritmo' desde 'datosAlgoritmo_respaldo' si está vacío
function restaurar_datos_algoritmo() {
    // Parámetros para obtener todos los posts del tipo 'social_post'
    $args = array(
        'post_type'      => 'social_post',
        'posts_per_page' => -1, // Obtener todos los posts
        'post_status'    => 'any', // Incluir todos los estados (publicados, borradores, etc.)
        'fields'         => 'ids', // Solo necesitamos los IDs para optimizar
        'no_found_rows'  => true, // Optimización: no necesitamos paginación
        'update_post_meta_cache' => false, // Optimización: no necesitamos meta cache
        'update_post_term_cache' => false, // Optimización: no necesitamos term cache
    );

    // Obtener los IDs de los posts
    $post_ids = get_posts($args);

    $restaurados_count = 0;
    // Verificar cada post
    foreach ($post_ids as $post_id) {
        // Intentar obtener el meta dato 'datosAlgoritmo'
        $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);

        // Si 'datosAlgoritmo' no existe o está vacío
        if (empty($datos_algoritmo)) {
            // Verificar si existe 'datosAlgoritmo_respaldo'
            $datos_algoritmo_respaldo = get_post_meta($post_id, 'datosAlgoritmo_respaldo', true);

            if (!empty($datos_algoritmo_respaldo)) {
                // Restaurar el valor de 'datosAlgoritmo' desde 'datosAlgoritmo_respaldo'
                // Asumimos que $datos_algoritmo_respaldo es el valor correcto (puede ser serializado o no)
                update_post_meta($post_id, 'datosAlgoritmo', $datos_algoritmo_respaldo);
                error_log("Restaurado 'datosAlgoritmo' para el post ID: $post_id");
                $restaurados_count++;
            }
        }
        // Liberar memoria
        unset($datos_algoritmo, $datos_algoritmo_respaldo);
    }

    // Agregar un log para saber que la función se ejecutó y cuántos se restauraron
    error_log("Restauración de 'datosAlgoritmo' completada. Posts restaurados: " . $restaurados_count);
}

// Ejecutar la función de restauración una sola vez al inicio
add_action('init', function() {
    // Usar una opción para asegurar que solo se ejecute una vez
    if (!get_option('datos_algoritmo_restaurado_v1')) {
        restaurar_datos_algoritmo();
        update_option('datos_algoritmo_restaurado_v1', 1); // Marcar que ya se ejecutó
    }
});

?>
