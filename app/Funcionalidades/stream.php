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
