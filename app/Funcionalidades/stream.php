<?

/*

en la ventana de red veo que las solicitudes asi ninguna se cachea

URL de la solicitud:
https://2upra.com/wp-json/1/v1/2?token=MjMyMDI3fDE3MjczMjM0NTd8MTkwLjIwNy4xMjEuMTMzfDY2ZjRkZDA1NTE4OTd8ZDdjM2NlODBkM2ViMDczNTljZmIxZjczNmI3MGMxODE4MDVlMzA4ZDM4Zjg5NGVkMGZmYmQ1ZTI5NWFlZDY3Yw%3D%3D
Método de solicitud:
GET
Código de estado:
200 OK
Dirección remota:
Directiva de sitio de referencia:
strict-origin-when-cross-origin
accept-ranges:
bytes
access-control-allow-headers:
Authorization, X-WP-Nonce, Content-Disposition, Content-MD5, Content-Type
access-control-expose-headers:
X-WP-Total, X-WP-TotalPages, Link
cache-control:
no-store, no-cache, must-revalidate, max-age=0
cache-control:
post-check=0, pre-check=0
connection:
keep-alive
content-length:
366594
content-range:
bytes 0-366593/366594
content-type:
audio/mpeg
date:
Thu, 26 Sep 2024 04:03:20 GMT
link:
<https://2upra.com/wp-json/>; rel="https://api.w.org/"
pragma:
no-cache
server:
nginx
x-content-type-options:
nosniff
x-robots-tag:
noindex

necesito que se cheeen los audios sin que pierdan su estricta seguridad 
*/

// Función para obtener la URL segura del audio
function tokenAudio($audio_id) {
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        return false;
    }

    $expiration = time() + 3600; 
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $unique_id = uniqid();
    $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
    $signature = hash_hmac('sha256', $data, ($_ENV['AUDIOCLAVE']));
    return base64_encode($data . '|' . $signature);
}

// Función para verificar el token
function verificarAudio($token) {
    $parts = explode('|', base64_decode($token));
    if (count($parts) !== 5) return false; // Asegurarse de que haya 5 partes
    list($audio_id, $expiration, $user_ip, $unique_id, $signature) = $parts;

    if ($_SERVER['REMOTE_ADDR'] !== $user_ip) {
        return false;
    }
    if (time() > $expiration) return false;
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        return false;
    }
    if (tokenYaUsado($unique_id)) return false;

    $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
    $expected_signature = hash_hmac('sha256', $data, ($_ENV['AUDIOCLAVE']));
    
    if (hash_equals($expected_signature, $signature)) {
        marcarTokenComoUsado($unique_id);
        return true;
    }
    return false;
}

function marcarTokenComoUsado($unique_id) {
    set_transient('audio_token_' . $unique_id, true, 3600);
}

function tokenYaUsado($unique_id) {
    return get_transient('audio_token_' . $unique_id) !== false;
}

// Función para obtener la URL segura del audio
function audioUrlSegura($audio_id) {
    $token = tokenAudio($audio_id);
    if (!$token) {
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }
    return site_url("/wp-json/1/v1/2?token=" . urlencode($token));
}

// Registrar el endpoint REST
add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/2', array(
        'methods' => 'GET',
        'callback' => 'audioStreamEnd',
        'args' => array(
            'token' => array(
                'required' => true,
            ),
        ),
        'permission_callback' => function($request) {
            return verificarAudio($request->get_param('token'));
        }
    ));
});


// Modificar la función audioStreamEnd para implementar streaming
function audioStreamEnd($data) {
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
        $file = $cache_file;
    } else {
        $original_file = get_attached_file($audio_id);
        if (!file_exists($original_file)) {
            return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
        }
        if (!@copy($original_file, $cache_file)) {
            return new WP_Error('copy_failed', 'Error al copiar el archivo de audio al caché.', array('status' => 500));
        }

        $file = $cache_file;
    }

    $fp = @fopen($file, 'rb');
    if (!$fp) {
        return new WP_Error('file_open_error', 'No se pudo abrir el archivo de audio.', array('status' => 500));
    }

    $size = filesize($file);
    $length = $size;
    $start = 0;
    $end = $size - 1;

    header('Content-Type: ' . get_post_mime_type($audio_id));
    header("Accept-Ranges: bytes");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // Manejar Ranges HTTP para streaming parcial
    if (isset($_SERVER['HTTP_RANGE'])) {
        $c_start = $start;
        $c_end = $end;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
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

    fclose($fp);
    exit();
}


// Registra el cron job
add_action('wp', 'schedule_audio_cache_cleanup');

function schedule_audio_cache_cleanup() {
    if (!wp_next_scheduled('audio_cache_cleanup')) {
        wp_schedule_event(time(), 'daily', 'audio_cache_cleanup');
    }
}

// Función para limpiar el caché
add_action('audio_cache_cleanup', 'clean_audio_cache');

function clean_audio_cache() {
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
function unschedule_audio_cache_cleanup() {
    $timestamp = wp_next_scheduled('audio_cache_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'audio_cache_cleanup');
    }
}
register_deactivation_hook(__FILE__, 'unschedule_audio_cache_cleanup');



