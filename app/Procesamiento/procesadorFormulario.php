<?php

function subidaDePost()
{
    guardarLog("---------------------------------------------");
    guardarLog("INICIO subidaDePost");

    // Registrar los datos recibidos
    guardarLog("Contenido de \$_FILES: " . print_r($_FILES, true));
    guardarLog("Contenido de \$_POST: " . print_r($_POST, true));

    if (isset($_FILES['post_image'])) {
        guardarLog("Imagen recibida: " . print_r($_FILES['post_image'], true));
        // Procesa la imagen aquí
    } else {
        guardarLog("No se recibió ninguna imagen");
    }

    // Verificar si es una solicitud AJAX válida y si el usuario tiene permisos
    if (
        !wp_verify_nonce($_POST['social_post_nonce'] ?? '', 'social-post-nonce')
        || !is_user_logged_in()
        || !current_user_can('edit_posts')
    ) {
        guardarLog("Error de permisos o nonce inválido en subidaDePost");
        guardarLog("Error: Permisos insuficientes o nonce inválido");
        wp_send_json_error(['message' => 'No tienes permiso para realizar esta acción.'], 403);
    }

    // Sanitizar y preparar datos de la publicación
    $post_content = sanitize_textarea_field($_POST['post_content'] ?? '');

    $is_rola = isset($_POST['rola']) && $_POST['rola'] == 1;
    $is_sample = isset($_POST['sample']) && $_POST['sample'] == 1;
    $is_post = isset($_POST['socialpost']) && $_POST['socialpost'] == 1;
    $isMomento = isset($_POST['momento']) && $_POST['momento'] == 1;

    if ($is_sample) {
        $post_content = sanitize_textarea_field($_POST['name_Rola1'] ?? '');
        $post_title = wp_trim_words($post_content, 15, '...');
        $artistic_name = get_the_author_meta('display_name', get_current_user_id());
    } elseif ($is_post) {
        $post_content = sanitize_textarea_field($_POST['post_content'] ?? '');
        $post_title = wp_trim_words($post_content, 15, '...');
        $artistic_name = get_the_author_meta('display_name', get_current_user_id());
    } else {
        $post_title = wp_trim_words($post_content, 15, '...');
        $artistic_name = sanitize_textarea_field($_POST['artistic_name'] ?? '');
    }

    $post_status = ($_POST['rola'] ?? '') === '1' ? 'pending' : 'publish';
    $post_data = [
        'post_title'    => $post_title,
        'post_content'  => $post_content,
        'post_status'   => $post_status,
        'post_author'   => get_current_user_id(),
        'post_type'     => 'social_post',
    ];

    $post_id = wp_insert_post($post_data);

    // Manejar error al crear la publicación
    if (is_wp_error($post_id)) {
        guardarLog("Error al crear la publicación en subidaDePost: " . $post_id->get_error_message());
        wp_send_json_error(['message' => 'Error al crear la publicación.'], 500);
    }

    // Actualizar metadatos de la publicación
    update_post_meta($post_id, '_post_puntuacion_final', 100);
    update_post_meta($post_id, 'paraDescarga', isset($_POST['paraDescarga']) ? 1 : 0);
    update_post_meta($post_id, 'momento', $isMomento ? 1 : 0);
    update_post_meta($post_id, 'sample', $is_sample ? 1 : 0);
    update_post_meta($post_id, 'isPost', $is_post ? 1 : 0);
    update_post_meta($post_id, 'rola', $is_rola ? 1 : 0);
    update_post_meta($post_id, 'esExclusivo', isset($_POST['esExclusivo']) ? 1 : 0);
    update_post_meta($post_id, 'paraColab', isset($_POST['paraColab']) ? 1 : 0);
    update_post_meta($post_id, 'real_name', sanitize_textarea_field($_POST['real_name'] ?? ''));
    update_post_meta($post_id, 'artistic_name', $artistic_name);
    update_post_meta($post_id, 'album', sanitize_textarea_field($_POST['album'] ?? ''));
    update_post_meta($post_id, 'email', sanitize_email($_POST['email'] ?? ''));
    update_post_meta($post_id, 'public', isset($_POST['public']) ? 1 : 0);

    // Procesar y guardar los tags
    $tags = sanitize_text_field($_POST['post_tags'] ?? '');
    if (!empty($tags)) {
        $tags_array = explode(',', $tags); 
        wp_set_post_tags($post_id, $tags_array, false);
    }

    // Procesar y actualizar el precio de la publicación
    if (!empty($_POST['post_price'])) {
        $post_price = trim(str_replace('$', '', sanitize_text_field($_POST['post_price'])));
        if (is_numeric($post_price) && floatval($post_price) >= 0) {
            update_post_meta($post_id, 'post_price', intval(floatval($post_price) * 1));
        } else {
            wp_die('El precio proporcionado es inválido.');
        }
    }

    $handleMediaUpload = function ($fileKey) use ($post_id) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        guardarLog("Intentando cargar el archivo con clave: {$fileKey} para el post {$post_id}");

        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            guardarLog("No se encontró archivo para subir con la clave: {$fileKey}");
            return false;
        }

        $attachment_id = media_handle_upload($fileKey, $post_id);

        if (is_wp_error($attachment_id)) {
            guardarLog("Error al subir el archivo: " . $attachment_id->get_error_message());
            return false;
        }

        guardarLog("Archivo subido exitosamente. ID de adjunto: {$attachment_id}");
        return $attachment_id;
    };

    //Procesar URL
    function procesarArchivoURL($post_id, $field_name)
    {
        $archivo_id = false;
        if (isset($_POST[$field_name]) && !empty($_POST[$field_name])) {
            $url = esc_url_raw($_POST[$field_name]);
            $parsed_url = wp_parse_url($url);
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);

            if (file_exists($file_path)) {
                $archivo_id = attachment_url_to_postid($url);
                if (!$archivo_id) {
                    $file_array = array(
                        'name' => basename($file_path),
                        'tmp_name' => $file_path
                    );
                    $archivo_id = media_handle_sideload($file_array, $post_id);
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        if ($archivo_id && !is_wp_error($archivo_id)) {
            update_post_meta($post_id, $field_name, $archivo_id);
            return true;
        }

        return false;
    }
    if (isset($_POST['archivo_url']) && !empty($_POST['archivo_url'])) {
        procesarArchivoURL($post_id, 'archivo_url');
    }

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] != 4) {
        $image_id = $handleMediaUpload('post_image');
        if ($image_id) {
            $result = set_post_thumbnail($post_id, $image_id);
            if ($result === false) {
                guardarLog("Error al establecer la imagen como miniatura del post. Post ID: $post_id, Image ID: $image_id");
            } else {
                guardarLog("Imagen establecida correctamente como miniatura. Post ID: $post_id, Image ID: $image_id");
            }
        } else {
            guardarLog("Error al subir la imagen. Detalles del archivo: " . print_r($_FILES['post_image'], true));
        }
    } else {
        guardarLog("No se subió ninguna imagen o hubo un error en la subida.");
    }

    function procesarAudio($post_id, $field_name, $handleMediaUpload, $index, $is_post)
    {
        guardarLog("+-----------------------------------------------+");
        guardarLog("Procesando audio para {$field_name} en el post {$post_id}");

        $audio_id = false;
        guardarLog("Contenido de \$_POST[$field_name]: " . (isset($_POST[$field_name]) ? $_POST[$field_name] : 'No definido'));

        if (isset($_POST[$field_name]) && !empty($_POST[$field_name])) {
            $url = esc_url_raw($_POST[$field_name]);
            guardarLog("URL del audio proporcionada directamente en POST: {$url}");

            $parsed_url = wp_parse_url($url);
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);

            if (file_exists($file_path)) {
                $audio_id = attachment_url_to_postid($url);
                if (!$audio_id) {
                    $file_array = array(
                        'name' => basename($file_path),
                        'tmp_name' => $file_path
                    );
                    $audio_id = media_handle_sideload($file_array, $post_id);
                }
                guardarLog("Archivo encontrado en el servidor: {$file_path}, ID de adjunto: {$audio_id}");
            } else {
                guardarLog("El archivo no se encuentra en el servidor: {$file_path}");
                return false;
            }
        } else {
            guardarLog("No se proporcionó URL para {$field_name}");
            return false;
        }

        if ($audio_id && !is_wp_error($audio_id)) {
            $post = get_post($post_id);
            $author = get_userdata($post->post_author);
            $file_path = get_attached_file($audio_id);
            $info = pathinfo($file_path);

            guardarLog("Archivo adjunto procesado, ruta: {$file_path}, info: " . print_r($info, true));

            $new_filename = sprintf(
                '2upra_%s_%s.%s',
                sanitize_file_name(mb_substr($author->user_login, 0, 20)),
                sanitize_file_name(mb_substr($post->post_content, 0, 40)),
                $info['extension']
            );

            $new_file_path = $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename;

            // Log de depuración adicional
            guardarLog("Intentando renombrar el archivo: {$file_path} a {$new_file_path}");

            if (rename($file_path, $new_file_path)) {
                update_attached_file($audio_id, $new_file_path);
                update_post_meta($post_id, $field_name, $audio_id);
                if ($is_post) {
                    update_post_meta($post_id, 'sample', true);
                    guardarLog("Metadato 'sample' agregado con valor 'true'");
                }
                guardarLog("Archivo renombrado a: {$new_file_path}");
                procesarAudioLigero($post_id, $audio_id, $index);
                return true;
            } else {
                guardarLog("Error al renombrar el archivo {$file_path} a {$new_file_path}");
                return false;
            }
        }

        guardarLog("Error procesando el archivo para {$field_name}");
        return false;
    }



    $max_audios = 21;
    $errors = [];
    $audio_count = 0;

    guardarLog("Iniciando procesamiento de audios");

    for ($i = 1; $i <= $max_audios; $i++) {
        $field_name = "post_audio{$i}";
        $audio_procesado = false;

        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] != 4) {
            $audio_procesado = procesarAudio($post_id, $field_name, $handleMediaUpload, $i, $is_post);
        } elseif (isset($_POST[$field_name]) && $_POST[$field_name] !== 'undefined' && !empty($_POST[$field_name])) {
            $audio_procesado = procesarAudio($post_id, $field_name, $handleMediaUpload, $i, $is_post);
        }

        if ($audio_procesado) {
            $audio_count++;
        } elseif (!$audio_procesado && (isset($_FILES[$field_name]) || isset($_POST[$field_name]))) {
            $errors[] = "Error al procesar {$field_name}";
        }
    }

    if (!empty($errors)) {
        guardarLog("Errores encontrados en " . count($errors) . " audios: " . implode(', ', $errors));
    } elseif ($audio_count > 0) {
        guardarLog("Todos los audios procesados correctamente. Total: {$audio_count}");
    } else {
        guardarLog("No se procesaron audios.");
    }

    if ($is_sample || $is_post || $is_rola) {
        // Obtener los valores de las metas existentes
        $post_audio_hd_1 = get_post_meta($post_id, 'post_audio_hd_1', true);
        $post_audio_lite_1 = get_post_meta($post_id, 'post_audio_lite_1', true);
        $post_audio1 = get_post_meta($post_id, 'post_audio1', true);

        // Renombrar las metas conservando los valores
        if ($post_audio_hd_1 !== '') {
            update_post_meta($post_id, 'post_audio_hd', $post_audio_hd_1);
            delete_post_meta($post_id, 'post_audio_hd_1');
        }

        if ($post_audio_lite_1 !== '') {
            update_post_meta($post_id, 'post_audio_lite', $post_audio_lite_1);
            delete_post_meta($post_id, 'post_audio_lite_1');
        }

        if ($post_audio1 !== '') {
            update_post_meta($post_id, 'post_audio', $post_audio1);
            delete_post_meta($post_id, 'post_audio1');
        }
    }
    if ($audio_count >= 2) {
        update_post_meta($post_id, 'albumRolas', true);
        guardarLog("Metadato 'albumRolas' agregado con valor 'true'");
    }
    if ($is_post && $audio_count >= 1) {
        update_post_meta($post_id, 'sample', true);
        guardarLog("Metadato 'sample' agregado con valor 'true'");
    }
    if (!$is_sample && !$is_post && $audio_count === 1) {
        update_post_meta($post_id, 'rola', true);
        guardarLog("Metadato 'rola' agregado con valor 'true'");
    }

    // Función para procesar nombres de rolas
    function procesarNameRolas($post_id)
    {
        guardarLog("procesarNameRolas iniciado con post_id: {$post_id}");
    
        $max_rolas = 20;
        $rolas = [];
        $campos_vacios = 0;
        $campos_no_recibidos = 0;
    
        for ($i = 1; $i <= $max_rolas; $i++) {
            $field_name = "name_Rola{$i}";
    
            if (isset($_POST[$field_name])) {
                $valor = trim($_POST[$field_name]);
                if (!empty($valor)) {
                    $rolas[] = sanitize_textarea_field($valor);
                } else {
                    $campos_vacios++;
                }
            } else {
                $campos_no_recibidos++;
            }
        }
    
        if (!empty($rolas)) {
            update_post_meta($post_id, 'rolas_meta_key', $rolas);
            guardarLog("Metadatos actualizados para post_id: {$post_id} con " . count($rolas) . " rolas: " . implode(", ", $rolas));
        } else {
            guardarLog("No se encontraron rolas válidas para post_id: {$post_id}");
        }
    
        // Guardar log resumen
        $resumen = "Resumen de procesamiento para post_id: {$post_id}: ";
        $resumen .= count($rolas) . " rolas válidas agregadas, ";
        $resumen .= "{$campos_vacios} campos vacíos, ";
        $resumen .= "{$campos_no_recibidos} campos no recibidos.";
    
        guardarLog($resumen);
    
        return $rolas;
    }
    // Procesar nombres de rolas
    $rolas = procesarNameRolas($post_id);

    if (!empty($errors)) {
        error_log("Errores al procesar audios en subidaDePost: " . implode(", ", $errors));
        wp_die('Error al procesar los audios: ' . implode(", ", $errors));
    }
    // Obtener y guardar logs de datos de usuario y otros detalles
    $user_info = get_userdata(get_current_user_id());
    $user_name = $user_info->user_login;
    guardarLog("Usuario obtenido: {$user_name} con ID: " . get_current_user_id());

    function extractSimpleList($tag_type)
    {
        if (isset($_POST[$tag_type]) && !empty($_POST[$tag_type])) {
            $tags_string = trim($_POST[$tag_type]);
            $tags_array = array_map('trim', explode(',', $tags_string));
            return $tags_array;
        }
        return [];
    }

    $additional_data = [
        'post_tags' => extractSimpleList('post_tags'),
        'genre_tags' => extractSimpleList('genre_tags'),
        'instrument_tags' => extractSimpleList('instrument_tags'),
        'data' => $post_content,
        'username' => $user_name,
        'rolas' => $rolas,
    ];

    guardarLog("Datos adicionales compilados para post_id: {$post_id}");
    // Codificar los datos adicionales en JSON y actualizar metadatos
    if ($additional_data_json = json_encode($additional_data)) {
        update_post_meta($post_id, 'additional_search_data', $additional_data_json);
        guardarLog("Metadatos de búsqueda adicionales actualizados para post_id: {$post_id}");
    } else {
        guardarLog("Error al codificar datos adicionales a JSON para post_id: {$post_id}");
    }

    // Agregar Pinkys al usuario y enviar notificación
    $current_user_id = get_current_user_id();
    agregar_pinkys_al_usuario($current_user_id, 1);
    guardarLog("Se ha agregado 1 Pinky al usuario con ID: {$current_user_id}");

    insertar_notificacion(
        $current_user_id,
        '¡Tu nueva publicación ha sido creada con éxito! Has recibido un 1 Pinky',
        get_permalink($post_id),
        $current_user_id
    );

    guardarLog("Notificación enviada al usuario con ID: {$current_user_id} por la publicación con post_id: {$post_id}");
    // Enviar respuesta exitosa
    echo json_encode(['success' => true, 'message' => 'Publicación creada exitosamente.']);
    process_album_post($post_id);
    wp_die();
    guardarLog("Fin subidaDepost");
    guardarLog("---------------------------------------------");
}


add_action('wp_ajax_submit_social_post', 'subidaDePost');


*/







