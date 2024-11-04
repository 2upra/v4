<?

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
    } else {
        header('Content-Length: ' . $size);
        fpassthru($fp);
    }
    
    fclose($fp);
    exit;
}