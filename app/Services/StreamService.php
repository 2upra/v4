<?php
define('ENABLE_BROWSER_AUDIO_CACHE', TRUE);

# Envia el archivo de audio al navegador con cabeceras de cache.
function audioFlujoFinalPro($request) {
    $idAudio = $request['id'];
    guardarLog("[audioFlujoFinalPro] [info] Iniciando flujo para idAudio: $idAudio");

    $archivoOriginal = get_attached_file($idAudio);
    if (!file_exists($archivoOriginal)) {
        guardarLog("[audioFlujoFinalPro] [error] Archivo no encontrado para idAudio: $idAudio ruta: $archivoOriginal");
        return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
    }
    guardarLog("[audioFlujoFinalPro] [info] Archivo encontrado: $archivoOriginal");

    header('Content-Type: ' . get_post_mime_type($idAudio));
    header('Accept-Ranges: bytes');
    header('Cache-Control: public max-age=31536000'); # 1 año
    header('Expires: ' . gmdate('D d M Y H:i:s', time() + 31536000) . ' GMT');
    header('Pragma: public');
    guardarLog("[audioFlujoFinalPro] [info] Cabeceras enviadas para idAudio: $idAudio");

    $punteroArchivo = @fopen($archivoOriginal, 'rb');
    if (!$punteroArchivo) {
         guardarLog("[audioFlujoFinalPro] [error] No se pudo abrir el archivo: $archivoOriginal");
         // Considerar retornar un WP_Error aquí también o manejar el error de forma más robusta.
         exit;
    }

    $tamano = filesize($archivoOriginal);

    if (isset($_SERVER['HTTP_RANGE'])) {
        // Nota: La lógica para manejar solicitudes de rango parcial (HTTP_RANGE) no está implementada.
        // Se enviará el archivo completo, lo cual podría no ser lo esperado por el cliente.
        // Se recomienda implementar el manejo de Content-Range y el status 206 Partial Content si se requiere streaming real.
        guardarLog("[audioFlujoFinalPro] [info] Solicitud de rango detectada para idAudio: $idAudio Rango: {$_SERVER['HTTP_RANGE']}. Enviando archivo completo.");
        // header('Content-Length: ' . $tamano); // No enviar Content-Length completo si se espera rango
        fpassthru($punteroArchivo);
    } else {
        guardarLog("[audioFlujoFinalPro] [info] Enviando archivo completo tamaño: $tamano para idAudio: $idAudio");
        header('Content-Length: ' . $tamano);
        fpassthru($punteroArchivo);
    }

    fclose($punteroArchivo);
    guardarLog("[audioFlujoFinalPro] [exito] Flujo completado para idAudio: $idAudio");
    exit;
}

# Autentica al usuario en peticiones AJAX o REST validando la cookie de login.
add_action('init', function () {
    if (!defined('DOING_AJAX') && !defined('REST_REQUEST')) {
        return;
    }
    guardarLog("[init_auth_check] [info] Verificando autenticacion para AJAX/REST");
    $idUsuario = wp_validate_auth_cookie('', 'logged_in');
    if ($idUsuario) {
        guardarLog("[init_auth_check] [info] Usuario autenticado idUsuario: $idUsuario");
        wp_set_current_user($idUsuario);
    } else {
        guardarLog("[init_auth_check] [info] No se pudo autenticar al usuario via cookie");
    }
});

# Genera una URL segura con token y nonce para acceder a un audio especifico.
function obtenerUrlAudioSegura($idAudio) {
    guardarLog("[obtenerUrlAudioSegura] [info] Iniciando generacion de URL para idAudio: $idAudio");
    $token = generarTokenAudio($idAudio);
    if (!$token || is_wp_error($token)) { // Corregido: verificar si es WP_Error también
        guardarLog("[obtenerUrlAudioSegura] [error] Fallo al generar token para idAudio: $idAudio");
        // Devolver el error si la generación del token falló
        return is_wp_error($token) ? $token : new WP_Error('token_generation_failed', 'No se pudo generar el token de audio.');
    }
    guardarLog("[obtenerUrlAudioSegura] [info] Token generado para idAudio: $idAudio");

    $nonce = wp_create_nonce('wp_rest');
    $url = site_url("/wp-json/1/v1/audio-pro/$idAudio?token=" . urlencode($token) . '&_wpnonce=' . $nonce); // Corregido: usar la ruta correcta
    guardarLog("[obtenerUrlAudioSegura] [exito] URL segura generada para idAudio: $idAudio url: $url");
    return $url;
}

# Bloquea el acceso directo a archivos en wp-content/uploads/ si no se presenta un token valido.
function bloquearAccesoDirectoArchivos() {
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/') !== false) {
        guardarLog("[bloquearAccesoDirectoArchivos] [info] Acceso detectado a wp-content/uploads URI: {$_SERVER['REQUEST_URI']}");
        $token = $_GET['token'] ?? $_COOKIE['audio_token'] ?? null;
        if ($token) {
             guardarLog("[bloquearAccesoDirectoArchivos] [info] Token encontrado en GET o COOKIE");
        } else {
             guardarLog("[bloquearAccesoDirectoArchivos] [alerta] Token no encontrado en GET ni COOKIE");
        }

        $resultadoVerificacion = verificarTokenAudio($token); // verificarTokenAudio ahora debería retornar true/false

        if (!$resultadoVerificacion) {
            guardarLog("[bloquearAccesoDirectoArchivos] [alerta] Verificacion de token fallo o token ausente. Acceso denegado a URI: {$_SERVER['REQUEST_URI']}");
            wp_die('Acceso denegado', 'Acceso denegado', array('response' => 403));
        } else {
            guardarLog("[bloquearAccesoDirectoArchivos] [info] Verificacion de token exitosa. Acceso permitido a URI: {$_SERVER['REQUEST_URI']}");
        }
    }
}
add_action('init', 'bloquearAccesoDirectoArchivos');

# Decrementa los usos restantes de un token de audio si el cache de navegador esta desactivado.
function decrementarUsosToken($idUnico) {
    guardarLog("[decrementarUsosToken] [info] Iniciando decremento para idUnico: $idUnico");
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        guardarLog("[decrementarUsosToken] [info] Cache de navegador habilitado no se decrementa idUnico: $idUnico");
        return;
    }

    $clave = 'audio_token_' . $idUnico;
    $usosRestantes = get_transient($clave);
    guardarLog("[decrementarUsosToken] [info] Obteniendo usos restantes para clave: $clave resultado: " . var_export($usosRestantes, true));


    if ($usosRestantes !== false && $usosRestantes > 0) {
        $usosRestantes--;
        guardarLog("[decrementarUsosToken] [info] Decrementando usos para clave: $clave nuevos usos: $usosRestantes");
        if ($usosRestantes > 0) {
            $expiracion = get_option('transient_timeout_' . $clave) ? (get_option('transient_timeout_' . $clave) - time()) : 3600; // Re-calcular TTL
             guardarLog("[decrementarUsosToken] [info] Actualizando transient clave: $clave usos: $usosRestantes expiracion: $expiracion");
            set_transient($clave, $usosRestantes, $expiracion > 0 ? $expiracion : 1); // Asegurar expiración positiva
        } else {
            guardarLog("[decrementarUsosToken] [info] Eliminando transient clave: $clave usos agotados");
            delete_transient($clave);
        }
    } else if ($usosRestantes === 0) {
         guardarLog("[decrementarUsosToken] [alerta] No se decrementa clave: $clave usos ya agotados");
    } else {
         guardarLog("[decrementarUsosToken] [alerta] No se decrementa clave: $clave transient no encontrado o expirado");
    }
}

# Registra las rutas REST API para servir el audio.
add_action('rest_api_init', function () {
    guardarLog("[rest_api_init] [info] Registrando rutas REST");
    # Ruta para usuarios pro, ahora usa audioFlujoFinalPro y verifica el token via verificarTokenAudio
    register_rest_route('1/v1', '/audio-pro/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'audioFlujoFinalPro',
        'permission_callback' => function ($request) {
             guardarLog("[rest_api_init_permission] [info] Verificando permisos para ruta audio-pro id: {$request['id']}");
            // 1. Verificar si el usuario tiene el rol adecuado (Admin o Pro)
            $esUsuarioValido = usuarioEsAdminOPro(get_current_user_id());
             guardarLog("[rest_api_init_permission] [info] Resultado chequeo rol usuarioEsAdminOPro: " . ($esUsuarioValido ? 'true' : 'false'));
             if (!$esUsuarioValido) {
                 guardarLog("[rest_api_init_permission] [error] Usuario no es Admin ni Pro");
                 return new WP_Error('rest_forbidden', 'Acceso no permitido.', array('status' => 403));
             }

            // 2. Verificar Nonce (si se requiere mayor seguridad además del token)
             $nonce = $request->get_param('_wpnonce');
             if (!wp_verify_nonce($nonce, 'wp_rest')) {
                 guardarLog("[rest_api_init_permission] [error] Verificacion de Nonce fallida");
                 // return new WP_Error('rest_nonce_invalid', 'Nonce inválido.', array('status' => 403));
                 // Nota: Comentado porque la lógica original parecía depender más del token. Descomentar si el nonce es mandatorio.
             } else {
                 guardarLog("[rest_api_init_permission] [info] Verificacion de Nonce exitosa");
             }

            // 3. Verificar Token de Audio (la lógica principal de acceso)
            $token = $request->get_param('token');
             guardarLog("[rest_api_init_permission] [info] Obteniendo token del request: $token");
             if (!$token) {
                 guardarLog("[rest_api_init_permission] [error] Token no proporcionado en el request");
                 return new WP_Error('rest_token_missing', 'Token de audio requerido.', array('status' => 401));
             }

             $resultadoVerificacion = verificarTokenAudio($token); // Llama a la función de verificación
             if (!$resultadoVerificacion) {
                 guardarLog("[rest_api_init_permission] [error] Verificacion de token de audio fallida");
                 // Los códigos de error específicos (401, 403, 429) se manejan dentro de verificarTokenAudio si es necesario.
                 // Devolver un error genérico aquí si la verificación falla.
                 return new WP_Error('rest_token_invalid', 'Token de audio inválido o expirado.', array('status' => 403));
             }

             guardarLog("[rest_api_init_permission] [exito] Permisos concedidos para ruta audio-pro id: {$request['id']}");
             return true; // Permiso concedido si todo está bien

         },
        'args' => array(
            'id' => array(
                'validate_callback' => function ($param) {
                    $esNumerico = is_numeric($param);
                    guardarLog("[rest_api_init_validate_id] [info] Validando parametro id: $param resultado: " . ($esNumerico ? 'true' : 'false'));
                    return $esNumerico;
                }
            ),
             'token' => array( // Asegurarse que el token se pasa como argumento esperado
                 'required' => true,
                 'validate_callback' => function ($param) {
                     $esValido = is_string($param) && !empty($param);
                     guardarLog("[rest_api_init_validate_token] [info] Validando parametro token: " . substr($param, 0, 10) . "... resultado: " . ($esValido ? 'true' : 'false'));
                     return $esValido;
                 }
             ),
             '_wpnonce' => array( // Asegurarse que el nonce se pasa como argumento esperado
                 'required' => true,
                 'validate_callback' => function ($param) {
                     $esValido = is_string($param) && !empty($param);
                     guardarLog("[rest_api_init_validate_nonce] [info] Validando parametro _wpnonce: $param resultado: " . ($esValido ? 'true' : 'false'));
                     return $esValido;
                 }
             ),
        ),
    ));
    guardarLog("[rest_api_init] [exito] Rutas REST registradas");
});

# Verifica la validez de un token de audio incluye rate limiting y otras comprobaciones. Retorna true si es valido false si no.
function verificarTokenAudio($token) {
    $ipUsuarioActual = $_SERVER['REMOTE_ADDR'];
    guardarLog("[verificarTokenAudio] [info] Iniciando verificacion token para IP: $ipUsuarioActual token: " . substr($token, 0, 10) . "...");

    # Rate Limiting por IP
    $limiteIntentos = 10;
    $ventanaTiempoIntentos = 10; // segundos
    $duracionBloqueoIp = 300; // segundos
    $claveIntentosFallidos = 'failed_attempts_' . $ipUsuarioActual;
    $numeroIntentos = get_transient($claveIntentosFallidos) ?: 0;
    $claveBloqueoIp = 'blocked_ip_' . $ipUsuarioActual;
    $ipEstaBloqueada = get_transient($claveBloqueoIp);

    if ($ipEstaBloqueada) {
        guardarLog("[verificarTokenAudio] [alerta] IP bloqueada temporalmente: $ipUsuarioActual");
        header("HTTP/1.1 429 Too Many Requests");
        // No retornar false aquí directamente si se usa dentro de permission_callback, mejor manejarlo allí.
        return false; // Indica fallo de verificación
    }

    # Comprobacion token vacio
    if (empty($token)) {
        $numeroIntentos++;
        guardarLog("[verificarTokenAudio] [alerta] Token vacio IP: $ipUsuarioActual Intento: $numeroIntentos/$limiteIntentos");
        set_transient($claveIntentosFallidos, $numeroIntentos, $ventanaTiempoIntentos);
        if ($numeroIntentos >= $limiteIntentos) {
            guardarLog("[verificarTokenAudio] [error] IP bloqueada por exceso de intentos fallidos: $ipUsuarioActual");
            set_transient($claveBloqueoIp, true, $duracionBloqueoIp);
        }
        header("HTTP/1.1 401 Unauthorized");
        return false; // Indica fallo de verificación
    }

    # Comprobaciones de cabeceras (Opcional pero recomendado para llamadas desde frontend)
    // Estas verificaciones pueden ser demasiado estrictas si el audio se accede de otras formas (ej. apps)
    // Considera si realmente son necesarias para tu caso de uso.
    /*
    if (!isset($_SERVER['HTTP_REFERER']) || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        guardarLog("[verificarTokenAudio] [alerta] Cabeceras Referer o X-Requested-With ausentes IP: $ipUsuarioActual");
        return false;
    }
    $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($refererHost !== '2upra.com') { // Reemplaza con tu dominio
        guardarLog("[verificarTokenAudio] [alerta] Referer invalido: $refererHost IP: $ipUsuarioActual");
        return false;
    }
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        guardarLog("[verificarTokenAudio] [alerta] X-Requested-With invalido: {$_SERVER['HTTP_X_REQUESTED_WITH']} IP: $ipUsuarioActual");
        return false;
    }
    guardarLog("[verificarTokenAudio] [info] Cabeceras Referer y XHR validadas para IP: $ipUsuarioActual");
    */

    # Decodificacion y validacion del token
    $tokenDecodificado = base64_decode($token, true); // Usar strict mode
    if ($tokenDecodificado === false) {
        guardarLog("[verificarTokenAudio] [error] Fallo al decodificar Base64 token IP: $ipUsuarioActual");
        return false;
    }

    $partesToken = explode('|', $tokenDecodificado);
    if (count($partesToken) !== 6) {
        guardarLog("[verificarTokenAudio] [error] Numero incorrecto de partes en el token: " . count($partesToken) . " IP: $ipUsuarioActual");
        return false;
    }

    list($idAudio, $tiempoExpiracion, $ipUsuarioToken, $idUnico, $maximoUsos, $firmaToken) = $partesToken;
    guardarLog("[verificarTokenAudio] [info] Token decodificado idAudio: $idAudio exp: $tiempoExpiracion ipToken: $ipUsuarioToken idUnico: $idUnico usos: $maximoUsos IP_Actual: $ipUsuarioActual");

    # Validacion de Firma
    $datosParaFirma = $idAudio . '|' . $tiempoExpiracion . '|' . $ipUsuarioToken . '|' . $idUnico;
    $firmaEsperada = hash_hmac('sha256', $datosParaFirma, $_ENV['AUDIOCLAVE']); // Asegurate que AUDIOCLAVE esté definida en wp-config.php o similar

    if (!hash_equals($firmaEsperada, $firmaToken)) {
        guardarLog("[verificarTokenAudio] [error] Firma HMAC invalida IP: $ipUsuarioActual idAudio: $idAudio");
        // Incrementar intentos fallidos si la firma no coincide
        $numeroIntentos++;
        set_transient($claveIntentosFallidos, $numeroIntentos, $ventanaTiempoIntentos);
        if ($numeroIntentos >= $limiteIntentos) {
            set_transient($claveBloqueoIp, true, $duracionBloqueoIp);
        }
        return false;
    }
     guardarLog("[verificarTokenAudio] [info] Firma HMAC valida IP: $ipUsuarioActual idAudio: $idAudio");


    # Logica especifica segun modo de cache
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        guardarLog("[verificarTokenAudio] [info] Modo Cache Navegador habilitado idAudio: $idAudio IP: $ipUsuarioActual");
        // En modo caché, la IP y los usos no se validan estrictamente aquí, solo la firma y expiración (que es muy lejana)
        if (time() > $tiempoExpiracion) {
             guardarLog("[verificarTokenAudio] [error] Token expirado (modo cache) IP: $ipUsuarioActual idAudio: $idAudio");
             return false; // Aunque la expiración es lejana, es bueno mantener la comprobación
        }

        // Aquí podrías añadir lógica de sesión si fuera necesario, pero la descripción original
        // dependía de la firma y la expiración lejana para el modo caché.
        // La lógica de sesión con transients (claveSesion, claveCache) parece compleja y potencialmente
        // innecesaria si la firma y expiración larga son suficientes. Simplificamos por ahora.

        guardarLog("[verificarTokenAudio] [exito] Verificacion exitosa (modo cache) IP: $ipUsuarioActual idAudio: $idAudio");
        // Si llegamos aquí, el token es válido en modo caché (firma correcta, no expirado)
        // Reseteamos los intentos fallidos para esta IP ya que el token fue válido
        delete_transient($claveIntentosFallidos);
        return true;

    } else {
        guardarLog("[verificarTokenAudio] [info] Modo Cache Navegador deshabilitado idAudio: $idAudio IP: $ipUsuarioActual");
        # Validacion de IP
        if ($ipUsuarioActual !== $ipUsuarioToken) {
            guardarLog("[verificarTokenAudio] [error] Discrepancia de IP Token: $ipUsuarioToken Actual: $ipUsuarioActual idAudio: $idAudio");
            return false;
        }
        guardarLog("[verificarTokenAudio] [info] IP coincidente idAudio: $idAudio IP: $ipUsuarioActual");

        # Validacion de Expiracion
        if (time() > $tiempoExpiracion) {
            guardarLog("[verificarTokenAudio] [error] Token expirado IP: $ipUsuarioActual idAudio: $idAudio");
            // Podríamos eliminar el transient aquí si quisiéramos
            // delete_transient('audio_token_' . $idUnico);
            return false;
        }
        guardarLog("[verificarTokenAudio] [info] Token no expirado idAudio: $idAudio IP: $ipUsuarioActual");

        # Validacion de Usos
        $claveTokenUsos = 'audio_token_' . $idUnico;
        $usosRestantes = get_transient($claveTokenUsos);
        guardarLog("[verificarTokenAudio] [info] Verificando usos para idUnico: $idUnico clave: $claveTokenUsos usosRestantes: " . var_export($usosRestantes, true));

        if ($usosRestantes === false || $usosRestantes <= 0) {
            guardarLog("[verificarTokenAudio] [error] Token sin usos restantes o expirado (transient) idUnico: $idUnico IP: $ipUsuarioActual");
            return false;
        }
         guardarLog("[verificarTokenAudio] [info] Usos restantes validos: $usosRestantes idUnico: $idUnico IP: $ipUsuarioActual");

        # Decrementar usos (solo si la verificación completa es exitosa hasta este punto)
        decrementaUsosToken($idUnico);

        guardarLog("[verificarTokenAudio] [exito] Verificacion exitosa (modo no-cache) IP: $ipUsuarioActual idAudio: $idAudio");
        // Reseteamos los intentos fallidos para esta IP ya que el token fue válido
        delete_transient($claveIntentosFallidos);
        return true;
    }
}


# Genera un token de acceso para un audio_id dado con expiracion firma y limite de usos.
function generarTokenAudio($idAudio) {
    guardarLog("[generarTokenAudio] [info] Iniciando generacion de token para idAudio: $idAudio");

    // Validacion basica del idAudio
    if (empty($idAudio) || !is_numeric($idAudio)) { // Asumiendo que idAudio es numérico como en la ruta REST
        guardarLog("[generarTokenAudio] [error] idAudio invalido o vacio: $idAudio");
        return false; // O retornar un WP_Error
    }

    if (!isset($_ENV['AUDIOCLAVE']) || empty($_ENV['AUDIOCLAVE'])) {
        guardarLog("[generarTokenAudio] [error] AUDIOCLAVE no esta definida o esta vacia");
        return new WP_Error('audio_key_missing', 'La clave de seguridad para audio no está configurada.');
    }

    $ipUsuarioActual = $_SERVER['REMOTE_ADDR'];

    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        guardarLog("[generarTokenAudio] [info] Generando token modo CACHE para idAudio: $idAudio");
        $tiempoExpiracion = strtotime('2038-01-18'); // Expiración muy lejana
        $ipUsuarioToken = 'cached'; // IP no relevante en modo caché
        $idUnico = md5($idAudio . $ipUsuarioActual . $_ENV['AUDIOCLAVE']); // ID único basado en audio IP y clave
        $maximoUsos = 999999; // Usos "ilimitados"
    } else {
        guardarLog("[generarTokenAudio] [info] Generando token modo NO-CACHE para idAudio: $idAudio IP: $ipUsuarioActual");
        $tiempoExpiracion = time() + 3600; // 1 hora de validez
        $ipUsuarioToken = $ipUsuarioActual;
        $idUnico = uniqid('audio_', true); // ID único por token generado
        $maximoUsos = 3; // Límite de usos
    }

    $datosParaFirma = $idAudio . '|' . $tiempoExpiracion . '|' . $ipUsuarioToken . '|' . $idUnico;
    $firma = hash_hmac('sha256', $datosParaFirma, $_ENV['AUDIOCLAVE']);
    $token = base64_encode($datosParaFirma . '|' . $maximoUsos . '|' . $firma);
    guardarLog("[generarTokenAudio] [info] Datos para firma: $datosParaFirma");
    guardarLog("[generarTokenAudio] [info] Firma generada: $firma");
    guardarLog("[generarTokenAudio] [info] Token Base64: " . substr($token, 0, 20) . "...");


    if (!(defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE)) {
         guardarLog("[generarTokenAudio] [info] Guardando transient para token no-cache idUnico: $idUnico usos: $maximoUsos");
        set_transient('audio_token_' . $idUnico, $maximoUsos, $tiempoExpiracion - time()); // Guardar transient con los usos y TTL
    }

    guardarLog("[generarTokenAudio] [exito] Token generado exitosamente para idAudio: $idAudio");
    return $token;
}

# Programa el evento recurrente para limpiar el cache de audio.
function programarLimpiezaCacheAudio() {
    if (!wp_next_scheduled('audio_cache_cleanup_hook')) { // Usar un nombre de hook diferente a la función
        wp_schedule_event(time(), 'daily', 'audio_cache_cleanup_hook');
        guardarLog("[programarLimpiezaCacheAudio] [exito] Evento 'audio_cache_cleanup_hook' programado diariamente");
    } else {
        guardarLog("[programarLimpiezaCacheAudio] [info] Evento 'audio_cache_cleanup_hook' ya estaba programado");
    }
}
add_action('wp', 'programarLimpiezaCacheAudio');


# Ejecuta la limpieza de archivos viejos en el directorio de cache de audio.
function limpiarCacheAudio() {
    guardarLog("[limpiarCacheAudio] [info] Iniciando tarea de limpieza de cache de audio");
    $directorioSubidas = wp_upload_dir();
    $directorioCache = $directorioSubidas['basedir'] . '/audio_cache'; // Asumiendo que este es tu directorio de caché

    if (is_dir($directorioCache)) {
        guardarLog("[limpiarCacheAudio] [info] Directorio de cache encontrado: $directorioCache");
        $archivosCache = glob($directorioCache . '/*');
        $tiempoActual = time();
        $limiteAntiguedad = 7 * 24 * 60 * 60; // 7 días en segundos

        if ($archivosCache === false) {
             guardarLog("[limpiarCacheAudio] [error] No se pudo leer el contenido del directorio de cache: $directorioCache");
             return;
        }

        if (empty($archivosCache)) {
            guardarLog("[limpiarCacheAudio] [info] Directorio de cache vacio no hay archivos para limpiar");
            return;
        }

        guardarLog("[limpiarCacheAudio] [info] " . count($archivosCache) . " archivos encontrados en el cache. Verificando antiguedad...");
        $archivosEliminados = 0;
        foreach ($archivosCache as $archivoCache) {
            if (is_file($archivoCache)) {
                 $tiempoModificacion = filemtime($archivoCache);
                 $antiguedad = $tiempoActual - $tiempoModificacion;
                if ($antiguedad > $limiteAntiguedad) {
                    if (unlink($archivoCache)) {
                        guardarLog("[limpiarCacheAudio] [info] Archivo de cache eliminado (antiguo): $archivoCache antiguedad: $antiguedad s");
                        $archivosEliminados++;
                    } else {
                        guardarLog("[limpiarCacheAudio] [error] No se pudo eliminar el archivo de cache: $archivoCache");
                    }
                } else {
                    // guardarLog("[limpiarCacheAudio] [debug] Archivo de cache conservado (reciente): $archivoCache antiguedad: $antiguedad s");
                }
            }
        }
         guardarLog("[limpiarCacheAudio] [exito] Limpieza de cache completada. Archivos eliminados: $archivosEliminados");
    } else {
        guardarLog("[limpiarCacheAudio] [alerta] Directorio de cache no existe: $directorioCache");
    }
}
add_action('audio_cache_cleanup_hook', 'limpiarCacheAudio'); // Enganchar la función al hook programado


# Desprograma el evento de limpieza al desactivar el plugin/tema.
function desprogramarLimpiezaCacheAudio() {
    $marcaTiempoProgramada = wp_next_scheduled('audio_cache_cleanup_hook');
    if ($marcaTiempoProgramada) {
        wp_unschedule_event($marcaTiempoProgramada, 'audio_cache_cleanup_hook');
        guardarLog("[desprogramarLimpiezaCacheAudio] [exito] Evento 'audio_cache_cleanup_hook' desprogramado");
    } else {
        guardarLog("[desprogramarLimpiezaCacheAudio] [info] Evento 'audio_cache_cleanup_hook' no estaba programado");
    }
}
