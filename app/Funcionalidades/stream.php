<?php

// Función para obtener la URL segura del audio
function tokenAudio($audio_id) {
    // Validar el formato del audio_id (opcional, dependiendo del tipo de datos)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        return false;
    }

    $expiration = time() + 450; // 7 m
    $data = $audio_id . '|' . $expiration;
    $signature = hash_hmac('sha256', $data, ($_ENV['AUDIOCLAVE']));
    return base64_encode($data . '|' . $signature);
}

// Función para verificar el token
function verificarAudio($token) {
    $parts = explode('|', base64_decode($token));
    if (count($parts) !== 3) return false;
    list($audio_id, $expiration, $signature) = $parts;

    // Verificar expiración del token
    if (time() > $expiration) return false;

    // Validar el formato del audio_id
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        return false;
    }

    // Verificar la firma
    $data = $audio_id . '|' . $expiration;
    $expected_signature = hash_hmac('sha256', $data, ($_ENV['AUDIOCLAVE']));
    return hash_equals($expected_signature, $signature);
}

// Modificar la función audioUrlSegura
function audioUrlSegura($audio_id) {
    $token = tokenAudio($audio_id);
    if (!$token) {
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }
    return site_url("/wp-json/1/v1/2?token=" . urlencode($token));
}

// Modificar el endpoint REST
add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/2', array(
        'methods' => 'GET',
        'callback' => 'audioStreamEnd',
        'args' => array(
            'token' => array(
                'required' => true,
            ),
        ),
        'permission_callback' => function($request) {
            return verificarAudio($request->get_param('token'));
        }
    ));
});

// Modificar la función audioStreamEnd para implementar streaming
function audioStreamEnd($data) {
    $token = $data['token'];
    $parts = explode('|', base64_decode($token));
    $audio_id = $parts[0];

    // Directorio de caché
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/audio_cache';
    if (!file_exists($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }

    $cache_file = $cache_dir . '/audio_' . $audio_id . '.cache';

    // Verifica si el archivo de caché existe y no ha expirado (1 semana)
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 7 * 24 * 60 * 60)) {
        $file = $cache_file;
    } else {
        // El audio no está en caché o ha expirado, copia el archivo original
        $original_file = get_attached_file($audio_id);
        if (!file_exists($original_file)) {
            return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
        }

        // Intentar copiar el archivo al caché
        if (!@copy($original_file, $cache_file)) {
            return new WP_Error('copy_failed', 'Error al copiar el archivo de audio al caché.', array('status' => 500));
        }

        $file = $cache_file;
    }

    $fp = @fopen($file, 'rb');
    if (!$fp) {
        return new WP_Error('file_open_error', 'No se pudo abrir el archivo de audio.', array('status' => 500));
    }

    $size = filesize($file);
    $length = $size;
    $start = 0;
    $end = $size - 1;

    header('Content-Type: ' . get_post_mime_type($audio_id));
    header("Accept-Ranges: bytes");

    // Manejar Ranges HTTP para streaming parcial
    if (isset($_SERVER['HTTP_RANGE'])) {
        $c_start = $start;
        $c_end = $end;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            exit;
        }
        if ($range == '-') {
            $c_start = $size - substr($range, 1);
        } else {
            $range = explode('-', $range);
            $c_start = $range[0];
            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
        }
        $c_end = ($c_end > $end) ? $end : $c_end;
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            exit;
        }
        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1;
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
    }

    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: " . $length);

    // Aumentar el tamaño del buffer para mejorar el rendimiento del streaming
    $buffer = 1024 * 64; // 64 KB
    while (!feof($fp) && ($p = ftell($fp)) <= $end) {
        if ($p + $buffer > $end) {
            $buffer = $end - $p + 1;
        }
        echo fread($fp, $buffer);
        flush();
    }

    fclose($fp);
    exit();
}

// Registra el cron job
add_action('wp', 'schedule_audio_cache_cleanup');

function schedule_audio_cache_cleanup() {
    if (!wp_next_scheduled('audio_cache_cleanup')) {
        wp_schedule_event(time(), 'daily', 'audio_cache_cleanup');
    }
}

// Función para limpiar el caché
add_action('audio_cache_cleanup', 'clean_audio_cache');

function clean_audio_cache() {
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/audio_cache';

    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) > 7 * 24 * 60 * 60)) {
                unlink($file);
            }
        }
    }
}

// Desprogramar el cron job cuando el plugin se desactiva
function unschedule_audio_cache_cleanup() {
    $timestamp = wp_next_scheduled('audio_cache_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'audio_cache_cleanup');
    }
}
register_deactivation_hook(__FILE__, 'unschedule_audio_cache_cleanup');














/* funcion vieja
add_action('template_redirect', 'custom_audio_streaming_handler');



function custom_audio_streaming_handler() {
    if (isset($_GET['custom-audio-stream'], $_GET['audio_id']) && $_GET['custom-audio-stream'] == '1') {
        $audio_id = intval($_GET['audio_id']);

        // Verificar que el ID de audio corresponde a un adjunto válido
        $attachment = get_post($audio_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            status_header(404);
            die('ID de audio inválido.');
        }

        $audio_path = get_attached_file($audio_id);

        if (!$audio_path || !file_exists($audio_path)) {
            status_header(404);
            die('Archivo no encontrado.');
        }

        // Verificar que el usuario tenga permiso para acceder al archivo
        // Puedes personalizar esta parte según tus necesidades de seguridad
        /*
        if (!current_user_can('read_private_posts')) {
            status_header(403);
            die('No tienes permiso para acceder a este archivo.');
        }


        // Obtener el tipo MIME correcto
        $file_info = wp_check_filetype($audio_path);
        $content_type = $file_info['type'];

        if (!$content_type) {
            status_header(403);
            die('Tipo de archivo no permitido.');
        }

        $last_modified_time = filemtime($audio_path);
        $etag = '"' . md5_file($audio_path) . '"';

        // 30 días en segundos
        $max_age = 30 * 24 * 60 * 60;

        // Establecer encabezados de cacheo
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: inline; filename="' . basename($audio_path) . '"');
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=' . $max_age);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT');
        header('Etag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified_time) . ' GMT');

        // Manejar solicitudes condicionales
        if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
            (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time)) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }

        // Manejar solicitudes de rango
        $filesize = filesize($audio_path);
        $offset = 0;
        $length = $filesize;

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $offset = (int)$matches[1];
            $end = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : $filesize - 1;
            $length = $end - $offset + 1;

            if ($offset > $end || $end >= $filesize) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header('Content-Range: bytes /' . $filesize);
                exit;
            }

            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $offset . '-' . $end . '/' . $filesize);
        }

        header('Content-Length: ' . $length);

        // Limitar el script para que solo sirva el archivo y no cargue todo WordPress en la memoria
        if (!ini_get('safe_mode')) {
            set_time_limit(0);
        }

        // Abrir el archivo
        $file = @fopen($audio_path, 'rb');
        if ($file) {
            // Mover el puntero al punto de inicio
            fseek($file, $offset);

            // Enviar el contenido en partes para manejar archivos grandes
            $buffer_size = 8192;
            $bytes_sent = 0;

            while (!feof($file) && ($bytes_sent < $length)) {
                $remaining_bytes = $length - $bytes_sent;
                $read_size = ($remaining_bytes > $buffer_size) ? $buffer_size : $remaining_bytes;
                $data = fread($file, $read_size);
                echo $data;
                flush();
                $bytes_sent += strlen($data);
            }

            fclose($file);
            exit;
        } else {
            status_header(500);
            die('No se pudo abrir el archivo.');
        }
    }
}
*/
