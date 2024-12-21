<?
function multiplesPost($postIdOriginal)
{
    if (!get_post($postIdOriginal)) {
        error_log("El post con ID: {$postIdOriginal} no existe.");
        return;
    }

    // Verifica si el post es del tipo 'social_post'
    if (get_post_type($postIdOriginal) !== 'social_post') {
        error_log("El post con ID: {$postIdOriginal} no es del tipo 'social_post'.");
        return;
    }

    // Verifica si el post tiene el meta 'multiple' igual a 1
    $isMultiple = get_post_meta($postIdOriginal, 'multiple', true);
    if ($isMultiple !== '1') {
        error_log("El post con ID: {$postIdOriginal} no tiene el meta 'multiple' igual a 1.");
        return;
    }

    $author_id = get_post_field('post_author', $postIdOriginal);
    $paraColab = get_post_meta($postIdOriginal, 'paraColab', true);
    $paraDescarga = get_post_meta($postIdOriginal, 'paraDescarga', true);
    $artista = get_post_meta($postIdOriginal, 'artista', true);
    $fan = get_post_meta($postIdOriginal, 'fan', true);
    $rola = get_post_meta($postIdOriginal, 'rola', true);
    $sample = get_post_meta($postIdOriginal, 'sample', true);
    $tagsUsuario = get_post_meta($postIdOriginal, 'tagsUsuario', true);
    $tienda = get_post_meta($postIdOriginal, 'tienda', true);
    $nombreLanzamiento = get_post_meta($postIdOriginal, 'nombreLanzamiento', true);

    list($multiples_audios_encontrados, $ids_nuevos_posts) = procesarAudiosMultiples($postIdOriginal, $author_id, $paraColab, $paraDescarga, $artista, $fan, $rola, $sample, $tagsUsuario, $tienda, $nombreLanzamiento);

    if (!$multiples_audios_encontrados) {
        delete_post_meta($postIdOriginal, 'multiple');
    } else {
        // Verifica si hay IDs de nuevos posts antes de actualizar
        if (!empty($ids_nuevos_posts)) {
            update_post_meta($postIdOriginal, 'posts_generados', $ids_nuevos_posts);
        }
        $quedan_audios = false;
        for ($i = 2; $i <= 30; $i++) {
            if (get_post_meta($postIdOriginal, 'post_audio_lite_' . $i, true)) {
                $quedan_audios = true;
                break;
            }
        }
        if (!$quedan_audios) {
            delete_post_meta($postIdOriginal, 'multiple');
        }
    }

}
function procesarAudiosMultiples($postIdOriginal, $author_id, $paraColab, $paraDescarga, $artista, $fan, $rola, $sample, $tagsUsuario, $tienda, $nombreLanzamiento)
{
    $multiples_audios_encontrados = false;
    $ids_nuevos_posts = array();

    // Obtener el ID de la imagen destacada (foto de portada)
    $imagen_destacada_id = get_post_thumbnail_id($postIdOriginal);

    for ($i = 2; $i <= 30; $i++) {
        $audio_lite_meta_key = 'post_audio_lite_' . $i;
        $audio_meta_key = 'post_audio' . $i;
        $idHash_audioId_key = 'idHash_audioId' . $i;
        $precio_key = 'precioRola' . $i;
        $name_key = 'nombreRola' . $i;
        $audioUrl_key = 'audioUrl' . $i;
        $audio_duration_key = 'audio_duration_' . $i;
        
        $audio_lite_id = get_post_meta($postIdOriginal, $audio_lite_meta_key, true);
        $audio_id_hash = get_post_meta($postIdOriginal, $idHash_audioId_key, true);
        $audio_id = get_post_meta($postIdOriginal, $audio_meta_key, true);
        $precio = get_post_meta($postIdOriginal, $precio_key, true);
        $name = get_post_meta($postIdOriginal, $name_key, true);
        $audioUrl = get_post_meta($postIdOriginal, $audioUrl_key, true);
        $audio_duration = get_post_meta($postIdOriginal, $audio_duration_key, true);

        if (! empty($audio_lite_id) && ! empty($audio_id_hash) && !empty($audio_id)) {
            $multiples_audios_encontrados = true;
            $ruta_audio_lite = wp_get_attachment_url($audio_lite_id);
            $upload_dir = wp_upload_dir();
            $ruta_servidor = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ruta_audio_lite);
            $nuevoPost = crearAutPost('', $ruta_servidor, $audio_id_hash, $author_id, $postIdOriginal);

            if (! is_wp_error($nuevoPost) && $nuevoPost) {
                $ids_nuevos_posts[] = $nuevoPost;

                // Copiar la imagen destacada al nuevo post
                if (!empty($imagen_destacada_id)) {
                    set_post_thumbnail($nuevoPost, $imagen_destacada_id);
                }

                // Copiar los demás metadatos
                if (! empty($audioUrl)) {
                    update_post_meta($nuevoPost, 'audioUrl', $audioUrl);
                }
                if (! empty($audio_duration)) {
                    update_post_meta($nuevoPost, 'audio_duration_1', $audio_duration);
                }
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
                if (! empty($precio)) {
                    update_post_meta($nuevoPost, 'precioRola', $precio);
                }
                if (! empty($name)) {
                    update_post_meta($nuevoPost, 'nombreRola', $name);
                }
                if (! empty($nombreLanzamiento)) {
                    update_post_meta($nuevoPost, 'nombreLanzamiento', $nombreLanzamiento);
                }
                delete_post_meta($postIdOriginal, $audio_lite_meta_key);
                delete_post_meta($postIdOriginal, $audio_meta_key);
                delete_post_meta($postIdOriginal, $idHash_audioId_key);
                delete_post_meta($postIdOriginal, $precio_key);
                delete_post_meta($postIdOriginal, $name_key);
                delete_post_meta($postIdOriginal, $audioUrl_key);
                delete_post_meta($postIdOriginal, $audio_duration_key);
                sleep(2);
            }
        }
    }
    return array($multiples_audios_encontrados, $ids_nuevos_posts);
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

    if ($autor_id === 44) {
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


