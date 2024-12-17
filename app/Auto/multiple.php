<?

function generar_publicaciones_multiples()
{
    // Registro de inicio de la función
    error_log('[generar_publicaciones_multiples] - Inicio de la función.');

    // Buscar todos los social_post con multiple en 1 o true
    $args = array(
        'post_type'      => 'social_post',
        'posts_per_page' => 1, // Procesar solo 1 post por ejecución
        'meta_query'     => array(
            array(
                'key'   => 'multiple',
                'value' => '1',
            ),
        ),
        'orderby'        => 'ID',  // Ordenar por ID ascendente
        'order'          => 'ASC',
    );
    $query = new WP_Query($args);

    error_log('[generar_publicaciones_multiples] - Consulta realizada: ' . var_export($args, true));

    if ($query->have_posts()) {
        error_log('[generar_publicaciones_multiples] - Se encontraron posts con la meta "multiple".');

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $author_id = get_post_field('post_author', $post_id);

            error_log('[generar_publicaciones_multiples] - Procesando post ID: ' . $post_id . ', Autor ID: ' . $author_id);

            // Copiar metas si existen
            $paraColab = get_post_meta($post_id, 'paraColab', true);
            $paraDescarga = get_post_meta($post_id, 'paraDescarga', true);
            $artista = get_post_meta($post_id, 'artista', true);
            $fan = get_post_meta($post_id, 'fan', true);
            $rola = get_post_meta($post_id, 'rola', true);
            $sample = get_post_meta($post_id, 'sample', true);
            $tagsUsuario = get_post_meta($post_id, 'tagsUsuario', true);

            error_log('[generar_publicaciones_multiples] - Valores de metas: paraColab: ' . $paraColab . ', paraDescarga: ' . $paraDescarga . ', artista: ' . $artista . ', fan: ' . $fan . ', rola: ' . $rola . ', sample: ' . $sample . ', tagsUsuario: ' . $tagsUsuario);

            $multiples_audios_encontrados = false;
            $ids_nuevos_posts = array(); // Array para almacenar los IDs de los nuevos posts

            // Iterar sobre los posibles audios múltiples
            for ($i = 2; $i <= 30; $i++) {
                $audio_lite_meta_key = 'post_audio_lite_' . $i;
                $audio_meta_key = 'post_audio' . $i;
                $idHash_audioId_key = 'idHash_audioId' . $i;

                $ruta_audio_lite = get_post_meta($post_id, $audio_lite_meta_key, true);
                $audio_id_hash = get_post_meta($post_id, $idHash_audioId_key, true);
                $audio_id = get_post_meta($post_id, $audio_meta_key, true);

                // Si existe el audio lite, generar un nuevo post
                if (! empty($ruta_audio_lite) && ! empty($audio_id_hash) && !empty($audio_id)) {
                    $multiples_audios_encontrados = true;

                    // Obtener la ruta del archivo en el servidor
                    $upload_dir = wp_upload_dir();
                    $ruta_servidor = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ruta_audio_lite);
                    $ruta_original = null; //la ruta original se puede omitir

                    error_log('[generar_publicaciones_multiples] - Ruta del archivo en el servidor: ' . $ruta_servidor);

                    // Crear el nuevo post usando crearAutPost
                    $nuevo_post_id = crearAutPost($ruta_original, $ruta_servidor, $audio_id_hash, $author_id, $post_id);
                    error_log('[generar_publicaciones_multiples] - Resultado de crearAutPost: ' . var_export($nuevo_post_id, true));

                    // Copiar metas al nuevo post si existen y se creó el post
                    if (! is_wp_error($nuevo_post_id) && $nuevo_post_id) {
                        // Guardar el ID del nuevo post en el array
                        $ids_nuevos_posts[] = $nuevo_post_id;
                        error_log('[generar_publicaciones_multiples] - Nuevo post creado con ID: ' . $nuevo_post_id);

                        if (! empty($paraColab)) {
                            update_post_meta($nuevo_post_id, 'paraColab', $paraColab);
                            error_log('[generar_publicaciones_multiples] - Meta paraColab copiada.');
                        }
                        if (! empty($paraDescarga)) {
                            update_post_meta($nuevo_post_id, 'paraDescarga', $paraDescarga);
                            error_log('[generar_publicaciones_multiples] - Meta paraDescarga copiada.');
                        }
                        if (! empty($artista)) {
                            update_post_meta($nuevo_post_id, 'artista', $artista);
                            error_log('[generar_publicaciones_multiples] - Meta artista copiada.');
                        }
                        if (! empty($fan)) {
                            update_post_meta($nuevo_post_id, 'fan', $fan);
                            error_log('[generar_publicaciones_multiples] - Meta fan copiada.');
                        }
                        if (! empty($rola)) {
                            update_post_meta($nuevo_post_id, 'rola', $rola);
                            error_log('[generar_publicaciones_multiples] - Meta rola copiada.');
                        }
                        if (! empty($sample)) {
                            update_post_meta($nuevo_post_id, 'sample', $sample);
                            error_log('[generar_publicaciones_multiples] - Meta sample copiada.');
                        }
                        if (! empty($tagsUsuario)) {
                            update_post_meta($nuevo_post_id, 'tagsUsuario', $tagsUsuario);
                            error_log('[generar_publicaciones_multiples] - Meta tagsUsuario copiada.');
                        }
                        if (! empty($audio_id)) {
                            update_post_meta($nuevo_post_id, 'post_audio', $audio_id);
                            error_log('[generar_publicaciones_multiples] - Meta post_audio copiada.');
                        }

                        // Pausa de 2 segundos entre cada creación de post
                        sleep(2);
                    } else {
                        error_log('[generar_publicaciones_multiples] - Error al crear el nuevo post.');
                    }
                } else {
                }
            }

            // Si no se encontraron múltiples audios, eliminar la meta 'multiple'
            if (! $multiples_audios_encontrados) {
                delete_post_meta($post_id, 'multiple');
                error_log('[generar_publicaciones_multiples] - No se encontraron múltiples audios para el post ID: ' . $post_id . '. Se eliminó la meta "multiple".');
            } else {
                // Si se encontraron múltiples audios, guardar los IDs de los nuevos posts en el post original
                update_post_meta($post_id, 'posts_generados', $ids_nuevos_posts);
                error_log('[generar_publicaciones_multiples] - Se encontraron múltiples audios para el post ID: ' . $post_id . '. IDs de los nuevos posts guardados: ' . implode(', ', $ids_nuevos_posts));
            }
        }
    } else {
        error_log('[generar_publicaciones_multiples] - No se encontraron posts con la meta "multiple".');
    }
    wp_reset_postdata();
    error_log('[generar_publicaciones_multiples] - Fin de la función.');
}

function crearAutPost($rutaOriginal = null, $rutaWpLite = null, $file_id = null, $autor_id = null, $post_original = null)
{
    error_log("[crearAutPost] Inicio crearAutPost con rutaOriginal: $rutaOriginal, rutaWpLite: $rutaWpLite, file_id: $file_id");

    if ($autor_id === null) {
        $autor_id = 44;
    }
    $nombre_archivo = pathinfo($rutaOriginal, PATHINFO_FILENAME);
    $carpeta = basename(dirname($rutaOriginal));
    $carpeta_abuela = basename(dirname(dirname($rutaOriginal)));
    $datosAlgoritmo = automaticAudio($rutaWpLite, $nombre_archivo, $carpeta, $carpeta_abuela);

    if (!$datosAlgoritmo) {
        error_log("[crearAutPost] Error: automaticAudio falló para rutaWpLite: $rutaWpLite");
        eliminarHash($file_id);
        return;
    }

    $descripcion_corta_es = $datosAlgoritmo['descripcion_corta']['en'] ?? '';
    $nombre_generado = $datosAlgoritmo['nombre_corto']['en'] ?? '';

    if (is_array($nombre_generado)) {
        $nombre_generado = $nombre_generado[0] ?? '';
    }

    if ($nombre_generado) {
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- áéíóúÁÉÍÓÚñÑ]/u', '', trim($nombre_generado));
        $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $nombre_final = substr($nombre_generado_limpio . '_' . $id_unica . '_2upra', 0, 60);
    } else {
        error_log("[crearAutPost] Error: nombre_generado está vacío para rutaOriginal: $rutaOriginal");
        eliminarHash($file_id);
        return;
    }

    $extension_original = pathinfo($rutaOriginal, PATHINFO_EXTENSION);
    $nuevaRutaOriginal = dirname($rutaOriginal) . '/' . $nombre_final . '.' . $extension_original;
    if (!file_exists($rutaOriginal) || (file_exists($nuevaRutaOriginal) && !unlink($nuevaRutaOriginal))) {
        error_log("[crearAutPost] Error: No se puede renombrar el archivo original $rutaOriginal a $nuevaRutaOriginal");
        eliminarHash($file_id);
        return;
    }
    if (!rename($rutaOriginal, $nuevaRutaOriginal)) {
        error_log("[crearAutPost] Error: Fallo al renombrar $rutaOriginal a $nuevaRutaOriginal");
        eliminarHash($file_id);
        return;
    }

    if (!file_exists($rutaWpLite)) {
        error_log("[crearAutPost] Error: rutaWpLite $rutaWpLite no existe");
        return;
    }
    $extension_lite = pathinfo($rutaWpLite, PATHINFO_EXTENSION);
    $nuevo_nombre_lite = dirname($rutaWpLite) . '/' . $nombre_final . '_lite.' . $extension_lite;
    if (file_exists($nuevo_nombre_lite) && !unlink($nuevo_nombre_lite)) {
        error_log("[crearAutPost] Error: No se puede eliminar el archivo existente $nuevo_nombre_lite");
        eliminarHash($file_id);
        return;
    }
    if (!rename($rutaWpLite, $nuevo_nombre_lite)) {
        error_log("[crearAutPost] Error: Fallo al renombrar $rutaWpLite a $nuevo_nombre_lite");
        eliminarHash($file_id);
        return;
    }

    if (is_array($descripcion_corta_es)) {
        $descripcion_corta_es = $descripcion_corta_es[0] ?? '';
    }

    $titulo = mb_substr($descripcion_corta_es, 0, 60);
    $post_data = [
        'post_title'    => $titulo,
        'post_content'  => $descripcion_corta_es,
        'post_status'   => 'publish',
        'post_author'   => $autor_id,
        'post_type'     => 'social_post',
    ];

    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        error_log("[crearAutPost] Error: No se pudo crear el post. Error: " . $post_id->get_error_message());
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return;
    }

    update_post_meta($post_id, 'rutaOriginal', $nuevaRutaOriginal);
    update_post_meta($post_id, 'rutaLiteOriginal', $nuevo_nombre_lite);
    update_post_meta($post_id, 'postAut', true);

    $audio_original_id = adjuntarArchivoAut($nuevaRutaOriginal, $post_id, $file_id);
    if (is_wp_error($audio_original_id)) {
        error_log("[crearAutPost] Error: Fallo al adjuntar el archivo original. Error: " . $audio_original_id->get_error_message());
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return $audio_original_id;
    }

    if (file_exists($nuevaRutaOriginal)) {
        unlink($nuevaRutaOriginal);
    }

    $audio_lite_id = adjuntarArchivoAut($nuevo_nombre_lite, $post_id);
    if (is_wp_error($audio_lite_id)) {
        error_log("[crearAutPost] Error: Fallo al adjuntar el archivo lite. Error: " . $audio_lite_id->get_error_message());
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return $audio_lite_id;
    }

    // Metadatos del post
    $existing_meta = get_post_meta($post_id);

    if (!isset($existing_meta['post_audio'])) {
        update_post_meta($post_id, 'post_audio', $audio_original_id);
    }
    if (!isset($existing_meta['post_audio_lite'])) {
        update_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
    }
    if (!isset($existing_meta['paraDescarga'])) {
        update_post_meta($post_id, 'paraDescarga', true);
    }
    if (!isset($existing_meta['nombreOriginal'])) {
        update_post_meta($post_id, 'nombreOriginal', $nombre_archivo);
    }
    if (!isset($existing_meta['carpetaOriginal'])) {
        update_post_meta($post_id, 'carpetaOriginal', $carpeta);
    }
    if (!isset($existing_meta['carpetaAbuelaOriginal'])) {
        update_post_meta($post_id, 'carpetaAbuelaOriginal', $carpeta_abuela);
    }
    if (!isset($existing_meta['audio_bpm'])) {
        update_post_meta($post_id, 'audio_bpm', $datosAlgoritmo['bpm'] ?? null);
    }
    if (!isset($existing_meta['audio_key'])) {
        update_post_meta($post_id, 'audio_key', $datosAlgoritmo['key'] ?? null);
    }
    if (!isset($existing_meta['audio_scale'])) {
        update_post_meta($post_id, 'audio_scale', $datosAlgoritmo['scale'] ?? null);
    }
    if (!isset($existing_meta['datosAlgoritmo'])) {
        update_post_meta($post_id, 'datosAlgoritmo', json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE));
    }

    error_log("[crearAutPost] crearAutPost end con post_id: $post_id");
    return $post_id;
}

/**
 * Programa la ejecución de la función [generar_publicaciones_multiples] cada 30 segundos.
 *
 * @return void
 */
function programar_generacion_publicaciones()
{
    if (! wp_next_scheduled('generar_publicaciones_multiples_evento')) {
        wp_schedule_event(time(), 'cada_treinta_segundos', 'generar_publicaciones_multiples_evento');
        error_log('programar_generacion_publicaciones() - Evento programado.');
    } else {
        error_log('programar_generacion_publicaciones() - El evento ya está programado.');
    }
}
add_action('wp', 'programar_generacion_publicaciones');
add_action('generar_publicaciones_multiples_evento', 'generar_publicaciones_multiples');

/**
 * Define el intervalo personalizado de 30 segundos.
 *
 * @param array $schedules Los intervalos de tiempo programados.
 *
 * @return array Los intervalos de tiempo programados con el nuevo intervalo de 30 segundos.
 */
function agregar_intervalo_treinta_segundos($schedules)
{
    $schedules['cada_treinta_segundos'] = array(
        'interval' => 30,
        'display'  => esc_html__('Cada 30 segundos'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'agregar_intervalo_treinta_segundos');
