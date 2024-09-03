<?php

add_action('template_redirect', 'custom_audio_streaming_handler');
function custom_audio_streaming_handler() {
    if (isset($_GET['custom-audio-stream']) && $_GET['custom-audio-stream'] == '1' && isset($_GET['audio_id'])) {
        $audio_id = intval($_GET['audio_id']);
        $audio_path = get_attached_file($audio_id);

        if (file_exists($audio_path)) {
            $file_extension = strtolower(pathinfo($audio_path, PATHINFO_EXTENSION));
            $content_type = 'audio/mpeg'; // Default to mp3
            
            if ($file_extension == 'wav') {
                $content_type = 'audio/wav';
            }

        $last_modified_time = filemtime($audio_path);
        $etag = md5_file($audio_path);

        // 30 días expresados en segundos
        $max_age = 30 * 24 * 60 * 60;

        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $max_age) . " GMT"); 
        header("Pragma: cache"); 
        header("Cache-Control: max-age=$max_age"); 
        header("ETag: \"$etag\"");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time ||
            isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
            trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }

            header('Content-Type: ' . $content_type);
            header('Accept-Ranges: bytes');

            $size = filesize($audio_path);
            $length = $size;
            $start = 0;
            $end = $size - 1;

            if (isset($_SERVER['HTTP_RANGE'])) {
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                $range = explode('-', $range);
                $start = $range[0];
                $end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $end;
                $length = $end - $start + 1;

                header('HTTP/1.1 206 Partial Content');
                header("Content-Range: bytes $start-$end/$size");
                header("Content-Length: $length");
            } else {
                header("Content-Length: $size");
            }

            $file = fopen($audio_path, 'rb');
            fseek($file, $start);
            while (!feof($file) && ($p = ftell($file)) <= $end) {
                if ($p + 1024 * 16 > $end) {
                    echo fread($file, $end - $p + 1);
                } else {
                    echo fread($file, 1024 * 16);
                }
                flush();
            }

            fclose($file);
            exit;
        } else {
            status_header(404);
            die('Archivo no encontrado.');
        }
    }
}
/*
add_action('template_redirect', 'custom_cache_handler');
function custom_cache_handler() {
    $log_file_path = '/var/www/html/wp-content/themes/wanlog.txt';
    error_log("custom_cache_handler invoked\n", 3, $log_file_path);

    if (isset($_GET['custom-cache']) && $_GET['custom-cache'] == '1' && isset($_GET['file_path']) && !empty($_GET['file_path'])) {
        $file_path = sanitize_text_field(urldecode($_GET['file_path']));
        error_log("File path parameter: " . $file_path . "\n", 3, $log_file_path);
        
        $upload_dir = wp_upload_dir();
        error_log("Upload directory: " . print_r($upload_dir, true) . "\n", 3, $log_file_path);
        $full_file_path = $upload_dir['basedir'] . '/' . $file_path;
        
        error_log("Full file path: " . $full_file_path . "\n", 3, $log_file_path);

        if (file_exists($full_file_path) && is_file($full_file_path)) {
            error_log("File exists: " . $full_file_path . "\n", 3, $log_file_path);
            $file_extension = strtolower(pathinfo($full_file_path, PATHINFO_EXTENSION));
            $content_types = array(
                'css' => 'text/css',
                'js' => 'application/javascript',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            );

            if (!isset($content_types[$file_extension])) {
                error_log("Unsupported file extension: " . $file_extension . "\n", 3, $log_file_path);
                status_header(415);
                die('Tipo de archivo no soportado.');
            }

            $content_type = $content_types[$file_extension];
            $last_modified_time = filemtime($full_file_path);
            $etag = md5_file($full_file_path);
            $max_age = 30 * 24 * 60 * 60;

            header("Expires: " . gmdate("D, d M Y H:i:s", time() + $max_age) . " GMT"); 
            header("Pragma: cache"); 
            header("Cache-Control: max-age=$max_age, public"); 
            header("ETag: \"$etag\"");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");

            if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time) ||
                (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
                header("HTTP/1.1 304 Not Modified");
                exit;
            }

            header('Content-Type: ' . $content_type);
            header('Content-Length: ' . filesize($full_file_path));

            // Asegúrate de que no haya salida previa
            if (ob_get_length()) {
                ob_end_clean();
            }

            // Leer y enviar el archivo
            readfile($full_file_path);
            exit;
        } else {
            error_log("File not found or is not a file: " . $full_file_path . "\n", 3, $log_file_path);
            status_header(404);
            die('Archivo no encontrado o no es un archivo.');
        }
    } else {
        error_log("Required parameters not set or incorrect\n", 3, $log_file_path);
    }
}
*/