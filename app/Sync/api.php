<?

add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/syncpre/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'obtenerAudiosUsuario',
        'permission_callback' => 'chequearElectron'
    ));
    register_rest_route('sync/v1', '/download/', array(
        'methods' => 'GET',
        'callback' => 'descargarAudiosSync',
        'args' => array(
            'token' => array('required' => true, 'type' => 'string'),
            'nonce' => array('required' => true, 'type' => 'string')
        )
    ));
    register_rest_route('1/v1', '/syncpre/(?P<user_id>\d+)/check', array(
        'methods'  => 'GET',
        'callback' => 'verificarCambiosAudios',
        'permission_callback' => 'chequearElectron'
    ));
});




function verificarCambiosAudios(WP_REST_Request $request)
{
    guardarLog('[verificarCambiosAudios] Inicio de la función');
    $user_id = $request->get_param('user_id');
    $force_sync = $request->get_param('force');

    if ($user_id == 355) {
        $user_id = 1;
    }

    $last_sync_timestamp = isset($_GET['last_sync']) ? intval($_GET['last_sync']) : 0;

    guardarLog("[verificarCambiosAudios] Parámetro recibido - user_id: {$user_id}");
    guardarLog("[verificarCambiosAudios] Parámetro recibido - last_sync_timestamp: {$last_sync_timestamp}");
    guardarLog("[verificarCambiosAudios] Parámetro recibido - force: {$force_sync}");

    global $wpdb;

    $descargas_timestamp = $wpdb->get_var($wpdb->prepare("
        SELECT meta_value
        FROM {$wpdb->usermeta}
        WHERE user_id = %d AND meta_key = 'descargas_modificado'
    ", $user_id));
    $samples_timestamp = $wpdb->get_var($wpdb->prepare("
        SELECT meta_value
        FROM {$wpdb->usermeta}
        WHERE user_id = %d AND meta_key = 'samplesGuardados_modificado'
    ", $user_id));

    $descargas_timestamp = ($descargas_timestamp !== null) ? intval($descargas_timestamp) : 0;
    $samples_timestamp = ($samples_timestamp !== null) ? intval($samples_timestamp) : 0;

    guardarLog("[verificarCambiosAudios] Valor convertido - descargas_modificado: {$descargas_timestamp}");
    guardarLog("[verificarCambiosAudios] Valor convertido - samplesGuardados_modificado: {$samples_timestamp}");

    $response_data = [
        'descargas_modificado' => $descargas_timestamp,
        'samplesGuardados_modificado' => $samples_timestamp,
        'force_sync' => false
    ];

    if ($force_sync === 'true') {
        guardarLog("[verificarCambiosAudios] Se recibió el parámetro 'force=true'. Forzando la sincronización.");
        $response_data['descargas_modificado'] = time();
        $response_data['samplesGuardados_modificado'] = time();
        $response_data['force_sync'] = true;
    } else {
        if ($descargas_timestamp > $last_sync_timestamp || $samples_timestamp > $last_sync_timestamp) {
            guardarLog("[verificarCambiosAudios] Se detectaron cambios desde el último sync.");
        } else {
            guardarLog("[verificarCambiosAudios] No se detectaron cambios desde el último sync.");
        }
    }

    guardarLog("[verificarCambiosAudios] Datos de respuesta: " . json_encode($response_data));

    guardarLog('[verificarCambiosAudios] Fin de la función');
    return rest_ensure_response($response_data);
}



function actualizarTimestampDescargas($user_id)
{
    $time = time();
    update_user_meta($user_id, 'descargas_modificado', $time);
    guardarLog("actualizarTimestampDescargas: User ID: $user_id, Timestamp actualizado a: $time");
}

add_action('nueva_descarga_realizada', 'actualizarTimestampDescargas', 10, 2);

function actualizarTimestampSamplesGuardados($user_id)
{
    update_user_meta($user_id, 'samplesGuardados_modificado', time());
}
add_action('samples_guardados_actualizados', 'actualizarTimestampSamplesGuardados', 10, 2);
function obtenerAudiosUsuario(WP_REST_Request $request)
{
    $user_id = $request->get_param('user_id');

    if ($user_id == 355) {
        $user_id = 1;
    }

    guardarLog("obtenerAudiosUsuario: User ID: $user_id");

    $post_id = $request->get_param('post_id');
    $descargas = get_user_meta($user_id, 'descargas', true);
    $samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true);
    $downloads = [];

    if (is_array($descargas)) {
        $post_ids = array_keys($descargas);

        if ($post_id !== null) {
            $post_ids = array_intersect($post_ids, [$post_id]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'post_likes';
        $post_ids_str = implode(',', $post_ids);

        $favoritos = [];
        if (!empty($post_ids_str)) {
            $favoritos_results = $wpdb->get_results($wpdb->prepare(
                "SELECT post_id FROM $table_name WHERE user_id = %d AND like_type = 'favorito' AND post_id IN ($post_ids_str)",
                $user_id
            ));

            foreach ($favoritos_results as $result) {
                $favoritos[$result->post_id] = true;
            }
        }

        foreach ($post_ids as $current_post_id) {
            $attachment_id = get_post_meta($current_post_id, 'post_audio', true);
            if ($attachment_id && get_post($attachment_id)) {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path) && strpos(mime_content_type($file_path), 'audio/') === 0) {
                    $token = wp_generate_password(20, false);
                    $nonce = wp_create_nonce('download_' . $token);
                    set_transient('sync_token_' . $token, $attachment_id, 300);

                    $optimized_image_url = obtenerImagenOptimizada($current_post_id);

                    $colecciones = isset($samplesGuardados[$current_post_id]) ? $samplesGuardados[$current_post_id] : ['No coleccionados'];
                    foreach ($colecciones as $collection_id) {
                        $collection_name = ($collection_id !== 'No coleccionados') ? get_the_title($collection_id) : 'No coleccionados';
                        $collection_name = sanitize_title($collection_name);

                        $downloads[] = [
                            'post_id' => $current_post_id,
                            'collection' => $collection_name,
                            'download_url' => home_url("/wp-json/sync/v1/download/?token=$token&nonce=$nonce"),
                            'audio_filename' => get_the_title($attachment_id) . '.' . pathinfo($file_path, PATHINFO_EXTENSION),
                            'image' => $optimized_image_url,
                            'es_favorito' => isset($favoritos[$current_post_id])
                        ];
                    }
                } else {
                    guardarLog("Error con el archivo de audio para el post ID: $current_post_id. Archivo: $file_path");
                }
            }
        }
    } else {
        guardarLog("obtenerAudiosUsuario: El metadato 'descargas' no es un array o no está definido para el usuario $user_id");
    }
    guardarLog("obtenerAudiosUsuario: Se encontraron " . count($downloads) . " audios para el usuario $user_id");

    return rest_ensure_response($downloads);
}


function descargarAudiosSync(WP_REST_Request $request)
{
    $token = $request->get_param('token');
    $nonce = $request->get_param('nonce');
    if (!wp_verify_nonce($nonce, 'download_' . $token)) {
        guardarLog("Intento de descarga con nonce inválido. Token: $token, Nonce: $nonce");
        return new WP_Error('invalid_nonce', 'Nonce inválido.', array('status' => 403));
    }
    $attachment_id = get_transient('sync_token_' . $token);
    if ($attachment_id) {
        delete_transient('sync_token_' . $token);
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {

            while (ob_get_level()) {
                ob_end_clean();
            }

            ini_set('zlib.output_compression', 'Off');
            ini_set('output_buffering', 'Off');
            set_time_limit(0);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);

            if (strpos($mime_type, 'audio/') !== 0) {
                guardarLog("Intento de acceso a archivo no de audio. Ruta: $file_path");
                return new WP_Error('invalid_file_type', 'Tipo de archivo inválido.', array('status' => 400));
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-Length: ' . filesize($file_path));
            header('Accept-Ranges: bytes');

            if (isset($_SERVER['HTTP_RANGE'])) {
                list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
                list($range) = explode(",", $range, 2);
                list($range, $range_end) = explode("-", $range);
                $range = intval($range);
                $size = filesize($file_path);
                $range_end = ($range_end) ? intval($range_end) : $size - 1;

                header('HTTP/1.1 206 Partial Content');
                header("Content-Range: bytes $range-$range_end/$size");
                header('Content-Length: ' . ($range_end - $range + 1));
            } else {
                $range = 0;
            }

            $handle = fopen($file_path, 'rb');
            if ($handle !== false) {
                fseek($handle, $range);
                fpassthru($handle);
                fclose($handle);
            } else {
                guardarLog("Error al abrir el archivo: $file_path");
                return new WP_Error('file_open_error', 'Error al abrir el archivo.', array('status' => 500));
            }
            flush();
            exit;
        } else {
            guardarLog("Archivo no encontrado en la ruta: $file_path. Token: $token");
            return new WP_Error('file_not_found', 'Archivo no encontrado.', array('status' => 404));
        }
    } else {
        guardarLog("Intento de descarga con token inválido o expirado: $token");
        return new WP_Error('invalid_token', 'Token inválido o expirado.', array('status' => 403));
    }
}
