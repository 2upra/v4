<?php

// Function moved from app/Functions/streamPro.php
function audioStreamEndPro($request) {
    $audio_id = $request['id'];
    
    $original_file = get_attached_file($audio_id);
    if (!file_exists($original_file)) {
        return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
    }

    // Configurar headers para caché agresivo
    header('Content-Type: ' . get_post_mime_type($audio_id));
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=31536000'); // 1 año
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    header('Pragma: public');

    // Streaming directo del archivo
    $fp = @fopen($original_file, 'rb');
    $size = filesize($original_file);
    
    // Manejar ranges si es necesario
    if (isset($_SERVER['HTTP_RANGE'])) {
        // [Código existente para manejar ranges...]
        // Refactor(Org): Placeholder - Implementar manejo de rangos si es necesario para PRO
        $c_start = 0;
        $c_end = $size - 1;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $c_start-$c_end/$size");
            exit;
        }
        if ($range == '-') {
            $c_start = $size - substr($range, 1);
        } else {
            $range = explode('-', $range);
            $c_start = $range[0];
            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size - 1;
        }
        $c_end = ($c_end > $size - 1) ? $size - 1 : $c_end;
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $c_start-$c_end/$size");
            exit;
        }
        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1;
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);
        $buffer = 1024 * 8;
        while(!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            echo fread($fp, $buffer);
            flush();
        }
    } else {
        header('Content-Length: ' . $size);
        fpassthru($fp);
    }
    
    fclose($fp);
    exit;
}

// Refactor(Org): Función audioStreamEnd() movida desde app/Functions/stream.php
function audioStreamEnd($data)
{
    if (ob_get_level()) ob_end_clean();
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

    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 24 * 60 * 60)) {
        //guardarLog("audioStreamEnd: Cargando audio desde el archivo en caché: $cache_file");
        $file = $cache_file;
    } else {
        $original_file = get_attached_file($audio_id);
        if (!file_exists($original_file)) {
            //guardarLog("audioStreamEnd: Error - Archivo de audio original no encontrado para el audio ID: $audio_id");
            return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
        }
        if (!@copy($original_file, $cache_file)) {
            //guardarLog("audioStreamEnd: Error - Fallo al copiar el archivo de audio al caché.");
            return new WP_Error('copy_failed', 'Error al copiar el archivo de audio al caché.', array('status' => 500));
        }

        //guardarLog("audioStreamEnd: Archivo de audio copiado exitosamente al caché: $cache_file");
        $file = $cache_file;
    }

    $fp = @fopen($file, 'rb');
    if (!$fp) {
        //guardarLog("audioStreamEnd: Error - No se pudo abrir el archivo de audio: $file");
        return new WP_Error('file_open_error', 'No se pudo abrir el archivo de audio.', array('status' => 500));
    }

    $size = filesize($file);
    $length = $size;
    $start = 0;
    $end = $size - 1;

    // Generar ETag único para el archivo
    $etag = '"' . md5($file . filemtime($file)) . '"';

    // Headers básicos
    header('Content-Type: ' . get_post_mime_type($audio_id));
    header('Accept-Ranges: bytes');

    // Configurar headers de caché según la constante
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        $cache_time = 60 * 60 * 2190; // 24 horas
        header('Cache-Control: public, max-age=' . $cache_time);
        header('Pragma: public');
        header('ETag: ' . $etag);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');

        // Verificar si el contenido no ha cambiado
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
    } else {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }


    // Manejar Ranges HTTP para streaming parcial
    if (isset($_SERVER['HTTP_RANGE'])) {
        //guardarLog("audioStreamEnd: HTTP Range solicitado: " . $_SERVER['HTTP_RANGE']);
        $c_start = $start;
        $c_end = $end;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            //guardarLog("audioStreamEnd: Error - Rango solicitado no soportado.");
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
            //guardarLog("audioStreamEnd: Error - Rango solicitado fuera de los límites.");
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

    // Streaming con rate limiting
    $buffer = 1024 * 8; // 8 KB
    $sleep = 1000; // Microsegundos de espera entre cada envío de buffer
    $sent = 0;
    while (!feof($fp) && ($p = ftell($fp)) <= $end) {
        if ($p + $buffer > $end) {
            $buffer = $end - $p + 1;
        }
        echo fread($fp, $buffer);
        $sent += $buffer;
        flush();
        if ($sent >= 64 * 1024) { // Cada 64 KB
            usleep($sleep);
            $sent = 0;
        }
    }

    //guardarLog("audioStreamEnd: Transmisión del audio completada para el archivo: $file");
    fclose($fp);
    exit();
}

// Refactor(Org): Registro de ruta REST API movido desde app/Functions/stream.php
add_action('rest_api_init', function () {
    // Ruta para usuarios normales (movida aquí)
    register_rest_route('1/v1', '/2', array(
        'methods' => 'GET',
        'callback' => 'audioStreamEnd',
        'args' => array(
            'token' => array(
                'required' => true,
            ),
        ),
        'permission_callback' => function ($request) {
            // La función verificarAudio() sigue estando en app/Functions/stream.php por ahora
            // Si se mueve verificarAudio a este servicio, actualizar la llamada aquí.
            return verificarAudio($request->get_param('token'));
        }
    ));
});
