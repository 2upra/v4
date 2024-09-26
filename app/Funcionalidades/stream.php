<?php
/*
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
