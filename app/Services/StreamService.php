<?
define('ENABLE_BROWSER_AUDIO_CACHE', TRUE);
// Añade esto al inicio de tu archivo
add_action('init', function () {
    if (!defined('DOING_AJAX') && !defined('REST_REQUEST')) {
        return;
    }
    $user_id = wp_validate_auth_cookie('', 'logged_in');
    if ($user_id) {
        wp_set_current_user($user_id);
    }
});

function audioUrlSegura($audio_id)
{
    //
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

/*
el ataque se recibio aca, como se hace para bloquear automaticamente solicitudes repetidas de esta forma 
172.70.254.90 - - [18/Dec/2024:01:03:18 +0100] "GET /wp-json/1/v1/2?token=dasodnsaodnoasdoasnodnasodnoasndoasdmkasmdoasmdqwei0uohfnwriojfnwerio HTTP/1.1" 401 116 "-" "python-requests/2.32.3"
*/

add_action('rest_api_init', function () {
    //guardarLog('Registrando rutas REST API');

    // Ruta para usuarios pro
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

    // Ruta para usuarios normales
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

    //guardarLog('Rutas REST API registradas');
});

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






function audioStreamEnd($data)
{
    if (ob_get_level()) ob_end_clean();
    $token = $data['token'];
    $parts = explode('|', base64_decode($token));
    $audio_id = $parts[0];

    // Directorio de caché
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/audio_cache';
    if (!file_exists($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }

    $cache_file = $cache_dir . '/audio_' . $audio_id . '.cache';

    if (file_exists($cache_file) && (time() - filemtime($cache_file) < 24 * 60 * 60)) {
        //guardarLog("audioStreamEnd: Cargando audio desde el archivo en caché: $cache_file");
        $file = $cache_file;
    } else {
        $original_file = get_attached_file($audio_id);
        if (!file_exists($original_file)) {
            //guardarLog("audioStreamEnd: Error - Archivo de audio original no encontrado para el audio ID: $audio_id");
            return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
        }
        if (!@copy($original_file, $cache_file)) {
            //guardarLog("audioStreamEnd: Error - Fallo al copiar el archivo de audio al caché.");
            return new WP_Error('copy_failed', 'Error al copiar el archivo de audio al caché.', array('status' => 500));
        }

        //guardarLog("audioStreamEnd: Archivo de audio copiado exitosamente al caché: $cache_file");
        $file = $cache_file;
    }

    $fp = @fopen($file, 'rb');
    if (!$fp) {
        //guardarLog("audioStreamEnd: Error - No se pudo abrir el archivo de audio: $file");
        return new WP_Error('file_open_error', 'No se pudo abrir el archivo de audio.', array('status' => 500));
    }

    $size = filesize($file);
    $length = $size;
    $start = 0;
    $end = $size - 1;

    // Generar ETag único para el archivo
    $etag = '"' . md5($file . filemtime($file)) . '"';

    // Headers básicos
    header('Content-Type: ' . get_post_mime_type($audio_id));
    header('Accept-Ranges: bytes');

    // Configurar headers de caché según la constante
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        $cache_time = 60 * 60 * 2190; // 24 horas
        header('Cache-Control: public, max-age=' . $cache_time);
        header('Pragma: public');
        header('ETag: ' . $etag);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');

        // Verificar si el contenido no ha cambiado
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
    } else {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }


    // Manejar Ranges HTTP para streaming parcial
    if (isset($_SERVER['HTTP_RANGE'])) {
        //guardarLog("audioStreamEnd: HTTP Range solicitado: " . $_SERVER['HTTP_RANGE']);
        $c_start = $start;
        $c_end = $end;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            //guardarLog("audioStreamEnd: Error - Rango solicitado no soportado.");
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            exit;
        }
        if ($range == '-') {
            $c_start = $size - substr($range, 1);
        } else {
            $range = explode('-', $range);
            $c_start = $range[0];
            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
        }
        $c_end = ($c_end > $end) ? $end : $c_end;
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
            //guardarLog("audioStreamEnd: Error - Rango solicitado fuera de los límites.");
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            exit;
        }
        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1;
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
    }

    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: " . $length);

    // Streaming con rate limiting
    $buffer = 1024 * 8; // 8 KB
    $sleep = 1000; // Microsegundos de espera entre cada envío de buffer
    $sent = 0;
    while (!feof($fp) && ($p = ftell($fp)) <= $end) {
        if ($p + $buffer > $end) {
            $buffer = $end - $p + 1;
        }
        echo fread($fp, $buffer);
        $sent += $buffer;
        flush();
        if ($sent >= 64 * 1024) { // Cada 64 KB
            usleep($sleep);
            $sent = 0;
        }
    }

    //guardarLog("audioStreamEnd: Transmisión del audio completada para el archivo: $file");
    fclose($fp);
    exit();
}



// Registra el cron job
add_action('wp', 'schedule_audio_cache_cleanup');

function schedule_audio_cache_cleanup()
{
    if (!wp_next_scheduled('audio_cache_cleanup')) {
        wp_schedule_event(time(), 'daily', 'audio_cache_cleanup');
    }
}

// Función para limpiar el caché
add_action('audio_cache_cleanup', 'clean_audio_cache');

function clean_audio_cache()
{
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/audio_cache';

    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) > 7 * 24 * 60 * 60)) {
                unlink($file);
            }
        }
    }
}

// Desprogramar el cron job cuando el plugin se desactiva
function unschedule_audio_cache_cleanup()
{
    $timestamp = wp_next_scheduled('audio_cache_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'audio_cache_cleanup');
    }
}
register_deactivation_hook(__FILE__, 'unschedule_audio_cache_cleanup');

