<?

function generar_publicaciones_multiples()
{
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


    if ($query->have_posts()) {

        while ($query->have_posts()) {
            $query->the_post();
            $postIdOriginal = get_the_ID();
            $author_id = get_post_field('post_author', $postIdOriginal);


            // Copiar metas si existen
            $paraColab = get_post_meta($postIdOriginal, 'paraColab', true);
            $paraDescarga = get_post_meta($postIdOriginal, 'paraDescarga', true);
            $artista = get_post_meta($postIdOriginal, 'artista', true);
            $fan = get_post_meta($postIdOriginal, 'fan', true);
            $rola = get_post_meta($postIdOriginal, 'rola', true);
            $sample = get_post_meta($postIdOriginal, 'sample', true);
            $tagsUsuario = get_post_meta($postIdOriginal, 'tagsUsuario', true);
            $tienda = get_post_meta($postIdOriginal, 'tienda', true);


            $multiples_audios_encontrados = false;
            $ids_nuevos_posts = array(); // Array para almacenar los IDs de los nuevos posts

            // Iterar sobre los posibles audios múltiples
            for ($i = 2; $i <= 30; $i++) {
                $audio_lite_meta_key = 'post_audio_lite_' . $i;
                $audio_meta_key = 'post_audio' . $i;
                $idHash_audioId_key = 'idHash_audioId' . $i;
                $precio_key = 'precioRola' . $i;
                $name_key = 'nombreRola' . $i;

                $audio_lite_id = get_post_meta($postIdOriginal, $audio_lite_meta_key, true); // Obtiene el ID del adjunto
                $audio_id_hash = get_post_meta($postIdOriginal, $idHash_audioId_key, true);
                $audio_id = get_post_meta($postIdOriginal, $audio_meta_key, true);
                $precio = get_post_meta($postIdOriginal, $precio_key, true);
                $name = get_post_meta($postIdOriginal, $name_key, true);

                // Si existe el audio lite (ahora usando el ID), generar un nuevo post
                if (! empty($audio_lite_id) && ! empty($audio_id_hash) && !empty($audio_id)) {
                    $multiples_audios_encontrados = true;

                    // Obtener la URL del adjunto usando el ID
                    $ruta_audio_lite = wp_get_attachment_url($audio_lite_id);

                    // Obtener la ruta del archivo en el servidor
                    $upload_dir = wp_upload_dir();
                    $ruta_servidor = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ruta_audio_lite);

                    $nuevoPost = crearAutPost('', $ruta_servidor, $audio_id_hash, $author_id, $postIdOriginal);
                    // Copiar metas al nuevo post si existen y se creó el post
                    if (! is_wp_error($nuevoPost) && $nuevoPost) {
                        // Guardar el ID del nuevo post en el array
                        $ids_nuevos_posts[] = $nuevoPost;

                        if (! empty($paraColab)) {
                            update_post_meta($nuevoPost, 'paraColab', $paraColab);
                        }
                        if (! empty($paraDescarga)) {
                            update_post_meta($nuevoPost, 'paraDescarga', $paraDescarga);
                        }
                        if (! empty($artista)) {
                            update_post_meta($nuevoPost, 'artista', $artista);
                        }
                        if (! empty($fan)) {
                            update_post_meta($nuevoPost, 'fan', $fan);
                        }
                        if (! empty($rola)) {
                            update_post_meta($nuevoPost, 'rola', $rola);
                        }
                        if (! empty($tienda)) {
                            update_post_meta($nuevoPost, 'tienda', $sample);
                        }
                        if (! empty($sample)) {
                            update_post_meta($nuevoPost, 'sample', $sample);
                        }
                        if (! empty($tagsUsuario)) {
                            update_post_meta($nuevoPost, 'tagsUsuario', $tagsUsuario);
                        }
                        if (! empty($audio_id)) {
                            update_post_meta($nuevoPost, 'post_audio', $audio_id);
                        }
                        // Aquí se añaden las líneas para guardar precio y nombre
                        if (! empty($precio)) {
                            update_post_meta($nuevoPost, 'precioRola', $precio);
                        }
                        if (! empty($name)) {
                            update_post_meta($nuevoPost, 'nombreRola', $name);
                        }

                        // Eliminar metas del post original después de procesar cada audio_lite
                        delete_post_meta($postIdOriginal, $audio_lite_meta_key);
                        delete_post_meta($postIdOriginal, $audio_meta_key);
                        delete_post_meta($postIdOriginal, $idHash_audioId_key);
                        delete_post_meta($postIdOriginal, $precio_key);
                        delete_post_meta($postIdOriginal, $name_key);

                        // Pausa de 2 segundos entre cada creación de post
                        sleep(2);
                    } else {
                    }
                }
            }

            // Si no se encontraron múltiples audios, eliminar la meta 'multiple'
            if (! $multiples_audios_encontrados) {
                delete_post_meta($postIdOriginal, 'multiple');
            } else {
                // Si se encontraron múltiples audios, guardar los IDs de los nuevos posts en el post original
                update_post_meta($postIdOriginal, 'posts_generados', $ids_nuevos_posts);

                // Verificar si aún quedan audios múltiples por procesar
                $quedan_audios = false;
                for ($i = 2; $i <= 30; $i++) {
                    if (get_post_meta($postIdOriginal, 'post_audio_lite_' . $i, true)) {
                        $quedan_audios = true;
                        break;
                    }
                }

                // Si no quedan audios múltiples, eliminar la meta 'multiple'
                if (! $quedan_audios) {
                    delete_post_meta($postIdOriginal, 'multiple');
                }
            }
        }
    } else {
    }
    wp_reset_postdata();
}

function crearAutPost($rutaOriginal = null, $rutaWpLite = null, $file_id = null, $autor_id = null, $post_original = null)
{

    if ($autor_id === null) {
        $autor_id = 44;
    }

    $nombre_archivo = null;
    $carpeta = null;
    $carpeta_abuela = null;
    $extension_original = null;
    $nuevaRutaOriginal = null;

    // Validar y procesar $rutaOriginal si existe
    if (!empty($rutaOriginal)) {
        if (!file_exists($rutaOriginal)) {
            // Puedes decidir si continuar o retornar aquí, dependiendo de si rutaOriginal es obligatoria
        } else {
            $nombre_archivo = pathinfo($rutaOriginal, PATHINFO_FILENAME);
            $carpeta = basename(dirname($rutaOriginal));
            $carpeta_abuela = basename(dirname(dirname($rutaOriginal)));
            $extension_original = pathinfo($rutaOriginal, PATHINFO_EXTENSION);
        }
    }

    // Validar $rutaWpLite
    if (empty($rutaWpLite)) {
        return;
    }

    if (!file_exists($rutaWpLite)) {
        return;
    }

    //Automatic audio solo necesita la ruta lite para funcionar
    $datosAlgoritmo = automaticAudio($rutaWpLite, $nombre_archivo, $carpeta, $carpeta_abuela);

    if (!$datosAlgoritmo) {
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
        eliminarHash($file_id);
        return;
    }

    // Manejo de renombrado de archivos, solo si $rutaOriginal existe
    if (!empty($rutaOriginal) && file_exists($rutaOriginal)) {
        $nuevaRutaOriginal = dirname($rutaOriginal) . '/' . $nombre_final . '.' . $extension_original;
        if (file_exists($nuevaRutaOriginal) && !unlink($nuevaRutaOriginal)) {
            eliminarHash($file_id);
            return;
        }
        if (!rename($rutaOriginal, $nuevaRutaOriginal)) {
            eliminarHash($file_id);
            return;
        }
    }

    // Manejo de renombrado de rutaWpLite
    $extension_lite = pathinfo($rutaWpLite, PATHINFO_EXTENSION);
    $nuevo_nombre_lite = dirname($rutaWpLite) . '/' . $nombre_final . '_lite.' . $extension_lite;
    if (file_exists($nuevo_nombre_lite) && !unlink($nuevo_nombre_lite)) {
        eliminarHash($file_id);
        return;
    }
    if (!rename($rutaWpLite, $nuevo_nombre_lite)) {
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
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return;
    }

    // Solo actualiza rutaOriginal si existe
    if (!empty($nuevaRutaOriginal)) {
        update_post_meta($post_id, 'rutaOriginal', $nuevaRutaOriginal);
    }

    update_post_meta($post_id, 'rutaLiteOriginal', $nuevo_nombre_lite);
    update_post_meta($post_id, 'postAut', true);

    // Adjuntar archivo original solo si $rutaOriginal existe
    $audio_original_id = null; // Inicializar para evitar errores si no se adjunta
    if (!empty($nuevaRutaOriginal)) {
        $audio_original_id = adjuntarArchivoAut($nuevaRutaOriginal, $post_id, $file_id);
        if (is_wp_error($audio_original_id)) {
            wp_delete_post($post_id, true);
            eliminarHash($file_id);
            return $audio_original_id;
        }
        if (file_exists($nuevaRutaOriginal)) {
            unlink($nuevaRutaOriginal);
        }
    }

    $audio_lite_id = adjuntarArchivoAut($nuevo_nombre_lite, $post_id);
    if (is_wp_error($audio_lite_id)) {
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return $audio_lite_id;
    }

    // Metadatos del post
    $existing_meta = get_post_meta($post_id);

    // Solo actualiza post_audio si se adjuntó un archivo original
    if (!empty($audio_original_id) && !isset($existing_meta['post_audio'])) {
        update_post_meta($post_id, 'post_audio', $audio_original_id);
    }

    if (!isset($existing_meta['post_audio_lite'])) {
        update_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
    }
    if (!isset($existing_meta['paraDescarga'])) {
        update_post_meta($post_id, 'paraDescarga', true);
    }

    // Solo actualiza estos metadatos si $rutaOriginal existía
    if (!empty($rutaOriginal)) {
        if (!isset($existing_meta['nombreOriginal'])) {
            update_post_meta($post_id, 'nombreOriginal', $nombre_archivo);
        }
        if (!isset($existing_meta['carpetaOriginal'])) {
            update_post_meta($post_id, 'carpetaOriginal', $carpeta);
        }
        if (!isset($existing_meta['carpetaAbuelaOriginal'])) {
            update_post_meta($post_id, 'carpetaAbuelaOriginal', $carpeta_abuela);
        }
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

    return $post_id;
}




function programar_generacion_publicaciones()
{
    if (! wp_next_scheduled('generar_publicaciones_multiples_evento')) {
        wp_schedule_event(time(), 'cada_treinta_segundos', 'generar_publicaciones_multiples_evento');
    } else {
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
        'interval' => 60,
        'display'  => esc_html__('Cada 30 segundos'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'agregar_intervalo_treinta_segundos');