<?php
// ACCIÓN PARA PUBLCIAR
function subidaDePost()
{
    guardar_log("---------------------------------------------");
    guardar_log("INICIO subidaDePost");

    // Registrar los datos recibidos
    guardar_log("Contenido de \$_FILES: " . print_r($_FILES, true));
    guardar_log("Contenido de \$_POST: " . print_r($_POST, true));

    if (isset($_FILES['post_image'])) {
        guardar_log("Imagen recibida: " . print_r($_FILES['post_image'], true));
        // Procesa la imagen aquí
    } else {
        guardar_log("No se recibió ninguna imagen");
    }

    // Verificar si es una solicitud AJAX válida y si el usuario tiene permisos
    if (
        !wp_verify_nonce($_POST['social_post_nonce'] ?? '', 'social-post-nonce')
        || !is_user_logged_in()
        || !current_user_can('edit_posts')
    ) {
        guardar_log("Error de permisos o nonce inválido en subidaDePost");
        guardar_log("Error: Permisos insuficientes o nonce inválido");
        wp_send_json_error(['message' => 'No tienes permiso para realizar esta acción.'], 403);
    }

    // Sanitizar y preparar datos de la publicación
    $post_content = sanitize_textarea_field($_POST['post_content'] ?? '');

    // Verificar, post o momento si es un sample
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
        guardar_log("Error al crear la publicación en subidaDePost: " . $post_id->get_error_message());
        wp_send_json_error(['message' => 'Error al crear la publicación.'], 500);
    }

    // Actualizar metadatos de la publicación
    update_post_meta($post_id, '_post_puntuacion_final', 100);
    update_post_meta($post_id, 'allow_download', isset($_POST['allow_download']) ? 1 : 0);
    update_post_meta($post_id, 'momento', $isMomento ? 1 : 0);
    update_post_meta($post_id, 'sample', $is_sample ? 1 : 0);
    update_post_meta($post_id, 'isPost', $is_post ? 1 : 0);
    update_post_meta($post_id, 'rola', $is_rola ? 1 : 0);
    update_post_meta($post_id, 'content-block', isset($_POST['content-block']) ? 1 : 0);
    update_post_meta($post_id, 'para_colab', isset($_POST['para_colab']) ? 1 : 0);
    update_post_meta($post_id, 'real_name', sanitize_textarea_field($_POST['real_name'] ?? ''));
    update_post_meta($post_id, 'artistic_name', $artistic_name);
    update_post_meta($post_id, 'album', sanitize_textarea_field($_POST['album'] ?? ''));
    update_post_meta($post_id, 'email', sanitize_email($_POST['email'] ?? ''));
    update_post_meta($post_id, 'public', isset($_POST['public']) ? 1 : 0);

    // Procesar y guardar los tags
    $tags = sanitize_text_field($_POST['post_tags'] ?? '');
    if (!empty($tags)) {
        $tags_array = explode(',', $tags); // Asumiendo que los tags están separados por comas
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
    
        guardar_log("Intentando cargar el archivo con clave: {$fileKey} para el post {$post_id}");
    
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
            guardar_log("No se encontró archivo para subir con la clave: {$fileKey}");
            return false;
        }
    
        $attachment_id = media_handle_upload($fileKey, $post_id);
    
        if (is_wp_error($attachment_id)) {
            guardar_log("Error al subir el archivo: " . $attachment_id->get_error_message());
            return false;
        }
    
        guardar_log("Archivo subido exitosamente. ID de adjunto: {$attachment_id}");
        return $attachment_id;
    };

    function procesarArchivoURL($post_id, $field_name)
    {
        guardar_log("+-----------------------------------------------+");
        guardar_log("Procesando archivo para {$field_name} en el post {$post_id}");

        $archivo_id = false;
        // Log para depuración
        guardar_log("Contenido de \$_POST[$field_name]: " . (isset($_POST[$field_name]) ? $_POST[$field_name] : 'No definido'));

        // Comprobar si se ha proporcionado una URL directamente en el campo
        if (isset($_POST[$field_name]) && !empty($_POST[$field_name])) {
            $url = esc_url_raw($_POST[$field_name]);
            guardar_log("URL del archivo proporcionada directamente en POST: {$url}");

            // Procesar la URL
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
                guardar_log("Archivo encontrado en el servidor: {$file_path}, ID de adjunto: {$archivo_id}");
            } else {
                guardar_log("El archivo no se encuentra en el servidor: {$file_path}");
                return false;
            }
        } else {
            guardar_log("No se proporcionó URL para {$field_name}");
            return false;
        }

        if ($archivo_id && !is_wp_error($archivo_id)) {
            update_post_meta($post_id, $field_name, $archivo_id);
            guardar_log("Archivo procesado y guardado con éxito, ID de adjunto: {$archivo_id}");
            return true;
        }

        guardar_log("Error procesando el archivo para {$field_name}");
        return false;
    }

    if (isset($_POST['archivo_url']) && !empty($_POST['archivo_url'])) {
        procesarArchivoURL($post_id, 'archivo_url');
    } else {
        guardar_log("No se proporcionó un archivo_url en el formulario.");
    }

    /* 

    2024-08-25 19:25:02 - Contenido de $_FILES: Array
(
    [post_image] => Array
        (
            [name] => 1107885577070174843_☆ ★.jpg
            [type] => image/jpeg
            [tmp_name] => /tmp/phpj4Y1Bk
            [error] => 0
            [size] => 62799
        )

)

    2024-08-25 19:25:02 - Imagen recibida: Array
(
    [name] => 1107885577070174843_☆ ★.jpg
    [type] => image/jpeg
    [tmp_name] => /tmp/phpj4Y1Bk
    [error] => 0
    [size] => 62799
)

2024-08-25 19:25:04 - Intentando cargar el archivo con clave: post_image para el post 231384
2024-08-25 19:25:04 - Error al establecer la imagen como miniatura del post.

    */

    // Manejar la subida de imagen
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] != 4) {
        $image_id = $handleMediaUpload('post_image');
        if ($image_id) {
            $result = set_post_thumbnail($post_id, $image_id);
            if ($result === false) {
                guardar_log("Error al establecer la imagen como miniatura del post. Post ID: $post_id, Image ID: $image_id");
            } else {
                guardar_log("Imagen establecida correctamente como miniatura. Post ID: $post_id, Image ID: $image_id");
            }
        } else {
            guardar_log("Error al subir la imagen. Detalles del archivo: " . print_r($_FILES['post_image'], true));
        }
    } else {
        guardar_log("No se subió ninguna imagen o hubo un error en la subida.");
    }

    function procesarAudio($post_id, $field_name, $handleMediaUpload, $index, $is_post)
    {
        guardar_log("+-----------------------------------------------+");
        guardar_log("Procesando audio para {$field_name} en el post {$post_id}");

        $audio_id = false;
        guardar_log("Contenido de \$_POST[$field_name]: " . (isset($_POST[$field_name]) ? $_POST[$field_name] : 'No definido'));

        if (isset($_POST[$field_name]) && !empty($_POST[$field_name])) {
            $url = esc_url_raw($_POST[$field_name]);
            guardar_log("URL del audio proporcionada directamente en POST: {$url}");

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
                guardar_log("Archivo encontrado en el servidor: {$file_path}, ID de adjunto: {$audio_id}");
            } else {
                guardar_log("El archivo no se encuentra en el servidor: {$file_path}");
                return false;
            }
        } else {
            guardar_log("No se proporcionó URL para {$field_name}");
            return false;
        }

        if ($audio_id && !is_wp_error($audio_id)) {
            $post = get_post($post_id);
            $author = get_userdata($post->post_author);
            $file_path = get_attached_file($audio_id);
            $info = pathinfo($file_path);

            guardar_log("Archivo adjunto procesado, ruta: {$file_path}, info: " . print_r($info, true));

            $new_filename = sprintf(
                '2upra_%s_%s.%s',
                sanitize_file_name(mb_substr($author->user_login, 0, 20)),
                sanitize_file_name(mb_substr($post->post_content, 0, 40)),
                $info['extension']
            );

            $new_file_path = $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename;

            // Log de depuración adicional
            guardar_log("Intentando renombrar el archivo: {$file_path} a {$new_file_path}");

            if (rename($file_path, $new_file_path)) {
                update_attached_file($audio_id, $new_file_path);
                update_post_meta($post_id, $field_name, $audio_id);
                if ($is_post) {
                    update_post_meta($post_id, 'sample', true);
                    guardar_log("Metadato 'sample' agregado con valor 'true'");
                }
                guardar_log("Archivo renombrado a: {$new_file_path}");
                procesarAudioLigero($post_id, $audio_id, $index);
                return true;
            } else {
                guardar_log("Error al renombrar el archivo {$file_path} a {$new_file_path}");
                return false;
            }
        }

        guardar_log("Error procesando el archivo para {$field_name}");
        return false;
    }



    $max_audios = 21;
    $errors = [];
    $audio_count = 0;

    guardar_log("Iniciando procesamiento de audios");

    for ($i = 1; $i <= $max_audios; $i++) {
        $field_name = "post_audio{$i}";

        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] != 4) {
            if (procesarAudio($post_id, $field_name, $handleMediaUpload, $i, $is_post)) {
                $audio_count++;
                guardar_log("{$field_name} procesado correctamente");
            } else {
                $errors[] = "Error al procesar {$field_name}";
                guardar_log("Error al procesar {$field_name}");
            }
        } elseif (isset($_POST[$field_name]) && $_POST[$field_name] !== 'undefined' && !empty($_POST[$field_name])) {
            // Procesar URL de audio
            if (procesarAudio($post_id, $field_name, $handleMediaUpload, $i, $is_post)) {
                $audio_count++;
                guardar_log("{$field_name} procesado correctamente");
            } else {
                $errors[] = "Error al procesar {$field_name}";
                guardar_log("Error al procesar {$field_name}");
            }
        } else {
            guardar_log("{$field_name} no presente o vacío");
        }
    }

    if (!empty($errors)) {
        guardar_log("Errores encontrados: " . print_r($errors, true));
    } elseif ($audio_count > 0) {
        guardar_log("Todos los audios procesados correctamente.");
    } else {
        guardar_log("No se procesaron audios.");
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
        guardar_log("Metadato 'albumRolas' agregado con valor 'true'");
    }
    if ($is_post && $audio_count >= 1) {
        update_post_meta($post_id, 'sample', true);
        guardar_log("Metadato 'sample' agregado con valor 'true'");
    }
    if (!$is_sample && !$is_post && $audio_count === 1) {
        update_post_meta($post_id, 'rola', true);
        guardar_log("Metadato 'rola' agregado con valor 'true'");
    }

    // Función para procesar nombres de rolas
    function procesarNameRolas($post_id)
    {
        guardar_log("procesarNameRolas iniciado con post_id: {$post_id}");

        $max_rolas = 20;
        $rolas = [];
        $log_resumen = [];

        for ($i = 1; $i <= $max_rolas; $i++) {
            $field_name = "name_Rola{$i}";

            if (isset($_POST[$field_name])) {
                $valor = trim($_POST[$field_name]);
                if (!empty($valor)) {
                    $rolas[] = sanitize_textarea_field($valor);
                    $log_resumen[] = "Rola {$i}: '{$valor}' agregada";
                } else {
                    $log_resumen[] = "Rola {$i}: Campo recibido pero vacío";
                }
            } else {
                $log_resumen[] = "Rola {$i}: Campo no recibido";
            }
        }

        if (!empty($rolas)) {
            update_post_meta($post_id, 'rolas_meta_key', $rolas);
            guardar_log("Metadatos actualizados para post_id: {$post_id} con rolas: " . implode(", ", $rolas));
        } else {
            guardar_log("No se encontraron rolas válidas para post_id: {$post_id}");
        }

        // Guardar log resumen
        guardar_log("Resumen de procesamiento para post_id: {$post_id}:\n" . implode("\n", $log_resumen));

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
    guardar_log("Usuario obtenido: {$user_name} con ID: " . get_current_user_id());

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

    guardar_log("Datos adicionales compilados para post_id: {$post_id}");
    // Codificar los datos adicionales en JSON y actualizar metadatos
    if ($additional_data_json = json_encode($additional_data)) {
        update_post_meta($post_id, 'additional_search_data', $additional_data_json);
        guardar_log("Metadatos de búsqueda adicionales actualizados para post_id: {$post_id}");
    } else {
        guardar_log("Error al codificar datos adicionales a JSON para post_id: {$post_id}");
    }

    // Agregar Pinkys al usuario y enviar notificación
    $current_user_id = get_current_user_id();
    agregar_pinkys_al_usuario($current_user_id, 1);
    guardar_log("Se ha agregado 1 Pinky al usuario con ID: {$current_user_id}");

    insertar_notificacion(
        $current_user_id,
        '¡Tu nueva publicación ha sido creada con éxito! Has recibido un 1 Pinky',
        get_permalink($post_id),
        $current_user_id
    );

    guardar_log("Notificación enviada al usuario con ID: {$current_user_id} por la publicación con post_id: {$post_id}");
    // Enviar respuesta exitosa
    echo json_encode(['success' => true, 'message' => 'Publicación creada exitosamente.']);
    process_album_post($post_id);
    wp_die();
    guardar_log("Fin subidaDepost");
    guardar_log("---------------------------------------------");
}


add_action('wp_ajax_submit_social_post', 'subidaDePost');
add_action('wp_ajax_nopriv_submit_social_post', 'subidaDePost');

function procesarAudioLigero($post_id, $audio_id, $index)
{
    guardar_log("INICIO procesarAudioLigero");

    // Obtener el archivo de audio original
    $audio_path = get_attached_file($audio_id);
    guardar_log("Ruta del archivo de audio original: {$audio_path}");

    // Obtener las partes del camino del archivo
    $path_parts = pathinfo($audio_path);
    $unique_id = uniqid('2upra_');
    $base_path = $path_parts['dirname'] . '/' . $unique_id;

    // Procesar archivo de audio ligero (128 kbps)
    $nuevo_archivo_path_lite = $base_path . '_128k.mp3';
    $comando_lite = "/usr/bin/ffmpeg -i {$audio_path} -b:a 128k {$nuevo_archivo_path_lite}";
    guardar_log("Ejecutando comando: {$comando_lite}");
    exec($comando_lite, $output_lite, $return_var_lite);
    if ($return_var_lite !== 0) {
        guardar_log("Error al procesar audio ligero: " . implode("\n", $output_lite));
    }

    // Insertar archivos en la biblioteca de medios
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Archivo ligero
    $filetype_lite = wp_check_filetype(basename($nuevo_archivo_path_lite), null);
    $attachment_lite = array(
        'post_mime_type' => $filetype_lite['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', basename($nuevo_archivo_path_lite)),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id_lite = wp_insert_attachment($attachment_lite, $nuevo_archivo_path_lite, $post_id);
    guardar_log("ID de adjunto ligero: {$attach_id_lite}");
    $attach_data_lite = wp_generate_attachment_metadata($attach_id_lite, $nuevo_archivo_path_lite);
    wp_update_attachment_metadata($attach_id_lite, $attach_data_lite);
    update_post_meta($post_id, "post_audio_lite_{$index}", $attach_id_lite);

    // Extraer y guardar la duración del audio
    $duration_command = "/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$nuevo_archivo_path_lite}";
    guardar_log("Ejecutando comando para duración del audio: {$duration_command}");
    $duration_in_seconds = shell_exec($duration_command);
    guardar_log("Salida de ffprobe: '{$duration_in_seconds}'");

    // Limpiar y validar la duración del audio
    $duration_in_seconds = trim($duration_in_seconds);
    if (is_numeric($duration_in_seconds)) {
        $duration_in_seconds = (float)$duration_in_seconds;
        $duration_formatted = floor($duration_in_seconds / 60) . ':' . str_pad($duration_in_seconds % 60, 2, '0', STR_PAD_LEFT);
        update_post_meta($post_id, "audio_duration_{$index}", $duration_formatted);
        guardar_log("Duración del audio (formateada): {$duration_formatted}");
    } else {
        guardar_log("Duración del audio no válida para el archivo {$audio_path}");
    }
}

function agregar_mimes_permitidos($mimes)
{
    // Agregar las nuevas extensiones permitidas
    $mimes['flp'] = 'application/octet-stream';
    $mimes['zip'] = 'application/zip';
    $mimes['rar'] = 'application/x-rar-compressed';
    $mimes['cubase'] = 'application/octet-stream';
    $mimes['proj'] = 'application/octet-stream';
    $mimes['aiff'] = 'audio/aiff';
    $mimes['midi'] = 'audio/midi';
    $mimes['ptx'] = 'application/octet-stream';
    $mimes['sng'] = 'application/octet-stream';
    $mimes['aup'] = 'application/octet-stream';
    $mimes['omg'] = 'application/octet-stream';
    $mimes['rpp'] = 'application/octet-stream';
    $mimes['xpm'] = 'image/x-xpixmap';
    $mimes['tst'] = 'application/octet-stream';

    return $mimes;
}
add_filter('upload_mimes', 'agregar_mimes_permitidos');




/*

mira, no parece que este borrando el anterior o cumpliendo su funcion como debería, puedes ajustarlo para que funcione correctamente

2024-08-24 22:48:23 - INICIO handle_file_upload
2024-08-24 22:48:23 - Hash recibido: 2155baa2e8190c76a26e65e0e29ffb3cf7df25a40e57f0c34cfe7280e126216b
2024-08-24 22:48:23 - Resultado de wp_handle_upload: Array
(
    [file] => /var/www/html/wp-content/uploads/2024/08/Their-Virtue-Is-A-Joke.flp
    [url] => https://2upra.com/wp-content/uploads/2024/08/Their-Virtue-Is-A-Joke.flp
    [type] => application/octet-stream
)

2024-08-24 22:48:23 - Carga exitosa. Hash guardado: 2155baa2e8190c76a26e65e0e29ffb3cf7df25a40e57f0c34cfe7280e126216b. URL del archivo: https://2upra.com/wp-content/uploads/2024/08/Their-Virtue-Is-A-Joke.flp
2024-08-24 22:49:19 - ---------------------------------------------
2024-08-24 22:49:19 - INICIO handle_file_upload
2024-08-24 22:49:19 - Hash recibido: 2155baa2e8190c76a26e65e0e29ffb3cf7df25a40e57f0c34cfe7280e126216b
2024-08-24 22:49:19 - Resultado de wp_handle_upload: Array
(
    [file] => /var/www/html/wp-content/uploads/2024/08/Their-Virtue-Is-A-Joke-1.flp
    [url] => https://2upra.com/wp-content/uploads/2024/08/Their-Virtue-Is-A-Joke-1.flp
    [type] => application/octet-stream
)

2024-08-24 22:49:19 - Carga exitosa. Hash guardado: 2155baa2e8190c76a26e65e0e29ffb3cf7df25a40e57f0c34cfe7280e126216b. URL del archivo: https://2upra.com/wp-content/uploads/2024/08/Their-Virtue-Is-A-Joke-1.flp */


function handle_file_upload()
{
    guardar_log("---------------------------------------------");
    guardar_log("INICIO handle_file_upload");

    if (!isset($_FILES['file']) || !isset($_POST['file_hash'])) {
        guardar_log("No se proporcionó archivo o hash");
        wp_send_json_error('No se proporcionó archivo o hash');
        return;
    }

    $file_hash = sanitize_text_field($_POST['file_hash']);
    guardar_log("Hash recibido: " . $file_hash);

    $existing_file_url = get_file_url_by_hash($file_hash);

    if ($existing_file_url) {
        guardar_log("Archivo existente encontrado con URL: " . $existing_file_url);

        $existing_file_path = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $existing_file_url);

        if (file_exists($existing_file_path)) {
            guardar_log("El archivo ya existe en el servidor. Se procederá a reemplazarlo.");
            unlink($existing_file_path);
            guardar_log("Archivo anterior eliminado: " . $existing_file_path);
        } else {
            guardar_log("El archivo no existe físicamente en el servidor, aunque estaba registrado.");
        }

        delete_file_hash($file_hash);
        guardar_log("Registro del hash anterior eliminado.");
    } else {
        guardar_log("No se encontró un archivo existente con este hash.");
    }
    // Procesar nuevo archivo
    $upload_overrides = array('test_form' => false, 'unique_filename_callback' => 'custom_unique_filename');
    $movefile = wp_handle_upload($_FILES['file'], $upload_overrides);

    guardar_log("Resultado de wp_handle_upload: " . print_r($movefile, true));

    if ($movefile && !isset($movefile['error'])) {
        // Guardar hash y URL
        save_file_hash($file_hash, $movefile['url']);

        guardar_log("Carga exitosa. Hash guardado: " . $file_hash . ". URL del nuevo archivo: " . $movefile['url']);
        wp_send_json_success(array('fileUrl' => $movefile['url']));
    } else {
        // Error en la carga
        guardar_log("Error en la carga: " . ($movefile['error'] ?? 'Error desconocido'));
        wp_send_json_error($movefile['error'] ?? 'Error desconocido');
    }

    guardar_log("FIN handle_file_upload");
    guardar_log("---------------------------------------------");
}

function custom_unique_filename($dir, $name, $ext)
{
    // Elimina la extensión original del nombre del archivo si existe
    $name = basename($name, $ext);

    // Devuelve el nombre del archivo sin la extensión
    return $name . $ext;
}

add_action('wp_ajax_file_upload', 'handle_file_upload');
add_action('wp_ajax_nopriv_file_upload', 'handle_file_upload');


function file_exists_by_hash($hash)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'file_hashes';
    $result = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE file_hash = %s", $hash));
    return $result > 0;
}

function save_file_hash($hash, $url)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'file_hashes';
    $wpdb->insert(
        $table_name,
        array(
            'file_hash' => $hash,
            'file_url' => $url,
            'upload_date' => current_time('mysql')
        ),
        array('%s', '%s', '%s')
    );
}

function find_post_by_file_hash($file_hash)
{
    global $wpdb;

    // Consulta para encontrar el ID del post que tiene el hash especificado como un meta dato
    $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id 
         FROM {$wpdb->postmeta} 
         WHERE meta_key = 'file_hash' 
         AND meta_value = %s 
         LIMIT 1",
        $file_hash
    ));

    // Retornar el ID del post si se encontró, de lo contrario, retornar false
    return $post_id ? (int) $post_id : false;
}

function get_file_url_by_hash($file_hash)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'file_hashes';

    // Consulta para obtener la URL del archivo asociada con el hash
    $file_url = $wpdb->get_var($wpdb->prepare(
        "SELECT file_url 
         FROM $table_name 
         WHERE file_hash = %s 
         LIMIT 1",
        $file_hash
    ));

    // Retornar la URL del archivo si se encontró, de lo contrario, retornar false
    return $file_url ? $file_url : false;
}

function delete_file_hash($file_hash)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'file_hashes';

    // Eliminar el registro que coincida con el hash proporcionado
    $deleted = $wpdb->delete($table_name, array('file_hash' => $file_hash), array('%s'));

    // Retornar true si se eliminó un registro, de lo contrario, false
    return $deleted ? true : false;
}


function crear_tabla_file_hashes()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'file_hashes';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            file_hash varchar(64) NOT NULL,
            file_url text NOT NULL,
            upload_date datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY file_hash (file_hash)
        ) ENGINE=InnoDB $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}



















function analyze_existing_files()
{
    // Directorio de carga de WordPress
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];

    // Directorio para archivos duplicados
    $duplicate_dir = $base_dir . '/duplicates';
    if (!file_exists($duplicate_dir)) {
        mkdir($duplicate_dir, 0755, true);
    }

    // Obtener todos los archivos
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    global $wpdb;
    $table_name = $wpdb->prefix . 'file_hashes';
    $pending_table = $wpdb->prefix . 'pending_moves';

    // Crear tabla de movimientos pendientes si no existe
    $wpdb->query("CREATE TABLE IF NOT EXISTS $pending_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_path VARCHAR(255) NOT NULL,
        new_path VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending'
    )");

    $batch_size = 50; // Número de archivos a procesar por lote
    $processed = 0;

    foreach ($files as $file) {
        // Saltar directorios
        if ($file->isDir()) {
            continue;
        }

        $file_path = $file->getPathname();
        $relative_path = str_replace($base_dir . '/', '', $file_path);

        // Verificar si ya tiene hash
        $existing_hash = $wpdb->get_var($wpdb->prepare(
            "SELECT file_hash FROM $table_name WHERE file_url LIKE %s",
            '%' . $wpdb->esc_like($relative_path)
        ));

        if (!$existing_hash) {
            // Generar hash
            $file_hash = md5_file($file_path);

            // Verificar si es un duplicado
            $duplicate = $wpdb->get_var($wpdb->prepare(
                "SELECT file_url FROM $table_name WHERE file_hash = %s",
                $file_hash
            ));

            if ($duplicate) {
                // Agregar a la lista de pendientes
                $new_path = $duplicate_dir . '/' . basename($file_path);
                $wpdb->insert(
                    $pending_table,
                    array(
                        'file_path' => $file_path,
                        'new_path' => $new_path
                    ),
                    array('%s', '%s')
                );
                log_duplicados("Archivo duplicado pendiente de mover: $file_path");
            } else {
                // Guardar el nuevo hash
                $wpdb->insert(
                    $table_name,
                    array(
                        'file_hash' => $file_hash,
                        'file_url' => $relative_path,
                        'upload_date' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s')
                );
                log_duplicados("Nuevo hash generado para: $file_path");
            }
        }

        $processed++;
        if ($processed >= $batch_size) {
            log_duplicados("Procesados $batch_size archivos. Pausa para evitar sobrecarga.");
            sleep(1); // Pausa de 1 segundo
            $processed = 0;
        }
    }
    log_duplicados("Análisis de archivos existentes completado");
}

function log_duplicados($mensaje)
{
    $log_file = WP_CONTENT_DIR . '/file_analysis_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $mensaje\n", FILE_APPEND);
}

// Función para mostrar y procesar archivos pendientes en el front-end
function mostrar_archivos_pendientes()
{
    global $wpdb;
    $pending_table = $wpdb->prefix . 'pending_moves';

    if (isset($_POST['process_pending'])) {
        $file_id = intval($_POST['file_id']);
        $file = $wpdb->get_row($wpdb->prepare("SELECT * FROM $pending_table WHERE id = %d", $file_id));

        if ($file) {
            rename($file->file_path, $file->new_path);
            $wpdb->update($pending_table, array('status' => 'processed'), array('id' => $file_id));
            echo "Archivo movido con éxito.";
        }
    }

    $pending_files = $wpdb->get_results("SELECT * FROM $pending_table WHERE status = 'pending'");

    echo "<h2>Archivos pendientes de mover</h2>";
    foreach ($pending_files as $file) {
        echo "<form method='post'>";
        echo "<p>{$file->file_path} -> {$file->new_path}</p>";
        echo "<input type='hidden' name='file_id' value='{$file->id}'>";
        echo "<input type='submit' name='process_pending' value='Procesar'>";
        echo "</form>";
    }
}

/* Programar la tarea para que se ejecute diariamente
if (!wp_next_scheduled('analyze_existing_files_event')) {
    wp_schedule_event(time(), 'daily', 'analyze_existing_files_event');
}
add_action('analyze_existing_files_event', 'analyze_existing_files'); 
analyze_existing_files(); */


function check_missing_images()
{
    $posts = get_posts(array('post_type' => 'any', 'posts_per_page' => -1));
    $missing_images = [];

    foreach ($posts as $post) {
        $post_content = $post->post_content;
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post_content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $image_url) {
                $image_path = str_replace(site_url('/'), ABSPATH, $image_url);
                if (!file_exists($image_path)) {
                    $missing_images[] = array(
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'image_url' => $image_url
                    );
                }
            }
        }
    }

    return $missing_images;
}

function analizar_y_recuperar_archivos_perdidos()
{
    global $wpdb;

    // Activar la depuración de WordPress
    if (!defined('WP_DEBUG')) {
        define('WP_DEBUG', true);
    }
    if (!defined('WP_DEBUG_LOG')) {
        define('WP_DEBUG_LOG', true);
    }
    if (!defined('WP_DEBUG_DISPLAY')) {
        define('WP_DEBUG_DISPLAY', true);
    }

    error_log("Iniciando análisis de archivos perdidos...");

    // Obtener todos los archivos adjuntos de la base de datos
    $attachments = $wpdb->get_results("SELECT ID, guid FROM {$wpdb->posts} WHERE post_type = 'attachment'");

    $archivos_faltantes = 0;
    $archivos_recuperables = 0;
    $ejemplos_recuperacion = [];

    foreach ($attachments as $attachment) {
        $file_path = get_attached_file($attachment->ID);

        if (!file_exists($file_path)) {
            $archivos_faltantes++;

            // Verificar si el archivo existe en wp-content/uploads
            $upload_dir = wp_upload_dir();
            $relative_path = str_replace($upload_dir['basedir'] . '/', '', $file_path);
            $alternative_path = $upload_dir['basedir'] . '/' . $relative_path;

            if (file_exists($alternative_path)) {
                $archivos_recuperables++;
                if (count($ejemplos_recuperacion) < 10) {
                    $ejemplos_recuperacion[] = [
                        'id' => $attachment->ID,
                        'ruta_original' => $file_path,
                        'ruta_recuperacion' => $alternative_path
                    ];
                }
            }
        }
    }

    error_log("Análisis completado.");
    error_log("Archivos faltantes: " . $archivos_faltantes);
    error_log("Archivos recuperables: " . $archivos_recuperables);

    if (!empty($ejemplos_recuperacion)) {
        error_log("Ejemplos de recuperación (máximo 10):");
        foreach ($ejemplos_recuperacion as $ejemplo) {
            error_log("ID: {$ejemplo['id']} - Original: {$ejemplo['ruta_original']} - Recuperación: {$ejemplo['ruta_recuperacion']}");
        }
    }

    return [
        'faltantes' => $archivos_faltantes,
        'recuperables' => $archivos_recuperables,
        'ejemplos' => $ejemplos_recuperacion
    ];
}
