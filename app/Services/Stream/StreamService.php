<?php
# app/Services/StreamService.php
define('ENABLE_BROWSER_AUDIO_CACHE', TRUE);

add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/2', array(
        'methods' => 'GET',
        'callback' => 'audioStreamEnd',
        'args' => array(
            'token' => array(
                'required' => true,
            ),
        ),
        'permission_callback' => function ($request) {
            //guardarLog('Verificando permiso para token: ' . $request->get_param('token'));
            return verificarAudio($request->get_param('token'));
        }
    ));
});

# Autentica usuario en peticiones AJAX o REST al inicio.
add_action('init', function () {
    guardarLog("[init_auth_hook] inicio Verificando autenticacion");
    if (!defined('DOING_AJAX') && !defined('REST_REQUEST')) {
        guardarLog("[init_auth_hook] info Omitiendo verificacion no es AJAX ni REST");
        return;
    }
    $idUsuarioValidado = wp_validate_auth_cookie('', 'logged_in');
    if ($idUsuarioValidado) {
        guardarLog("[init_auth_hook] exito Usuario autenticado idUsuarioValidado: $idUsuarioValidado");
        wp_set_current_user($idUsuarioValidado);
    } else {
        guardarLog("[init_auth_hook] info No se encontro cookie de autenticacion valida");
    }
});

# Genera una URL segura con token para acceder al audio.
function audioUrlSegura($idAudio)
{
    guardarLog("[audioUrlSegura] inicio Generando URL para idAudio: $idAudio");
    $tokenGenerado = tokenAudio($idAudio);
    if (!$tokenGenerado) {
        guardarLog("[audioUrlSegura] error Fallo al generar token para idAudio: $idAudio");
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }
    guardarLog("[audioUrlSegura] info Token generado (parcial): " . substr($tokenGenerado, 0, 10) . "...");

    $nonceSeguridad = wp_create_nonce('wp_rest');
    guardarLog("[audioUrlSegura] info Nonce generado: $nonceSeguridad");
    $urlSegura = site_url("/wp-json/1/v1/2?token=" . urlencode($tokenGenerado) . '&_wpnonce=' . $nonceSeguridad);
    guardarLog("[audioUrlSegura] exito URL segura generada para idAudio: $idAudio url: $urlSegura");
    return $urlSegura;
}

# Bloquea el acceso directo a archivos en wp-content/uploads si no hay token válido.
function bloquear_acceso_directo_archivos()
{
    guardarLog("[bloquear_acceso_directo_archivos] inicio Verificando acceso a: {$_SERVER['REQUEST_URI']}");
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/') !== false) {
        guardarLog("[bloquear_acceso_directo_archivos] info Ruta de uploads detectada intentando validar token");
        $tokenRecibido = $_GET['token'] ?? $_COOKIE['audio_token'] ?? null;
        if (!$tokenRecibido) {
             guardarLog("[bloquear_acceso_directo_archivos] error Token no encontrado acceso denegado para URI: {$_SERVER['REQUEST_URI']}");
             wp_die('Acceso denegado', 'Acceso denegado', array('response' => 403));
        }

        guardarLog("[bloquear_acceso_directo_archivos] info Token encontrado procediendo a verificar para URI: {$_SERVER['REQUEST_URI']}");
        if (!verificarAudio($tokenRecibido)) {
            guardarLog("[bloquear_acceso_directo_archivos] error Token invalido acceso denegado para URI: {$_SERVER['REQUEST_URI']}");
            wp_die('Acceso denegado', 'Acceso denegado', array('response' => 403));
        }
         guardarLog("[bloquear_acceso_directo_archivos] exito Token valido acceso permitido para URI: {$_SERVER['REQUEST_URI']}");
    } else {
         guardarLog("[bloquear_acceso_directo_archivos] info Omitiendo no es ruta de uploads URI: {$_SERVER['REQUEST_URI']}");
    }
}
add_action('init', 'bloquear_acceso_directo_archivos');

# Decrementa los usos restantes de un token de audio si el caché de navegador está desactivado.
function decrementaUsosToken($idUnico)
{
    guardarLog("[decrementaUsosToken] inicio Intentando decrementar usos para idUnico: $idUnico");
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        guardarLog("[decrementaUsosToken] info Cache de navegador habilitado omitiendo decremento");
        return;
    }

    $claveTransitoria = 'audio_token_' . $idUnico;
    guardarLog("[decrementaUsosToken] info Obteniendo usos restantes para clave: $claveTransitoria");
    $usosRestantesActuales = get_transient($claveTransitoria);

    if ($usosRestantesActuales !== false && $usosRestantesActuales > 0) {
        guardarLog("[decrementaUsosToken] info Usos restantes actuales: $usosRestantesActuales");
        $usosRestantesActuales--;
        guardarLog("[decrementaUsosToken] info Decrementando usos a: $usosRestantesActuales");
        if ($usosRestantesActuales > 0) {
            guardarLog("[decrementaUsosToken] info Actualizando transient clave: $claveTransitoria nuevos usos: $usosRestantesActuales");
            set_transient($claveTransitoria, $usosRestantesActuales, get_option('transient_timeout_' . $claveTransitoria));
        } else {
            guardarLog("[decrementaUsosToken] info Eliminando transient clave: $claveTransitoria ultimo uso");
            delete_transient($claveTransitoria);
        }
    } else {
         guardarLog("[decrementaUsosToken] info No se encontraron usos restantes o ya expiraron para clave: $claveTransitoria");
    }
}


# Verifica la validez de un token de audio y aplica limitación de intentos.
function verificarAudio($token)
{
    $ipSolicitante = $_SERVER['REMOTE_ADDR'];
    guardarLog("[verificarAudio] inicio Verificando token (parcial): " . substr($token, 0, 10) . "... desde IP: $ipSolicitante");

    # Limitación de intentos por IP
    $limiteIntentos = 10;
    $ventanaTiempoSegundos = 10;
    $duracionBloqueoSegundos = 300;
    $claveTransitoriaIntentos = 'failed_attempts_' . $ipSolicitante;
    $numeroIntentos = get_transient($claveTransitoriaIntentos);
    if ($numeroIntentos === false) $numeroIntentos = 0;

    $claveTransitoriaBloqueo = 'blocked_ip_' . $ipSolicitante;
    $ipEstaBloqueada = get_transient($claveTransitoriaBloqueo);

    guardarLog("[verificarAudio] info Verificando limites de intentos para IP: $ipSolicitante intentos: $numeroIntentos bloqueado: " . ($ipEstaBloqueada !== false ? 'si' : 'no'));

    if ($ipEstaBloqueada !== false) {
        guardarLog("[verificarAudio] error IP bloqueada: $ipSolicitante");
        header("HTTP/1.1 429 Too Many Requests");
        return false;
    }

    if (empty($token)) {
        $numeroIntentos++;
        guardarLog("[verificarAudio] error Token vacio incrementando intentos fallidos para IP: $ipSolicitante a: $numeroIntentos");
        set_transient($claveTransitoriaIntentos, $numeroIntentos, $ventanaTiempoSegundos);
        if ($numeroIntentos >= $limiteIntentos) {
             guardarLog("[verificarAudio] error Limite de intentos alcanzado bloqueando IP: $ipSolicitante");
             set_transient($claveTransitoriaBloqueo, true, $duracionBloqueoSegundos);
        }
        header("HTTP/1.1 401 Unauthorized");
        return false;
    }

    # Verificaciones de Headers (simple anti-hotlinking/scraping)
     guardarLog("[verificarAudio] info Verificando Referer y X-Requested-With");
    if (!isset($_SERVER['HTTP_REFERER'])) {
         guardarLog("[verificarAudio] error Header Referer ausente");
         return false;
    }
     if(!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
         guardarLog("[verificarAudio] error Header X-Requested-With ausente");
         return false;
     }

    $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    guardarLog("[verificarAudio] info Verificando host del Referer: $refererHost");
    if ($refererHost !== '2upra.com') { // Asegúrate que este sea tu dominio real
        guardarLog("[verificarAudio] error Host del Referer invalido: $refererHost");
        return false;
    }

    guardarLog("[verificarAudio] info Verificando valor X-Requested-With: {$_SERVER['HTTP_X_REQUESTED_WITH']}");
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        guardarLog("[verificarAudio] error Valor X-Requested-With invalido: {$_SERVER['HTTP_X_REQUESTED_WITH']}");
        return false;
    }

    # Decodificar y validar token
    guardarLog("[verificarAudio] info Decodificando token");
    $tokenDecodificado = base64_decode($token);
    $partesToken = explode('|', $tokenDecodificado);
    if (count($partesToken) !== 6) {
        guardarLog("[verificarAudio] error Token con numero incorrecto de partes: " . count($partesToken));
        return false;
    }

    list($idAudio, $expiracion, $ipUsuario, $idUnico, $maxUsos, $firma) = $partesToken;
    guardarLog("[verificarAudio] info Partes del token idAudio: $idAudio expiracion: $expiracion ipUsuario: $ipUsuario idUnico: $idUnico maxUsos: $maxUsos");

    # Lógica principal según caché de navegador
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
         guardarLog("[verificarAudio] info Procesando logica de cache de navegador");
        $claveTransitoriaSesion = 'audio_session_' . $idAudio . '_' . $ipSolicitante;
        $claveTransitoriaCache = 'audio_access_' . $idAudio . '_' . $ipSolicitante;

        $datosParaFirma = $idAudio . '|' . $expiracion . '|' . $ipUsuario . '|' . $idUnico;
        guardarLog("[verificarAudio] info Calculando firma esperada (cache) con datos: $datosParaFirma");
        $firmaCalculada = hash_hmac('sha256', $datosParaFirma, $_ENV['AUDIOCLAVE']);

        if (!hash_equals($firmaCalculada, $firma)) {
            guardarLog("[verificarAudio] error Firma invalida (cache) esperada: $firmaCalculada recibida: $firma");
            return false;
        }
        guardarLog("[verificarAudio] info Firma valida (cache)");

        guardarLog("[verificarAudio] info Verificando transient de sesion clave: $claveTransitoriaSesion");
        $tokenSesionGuardado = get_transient($claveTransitoriaSesion);

        if ($tokenSesionGuardado === false) {
            guardarLog("[verificarAudio] info Transient de sesion no encontrado (primer acceso) estableciendo transients");
            set_transient($claveTransitoriaSesion, $token, 3600);
            set_transient($claveTransitoriaCache, 1, 3600);
            guardarLog("[verificarAudio] exito Token verificado correctamente (cache primer acceso)");
            return true;
        } else {
            guardarLog("[verificarAudio] info Transient de sesion encontrado valor (parcial): " . substr($tokenSesionGuardado, 0, 10) . "...");
             guardarLog("[verificarAudio] info Verificando transient de conteo de accesos clave: $claveTransitoriaCache");
            $numeroAccesosCache = get_transient($claveTransitoriaCache);

            if ($numeroAccesosCache !== false) {
                 guardarLog("[verificarAudio] info Conteo de accesos OK ($numeroAccesosCache) incrementando");
                 set_transient($claveTransitoriaCache, $numeroAccesosCache + 1, 3600);
                 guardarLog("[verificarAudio] exito Token verificado correctamente (cache acceso subsecuente)");
                 return true;
            } else {
                 guardarLog("[verificarAudio] error Conteo de accesos invalido o condiciones no cumplidas");
                 return false;
            }
        }
    } else {
        guardarLog("[verificarAudio] info Procesando logica sin cache de navegador");
        if ($ipSolicitante !== $ipUsuario) {
            guardarLog("[verificarAudio] error Discrepancia de IP solicitante: $ipSolicitante vs token: $ipUsuario");
            return false;
        }

        if (time() > $expiracion) {
            guardarLog("[verificarAudio] error Token expirado tiempo actual: " . time() . " expiracion: $expiracion");
            return false;
        }

        $claveUsoToken = 'audio_token_' . $idUnico;
        guardarLog("[verificarAudio] info Verificando usos restantes clave: $claveUsoToken");
        $usosRestantes = get_transient($claveUsoToken);
        if ($usosRestantes === false || $usosRestantes <= 0) {
            guardarLog("[verificarAudio] error No quedan usos o el transient expiro para clave: $claveUsoToken");
            return false;
        }
         guardarLog("[verificarAudio] info Usos restantes encontrados: $usosRestantes");

        $datosParaFirma = $idAudio . '|' . $expiracion . '|' . $ipUsuario . '|' . $idUnico;
         guardarLog("[verificarAudio] info Calculando firma esperada (sin cache) con datos: $datosParaFirma");
        $firmaCalculada = hash_hmac('sha256', $datosParaFirma, $_ENV['AUDIOCLAVE']);

        if (hash_equals($firmaCalculada, $firma)) {
             guardarLog("[verificarAudio] info Firma valida (sin cache) decrementando usos para idUnico: $idUnico");
            decrementaUsosToken($idUnico);
             guardarLog("[verificarAudio] exito Token verificado correctamente (sin cache)");
            return true;
        } else {
             guardarLog("[verificarAudio] error Firma invalida (sin cache) esperada: $firmaCalculada recibida: $firma");
             return false;
        }
    }

    // Este punto no deberia alcanzarse en teoria si la logica es correcta
    guardarLog("[verificarAudio] error Verificacion de token llego al final sin retornar true/false");
    return false;
}

# Genera un token de corta o larga duración para un ID de audio.
function tokenAudio($idAudio)
{
     guardarLog("[tokenAudio] inicio Generando token para idAudio: $idAudio");
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $idAudio)) {
         guardarLog("[tokenAudio] error ID de audio invalido formato incorrecto: $idAudio");
         return false;
    }

    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
         guardarLog("[tokenAudio] info Generando token con cache de navegador habilitado");
        $tiempoExpiracion = strtotime('2030-12-31'); # Expiracion larga para cache
        $ipUsuarioToken = 'cached'; # IP no relevante para cache
        $identificadorUnico = md5($idAudio . $_ENV['AUDIOCLAVE']); # ID unico basado en audio y clave secreta
        $maximoUsos = 999999; # Usos "ilimitados" para cache

        $datosParaFirma = $idAudio . '|' . $tiempoExpiracion . '|' . $ipUsuarioToken . '|' . $identificadorUnico;
        guardarLog("[tokenAudio] info Preparando datos para token cache idAudio: $idAudio expiracion: $tiempoExpiracion ip: $ipUsuarioToken idUnico: $identificadorUnico");
        guardarLog("[tokenAudio] info Calculando firma para token cache");
        $firmaCalculada = hash_hmac('sha256', $datosParaFirma, $_ENV['AUDIOCLAVE']);
        $tokenGenerado = base64_encode($datosParaFirma . '|' . $maximoUsos . '|' . $firmaCalculada);

        guardarLog("[tokenAudio] exito Token cache generado (parcial): " . substr($tokenGenerado, 0, 20) . "...");
        return $tokenGenerado;
    } else {
        guardarLog("[tokenAudio] info Generando token sin cache de navegador");
        $tiempoExpiracion = time() + 3600; # 1 hora de validez
        $ipUsuarioToken = $_SERVER['REMOTE_ADDR'];
        $identificadorUnico = uniqid('', true); # ID unico para este token especifico
        $maximoUsos = 3; # Limite de usos

        $datosParaFirma = $idAudio . '|' . $tiempoExpiracion . '|' . $ipUsuarioToken . '|' . $identificadorUnico;
        guardarLog("[tokenAudio] info Preparando datos para token no-cache idAudio: $idAudio expiracion: $tiempoExpiracion ip: $ipUsuarioToken idUnico: $identificadorUnico maxUsos: $maximoUsos");
        guardarLog("[tokenAudio] info Calculando firma para token no-cache");
        $firmaCalculada = hash_hmac('sha256', $datosParaFirma, $_ENV['AUDIOCLAVE']);
        $tokenGenerado = base64_encode($datosParaFirma . '|' . $maximoUsos . '|' . $firmaCalculada);

        $claveTransitoriaToken = 'audio_token_' . $identificadorUnico;
        guardarLog("[tokenAudio] info Guardando transient $claveTransitoriaToken con maxUsos: $maximoUsos duracion: 3600");
        set_transient($claveTransitoriaToken, $maximoUsos, 3600);

        guardarLog("[tokenAudio] exito Token no-cache generado (parcial): " . substr($tokenGenerado, 0, 20) . "...");
        return $tokenGenerado;
    }
}



?>