<?php

# Envía el archivo de audio aplicando caché.
function audioStreamEndPro($request)
{
    $idAudio = $request['id'];
    guardarLog("[audioStreamEndPro] inicio Procesando solicitud para idAudio: $idAudio");

    $rutaArchivoOriginal = get_attached_file($idAudio);
    if (!file_exists($rutaArchivoOriginal)) {
        guardarLog("[audioStreamEndPro] error Archivo no encontrado para idAudio: $idAudio ruta: $rutaArchivoOriginal");
        return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
    }
    guardarLog("[audioStreamEndPro] info Archivo encontrado: $rutaArchivoOriginal");

    guardarLog("[audioStreamEndPro] info Enviando headers para idAudio: $idAudio");
    header('Content-Type: ' . get_post_mime_type($idAudio));
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=31536000'); # 1 año
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    header('Pragma: public');

    $punteroArchivo = @fopen($rutaArchivoOriginal, 'rb');
    $tamanoArchivo = filesize($rutaArchivoOriginal);

    if (isset($_SERVER['HTTP_RANGE'])) {
        // Logica para manejar range requests (no implementada en el original pero se podria loggear aqui)
        guardarLog("[audioStreamEndPro] info Solicitud con HTTP_RANGE detectada (logica no implementada aqui)");
    } else {
        guardarLog("[audioStreamEndPro] info Enviando contenido completo del archivo idAudio: $idAudio tamano: $tamanoArchivo");
        header('Content-Length: ' . $tamanoArchivo);
        fpassthru($punteroArchivo);
    }

    fclose($punteroArchivo);
    guardarLog("[audioStreamEndPro] exito Finalizado envio para idAudio: $idAudio");
    exit;
}
