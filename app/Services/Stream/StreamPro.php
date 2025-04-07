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

# Registra la ruta REST para servir audio a usuarios Pro.
add_action('rest_api_init', function () {
    guardarLog("[rest_api_init_hook] inicio Registrando rutas REST");
    register_rest_route('1/v1', '/audio-pro/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'audioStreamEndPro',
        'permission_callback' => function () {
             $userId = get_current_user_id();
             $esPro = usuarioEsAdminOPro($userId);
             guardarLog("[rest_api_init_hook] info Verificando permisos para usuario ID: $userId resultado: " . ($esPro ? 'permitido' : 'denegado'));
             return $esPro;
        },
        'args' => array(
            'id' => array(
                'validate_callback' => function ($param) {
                    $esNumerico = is_numeric($param);
                    guardarLog("[rest_api_init_hook] info Validando parametro id: $param resultado: " . ($esNumerico ? 'valido' : 'invalido'));
                    return $esNumerico;
                }
            ),
        ),
    ));
     guardarLog("[rest_api_init_hook] exito Rutas REST registradas");
});
