<?php
define('ENABLE_BROWSER_AUDIO_CACHE', TRUE);

# Envía el archivo de audio directamente con headers de caché agresivos.
function audioStreamEndPro($request) {
    guardarLog("audioStreamEndPro", "Inicio de la función");
    $idAudio = $request['id'];
    guardarLog("audioStreamEndPro", "ID del audio: $idAudio");

    $archivoOriginal = get_attached_file($idAudio);
    guardarLog("audioStreamEndPro", "Archivo original: $archivoOriginal");

    if (!file_exists($archivoOriginal)) {
        guardarLog("audioStreamEndPro", "Error: Archivo de audio no encontrado.");
        return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
    }

    $tipoMime = get_post_mime_type($idAudio);
    guardarLog("audioStreamEndPro", "Tipo MIME: $tipoMime");
    header('Content-Type: ' . $tipoMime);
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=31536000'); # 1 año
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    header('Pragma: public');

    $fp = @fopen($archivoOriginal, 'rb');
    if (!$fp) {
        guardarLog("audioStreamEndPro", "Error: No se pudo abrir el archivo.");
        return new WP_Error('file_open_error', 'No se pudo abrir el archivo de audio.', array('status' => 500));
    }

    $tamano = filesize($archivoOriginal);
    guardarLog("audioStreamEndPro", "Tamaño del archivo: $tamano");

    if (isset($_SERVER['HTTP_RANGE'])) {
        guardarLog("audioStreamEndPro", "Solicitud de rango detectada.");
        # @TODO: Implementar soporte para rangos
    } else {
        guardarLog("audioStreamEndPro", "Enviando el archivo completo.");
        header('Content-Length: ' . $tamano);
        fpassthru($fp);
        guardarLog("audioStreamEndPro", "Archivo enviado.");
    }

    fclose($fp);
    guardarLog("audioStreamEndPro", "Conexión cerrada.");
    exit;
}

# Autentica al usuario si está haciendo una petición AJAX o REST.
add_action('init', function () {
    if (!defined('DOING_AJAX') && !defined('REST_REQUEST')) {
        return;
    }
    $idUsuario = wp_validate_auth_cookie('', 'logged_in');
    if ($idUsuario) {
        wp_set_current_user($idUsuario);
    }
});

# Genera una URL segura para acceder al audio.
function audioUrlSegura($idAudio) {
    guardarLog("audioUrlSegura", "Generando URL segura para audio_id: $idAudio");
    $token = tokenAudio($idAudio);
    if (!$token) {
        guardarLog("audioUrlSegura", "Error: Token inválido para audio_id: $idAudio");
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }

    $nonce = wp_create_nonce('wp_rest');
    $url = site_url("/wp-json/1/v1/2?token=" . urlencode($token) . '&_wpnonce=' . $nonce);
    guardarLog("audioUrlSegura", "URL generada: $url");
    return $url;
}

# Bloquea el acceso directo a los archivos de audio.
function bloquear_acceso_directo_archivos() {
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/') !== false) {
        $token = $_GET['token'] ?? $_COOKIE['audio_token'] ?? null;
        if (!$token || !verificarAudio($token)) {
            guardarLog("bloquear_acceso_directo_archivos", "Acceso denegado por token inválido o inexistente.");
            wp_die('Acceso denegado', 'Acceso denegado', array('response' => 403));
        }
    }
}
add_action('init', 'bloquear_acceso_directo_archivos');

# Decrementa el número de usos restantes del token.
function decrementaUsosToken($idUnico) {
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        return;
    }

    $clave = 'audio_token_' . $idUnico;
    $usosRestantes = get_transient($clave);

    if ($usosRestantes !== false && $usosRestantes > 0) {
        $usosRestantes--;
        if ($usosRestantes > 0) {
            set_transient($clave, $usosRestantes, get_option('transient_timeout_' . $clave));
        } else {
            delete_transient($clave);
        }
    }
}

# Registra las rutas de la API REST.
add_action('rest_api_init', function () {
    # Ruta para usuarios pro
    register_rest_route('1/v1', '/audio-pro/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'audioStreamEndPro',
        'permission_callback' => function () {
            return usuarioEsAdminOPro(get_current_user_id());
        },
        'args' => array(
            'id' => array(
                'validate_callback' => function ($param) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
});

# Verifica la validez del token de audio.
function verificarAudio($token) {
    guardarLog("verificarAudio", "Verificando token: $token");

    $ip = $_SERVER['REMOTE_ADDR'];
    $limite = 10;
    $ventanaTiempo = 10;
    $duracionBloqueo = 300;

    $claveIntentos = 'failed_attempts_' . $ip;
    $intentos = get_transient($claveIntentos);

    if ($intentos === false) {
        $intentos = 0;
    }

    $claveBloqueo = 'blocked_ip_' . $ip;
    $estaBloqueado = get_transient($claveBloqueo);

    if ($estaBloqueado !== false) {
        guardarLog("verificarAudio", "Acceso bloqueado por demasiados intentos fallidos desde IP: $ip");
        header("HTTP/1.1 429 Too Many Requests");
        return false;
    }

    if (empty($token)) {
        $intentos++;
        set_transient($claveIntentos, $intentos, $ventanaTiempo);

        if ($intentos >= $limite) {
            set_transient($claveBloqueo, true, $duracionBloqueo);
            guardarLog("verificarAudio", "IP bloqueada por superar el límite de intentos fallidos: $ip");
        }
        guardarLog("verificarAudio", "Token vacío. Intentos fallidos: $intentos");
        header("HTTP/1.1 401 Unauthorized");
        return false;
    }

    if (!isset($_SERVER['HTTP_REFERER']) || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        guardarLog("verificarAudio", "Referer o X-Requested-With no definidos.");
        return false;
    }

    $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($refererHost !== '2upra.com') {
        guardarLog("verificarAudio", "Referer no coincide. Referer: $refererHost");
        return false;
    }

    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        guardarLog("verificarAudio", "X-Requested-With no es XMLHttpRequest.");
        return false;
    }

    $decodificado = base64_decode($token);
    $partes = explode('|', $decodificado);
    if (count($partes) !== 6) {
        guardarLog("verificarAudio", "Token inválido. Número de partes incorrecto.");
        return false;
    }

    list($idAudio, $expiracion, $ipUsuario, $idUnico, $maxUsos, $firma) = $partes;
    guardarLog("verificarAudio", "Token decodificado. ID Audio: $idAudio, Expiración: $expiracion, IP Usuario: $ipUsuario, ID Unico: $idUnico, Max Usos: $maxUsos, Firma: $firma");

    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        $claveSesion = 'audio_session_' . $idAudio . '_' . $_SERVER['REMOTE_ADDR'];
        $claveCache = 'audio_access_' . $idAudio . '_' . $_SERVER['REMOTE_ADDR'];

        $data = $idAudio . '|' . $expiracion . '|' . $ipUsuario . '|' . $idUnico;
        $firmaEsperada = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (!hash_equals($firmaEsperada, $firma)) {
            guardarLog("verificarAudio", "Firma no coincide (cache).");
            return false;
        }

        $sesionActual = get_transient($claveSesion);

        if ($sesionActual === false) {
            set_transient($claveSesion, $token, 3600);
            set_transient($claveCache, 1, 3600);
            guardarLog("verificarAudio", "Nueva sesión iniciada y caché activado.");
            return true;
        } else {
            $conteoAccesos = get_transient($claveCache);

            if (
                $conteoAccesos !== false &&
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' &&
                strpos($_SERVER['HTTP_REFERER'], '2upra.com') !== false
            ) {
                set_transient($claveCache, $conteoAccesos + 1, 3600);
                guardarLog("verificarAudio", "Acceso permitido desde caché. Conteo: " . ($conteoAccesos + 1));
                return true;
            }

            guardarLog("verificarAudio", "Acceso denegado. No cumple condiciones de caché.");
            return false;
        }
    } else {
        if ($_SERVER['REMOTE_ADDR'] !== $ipUsuario) {
            guardarLog("verificarAudio", "IP no coincide. IP Servidor: " . $_SERVER['REMOTE_ADDR'] . ", IP Token: $ipUsuario");
            return false;
        }

        if (time() > $expiracion) {
            guardarLog("verificarAudio", "Token expirado.");
            return false;
        }

        $usosRestantes = get_transient('audio_token_' . $idUnico);
        if ($usosRestantes === false || $usosRestantes <= 0) {
            guardarLog("verificarAudio", "No quedan usos restantes para el token.");
            return false;
        }

        $data = $idAudio . '|' . $expiracion . '|' . $ipUsuario . '|' . $idUnico;
        $firmaEsperada = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (hash_equals($firmaEsperada, $firma)) {
            decrementaUsosToken($idUnico);
            guardarLog("verificarAudio", "Token verificado con éxito. Usos restantes decrementados.");
            return true;
        }

        guardarLog("verificarAudio", "Firma no coincide (no cache).");
        return false;
    }
}

# Genera un token para el audio.
function tokenAudio($idAudio) {
    guardarLog("tokenAudio", "Generando token para audio_id: $idAudio");
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $idAudio)) {
        guardarLog("tokenAudio", "Error: ID de audio inválido. Caracteres no alfanuméricos detectados.");
        return false;
    }

    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        $expiracion = strtotime('2030-12-31');
        $ipUsuario = 'cached';
        $idUnico = md5($idAudio . $_ENV['AUDIOCLAVE']);
        $maxUsos = 999999;

        $data = $idAudio . '|' . $expiracion . '|' . $ipUsuario . '|' . $idUnico;
        $firma = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);
        $token = base64_encode($data . '|' . $maxUsos . '|' . $firma);
        guardarLog("tokenAudio", "Token generado (cache): $token");

        return $token;
    } else {
        $expiracion = time() + 3600;
        $ipUsuario = $_SERVER['REMOTE_ADDR'];
        $idUnico = uniqid('', true);
        $maxUsos = 3;

        $data = $idAudio . '|' . $expiracion . '|' . $ipUsuario . '|' . $idUnico;
        $firma = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);
        $token = base64_encode($data . '|' . $maxUsos . '|' . $firma);

        set_transient('audio_token_' . $idUnico, $maxUsos, 3600);
        guardarLog("tokenAudio", "Token generado (no cache): $token");

        return $token;
    }
}

# Programa la limpieza del caché de audio.
add_action('wp', 'programarLimpiezaCacheAudio');

function programarLimpiezaCacheAudio() {
    if (!wp_next_scheduled('audio_cache_cleanup')) {
        wp_schedule_event(time(), 'daily', 'audio_cache_cleanup');
    }
}

# Limpia el caché de audio.
add_action('audio_cache_cleanup', 'limpiarCacheAudio');

function limpiarCacheAudio() {
    $directorioSubida = wp_upload_dir();
    $directorioCache = $directorioSubida['basedir'] . '/audio_cache';

    if (is_dir($directorioCache)) {
        $archivos = glob($directorioCache . '/*');
        $ahora = time();

        foreach ($archivos as $archivo) {
            if (is_file($archivo) && ($ahora - filemtime($archivo) > 7 * 24 * 60 * 60)) {
                unlink($archivo);
            }
        }
    }
}

# Desprograma la limpieza del caché de audio al desactivar el plugin.
function desprogramarLimpiezaCacheAudio() {
    $marcaTiempo = wp_next_scheduled('audio_cache_cleanup');
    if ($marcaTiempo) {
        wp_unschedule_event($marcaTiempo, 'audio_cache_cleanup');
    }
}
register_deactivation_hook(__FILE__, 'desprogramarLimpiezaCacheAudio');
