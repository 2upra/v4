<?

add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/syncpre/(?P<user_id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'obtenerAudiosUsuario',
        'permission_callback' => 'chequearElectron',
    ));
    register_rest_route('sync/v1', '/download/', array(
        'methods' => 'GET',
        'callback' => 'descargarAudiosSync',
        'args' => array(
            'token' => array('required' => true, 'type' => 'string'),
            'nonce' => array('required' => true, 'type' => 'string'),
        ),
    ));
    register_rest_route('1/v1', '/syncpre/(?P<user_id>\d+)/check', array(
        'methods'  => 'GET',
        'callback' => 'verificarCambiosAudios',
        'permission_callback' => 'chequearElectron',
    ));
    register_rest_route('1/v1',  '/infoUsuario', array(
        'methods' => 'POST',
        'callback' => 'handle_info_usuario',
        'permission_callback' => 'chequearElectron',
    ));
});


function handle_info_usuario(WP_REST_Request $request)
{
    $receptor = intval($request->get_param('receptor'));

    if ($receptor <= 0) {
        return new WP_Error('invalid_receptor', 'ID del receptor inválido.', array('status' => 400));
    }

    $imagenPerfil = imagenPerfil($receptor) ?: 'ruta_por_defecto.jpg';
    $nombreUsuario = obtenerNombreUsuario($receptor) ?: 'Usuario Desconocido';

    return array(
        'imagenPerfil' => $imagenPerfil,
        'nombreUsuario' => $nombreUsuario,
    );
}

// Función de permiso: valida la cabecera X-Electron-App
function chequearElectron()
{
    //error_log("Iniciando chequearElectron...");
    if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
        //error_log("Cabecera válida: " . $_SERVER['HTTP_X_ELECTRON_APP']);
        return true;
    }
    //error_log("Cabecera inválida o ausente.");
    return new WP_Error('forbidden', 'Acceso no autorizado', array('status' => 403));
}


function verificarCambiosAudios(WP_REST_Request $request)
{
    $user_id = $request->get_param('user_id'); // Obtiene el parámetro user_id
    $last_sync_timestamp = isset($_GET['last_sync']) ? intval($_GET['last_sync']) : 0;

    // Forzar eliminación de caché
    //wp_cache_delete($user_id, 'users');
    //error_log("Caché eliminada para user_id: $user_id");

    // Obtener los valores directamente desde la base de datos
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

    // Más logs para depuración
    //error_log("verificarCambiosAudios: Descargas Timestamp: $descargas_timestamp, Samples Timestamp: $samples_timestamp");

    $response_data = [
        'descargas_modificado' => $descargas_timestamp,
        'samplesGuardados_modificado' => $samples_timestamp,
    ];

    return rest_ensure_response($response_data);
}



function actualizarTimestampDescargas($user_id)
{
    //SI FUNCIONA PORQUE EN LA BASE DE DATOS SE VE EL NUEVO VALOR CUANDO CAMBIA
    $time = time();
    update_user_meta($user_id, 'descargas_modificado', $time);
    error_log("actualizarTimestampDescargas: User ID: $user_id, Timestamp actualizado a: $time"); // Nuevo log
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
    error_log("obtenerAudiosUsuario: User ID: $user_id"); // Log al inicio

    $post_id = $request->get_param('post_id'); // Nuevo parámetro opcional
    $descargas = get_user_meta($user_id, 'descargas', true);
    $samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true);
    $downloads = [];

    if (is_array($descargas)) {
        foreach ($descargas as $current_post_id => $count) {
            // Si se proporciona post_id, solo procesar ese post
            if ($post_id !== null && $current_post_id != $post_id) continue;

            $attachment_id = get_post_meta($current_post_id, 'post_audio', true);
            if ($attachment_id && get_post($attachment_id)) {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path) && strpos(mime_content_type($file_path), 'audio/') === 0) {
                    // Obtener imagen optimizada
                    $optimized_image_url = obtenerImagenOptimizada($current_post_id);

                    $colecciones = isset($samplesGuardados[$current_post_id]) ? $samplesGuardados[$current_post_id] : ['No coleccionados'];
                    foreach ($colecciones as $collection_id) {
                        $collection_name = ($collection_id !== 'No coleccionados') ? get_the_title($collection_id) : 'No coleccionados';
                        $collection_name = sanitize_title($collection_name);

                        $downloads[] = [
                            'post_id' => $current_post_id,
                            'collection' => $collection_name,
                            'download_url' => home_url("/wp-json/sync/v1/download/?attachment_id=$attachment_id"),
                            'audio_filename' => get_the_title($attachment_id) . '.' . pathinfo($file_path, PATHINFO_EXTENSION),
                            'image' => $optimized_image_url, // Añadimos la URL de la imagen
                        ];
                    }
                } else {
                    error_log("Error con el archivo de audio para el post ID: $current_post_id. Archivo: $file_path");
                }
            }
        }
    } else {
        error_log("obtenerAudiosUsuario: El metadato 'descargas' no es un array o no está definido para el usuario $user_id");
    }
    error_log("obtenerAudiosUsuario: Se encontraron " . count($downloads) . " audios para el usuario $user_id");

    return rest_ensure_response($downloads);
}

function descargarAudiosSync(WP_REST_Request $request)
{
    $attachment_id = $request->get_param('attachment_id');

    if ($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $mime_type = mime_content_type($file_path);
            if (strpos($mime_type, 'audio/') !== 0) {
                error_log("Intento de acceso a archivo no de audio. Ruta: $file_path");
                return new WP_Error('invalid_file_type', 'Tipo de archivo inválido.', array('status' => 400));
            }
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: no-cache');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            error_log("Archivo no encontrado en la ruta: $file_path. Attachment ID: $attachment_id");
            return new WP_Error('file_not_found', 'Archivo no encontrado.', array('status' => 404));
        }
    } else {
        error_log("Intento de descarga sin un attachment_id válido.");
        return new WP_Error('invalid_request', 'Parámetro attachment_id faltante o inválido.', array('status' => 400));
    }
}

function obtenerImagenOptimizada($post_id)
{
    // Intentar obtener la imagen de portada
    $portada_id = get_post_thumbnail_id($post_id);
    if ($portada_id) {
        $portada_url = wp_get_attachment_url($portada_id);
        if ($portada_url) {
            return img($portada_url); // Optimizar la imagen
        }
    }

    // Si no hay portada, intentar obtener la imagen temporal
    $imagen_temporal_id = get_post_meta($post_id, 'imagenTemporal', true);
    if ($imagen_temporal_id) {
        $imagen_temporal_url = wp_get_attachment_url($imagen_temporal_id);
        if ($imagen_temporal_url) {
            return img($imagen_temporal_url); // Optimizar la imagen
        }
    }

    // Si no hay imagen, devolver null
    return null;
}
