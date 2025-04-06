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
    } else {
        header('Content-Length: ' . $size);
        fpassthru($fp);
    }
    
    fclose($fp);
    exit;
}

// Refactor(Org): Moved streaming functions from app/Functions/stream.php
function audioUrlSegura($audio_id)
{
    //guardarLog("Generando URL segura para audio ID: " . $audio_id);
    /*
    $user_id = get_current_user_id();
    if (usuarioEsAdminOPro($user_id)) {
        $url = site_url("/wp-json/1/v1/audio-pro/{$audio_id}");
        //guardarLog("URL generada para admin/pro: " . $url);
        return $url;
    }
    */
    $token = tokenAudio($audio_id);
    if (!$token) {
        //guardarLog("Error generando token para audio ID: " . $audio_id);
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }

    $nonce = wp_create_nonce('wp_rest');
    $url = site_url("/wp-json/1/v1/2?token=" . urlencode($token) . '&_wpnonce=' . $nonce);
    //guardarLog("URL generada para usuario normal: " . $url);
    return $url;
}

function bloquear_acceso_directo_archivos()
{
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/') !== false) {
        // Check for a valid token, maybe in a query parameter or cookie
        $token = $_GET['token'] ?? $_COOKIE['audio_token'] ?? null;
        if (!$token || !verificarAudio($token)) {
            wp_die('Acceso denegado', 'Acceso denegado', array('response' => 403));
        }
    }
}
add_action('init', 'bloquear_acceso_directo_archivos');

function decrementaUsosToken($unique_id)
{
    // Solo decrementar si el cacheo del navegador está desactivado
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        return; // No decrementar para tokens cacheados
    }

    //guardarLog("Decrementando usos del token: $unique_id");
    $key = 'audio_token_' . $unique_id;
    $usos_restantes = get_transient($key);

    if ($usos_restantes !== false && $usos_restantes > 0) {
        $usos_restantes--;
        if ($usos_restantes > 0) {
            set_transient($key, $usos_restantes, get_option('transient_timeout_' . $key));
        } else {
            delete_transient($key);
        }
    }
}

function verificarAudio($token)
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $limit = 10; // Número máximo de intentos fallidos en un período de tiempo
    $time_window = 10; // Período de tiempo en segundos (60 segundos = 1 minuto)
    $block_duration = 300; // Tiempo de bloqueo en segundos (300 segundos = 5 minutos)

    // Obtener el contador de intentos fallidos para la IP actual
    $attempts_key = 'failed_attempts_' . $ip;
    $attempts = get_transient($attempts_key);

    if ($attempts === false) {
        $attempts = 0;
    }

    // Comprobar si la IP está bloqueada
    $blocked_key = 'blocked_ip_' . $ip;
    $is_blocked = get_transient($blocked_key);

    if ($is_blocked !== false) {
        //guardarLog("Error: IP bloqueada temporalmente debido a múltiples intentos fallidos");
        header("HTTP/1.1 429 Too Many Requests");
        return false;
    }

    if (empty($token)) {
        // Incrementar el contador de intentos fallidos
        $attempts++;
        set_transient($attempts_key, $attempts, $time_window);

        // Bloquear la IP si se excede el límite
        if ($attempts >= $limit) {
            set_transient($blocked_key, true, $block_duration);
            //guardarLog("Bloqueando IP: " . $ip . " por " . $block_duration . " segundos");
        }
        header("HTTP/1.1 401 Unauthorized");
        return false;
    }

    // Verificar referer y headers
    if (!isset($_SERVER['HTTP_REFERER']) || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        //guardarLog("Error: Faltan headers requeridos");
        return false;
    }

    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($referer_host !== '2upra.com') {
        //guardarLog("Error: referer no válido");
        return false;
    }

    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        //guardarLog("Error: No es una petición AJAX");
        return false;
    }

    $decoded = base64_decode($token);
    $parts = explode('|', $decoded);
    if (count($parts) !== 6) {
        //guardarLog("Error: número incorrecto de partes en el token");
        return false;
    }
    //guardarLog("Partes del token: " . print_r($parts, true));

    list($audio_id, $expiration, $user_ip, $unique_id, $max_usos, $signature) = $parts;

    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        // Generar una clave única para esta sesión y audio
        $session_key = 'audio_session_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];
        $cache_key = 'audio_access_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];

        // Verificar la firma
        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $expected_signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (!hash_equals($expected_signature, $signature)) {
            //guardarLog("Error: firma no válida");
            return false;
        }

        // Verificar sesión actual
        $current_session = get_transient($session_key);

        if ($current_session === false) {
            // Primera solicitud en esta sesión
            set_transient($session_key, $token, 3600); // 1 hora
            set_transient($cache_key, 1, 3600); // Contador de accesos
            return true;
        } else {
            // Verificar si es una solicitud válida desde la página
            $access_count = get_transient($cache_key);

            if (
                $access_count !== false &&
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' &&
                strpos($_SERVER['HTTP_REFERER'], '2upra.com') !== false
            ) {

                set_transient($cache_key, $access_count + 1, 3600);
                return true;
            }

            //guardarLog("Error: acceso directo o no autorizado");
            return false;
        }
    } else {
        // Comportamiento original para modo sin caché
        if ($_SERVER['REMOTE_ADDR'] !== $user_ip) {
            //guardarLog("Error: IP no coincide");
            return false;
        }

        if (time() > $expiration) {
            //guardarLog("Error: token expirado");
            return false;
        }

        $usos_restantes = get_transient('audio_token_' . $unique_id);
        if ($usos_restantes === false || $usos_restantes <= 0) {
            return false;
        }

        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $expected_signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (hash_equals($expected_signature, $signature)) {
            decrementaUsosToken($unique_id);
            return true;
        }

        return false;
    }
}

// Refactor(Org): Función tokenAudio() movida desde app/Functions/stream.php
function tokenAudio($audio_id)
{
    //guardarLog("Generando token para audio_id: $audio_id");

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        //guardarLog("Error: audio_id inválido: $audio_id");
        return false;
    }

    // Si el cacheo del navegador está activado, generamos un token más persistente
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        // Usar una fecha fija para que el token sea consistente
        $expiration = strtotime('2030-12-31'); // Fecha lejana fija
        $user_ip = 'cached'; // No usamos IP para permitir el cacheo
        $unique_id = md5($audio_id . $_ENV['AUDIOCLAVE']); // ID único pero consistente
        $max_usos = 999999; // Número alto de usos

        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);
        $token = base64_encode($data . '|' . $max_usos . '|' . $signature);

        // No necesitamos almacenar el contador de usos para tokens cacheados
        return $token;
    } else {
        // Comportamiento original para cuando el cacheo está desactivado
        $expiration = time() + 3600;
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $unique_id = uniqid('', true);
        $max_usos = 3;

        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);
        $token = base64_encode($data . '|' . $max_usos . '|' . $signature);

        set_transient('audio_token_' . $unique_id, $max_usos, 3600);

        //guardarLog("Token generado exitosamente: $token");
        return $token;
    }
}
