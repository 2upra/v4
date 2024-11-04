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
    $user_id = get_current_user_id();
    if (usuarioEsAdminOPro($user_id)) {
        return site_url("/wp-json/1/v1/audio-pro/{$audio_id}");
    }
    $token = tokenAudio($audio_id);
    if (!$token) {
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }
    $nonce = wp_create_nonce('wp_rest');
    return site_url("/wp-json/1/v1/2?token=" . urlencode($token) . '&_wpnonce=' . $nonce);
}

function bloquear_acceso_directo_archivos()
{
    if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/') !== false) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, home_url()) === false) {
            wp_die('Acceso denegado: no puedes descargar este archivo directamente.', 'Acceso denegado', array('response' => 403));
        }
    }
}
add_action('init', 'bloquear_acceso_directo_archivos');


add_action('rest_api_init', function () {
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

    // Endpoint original para usuarios normales
    register_rest_route('1/v1', '/2', array(
        'methods' => 'GET',
        'callback' => 'audioStreamEnd',
        'args' => array(
            'token' => array(
                'required' => true,
            ),
        ),
        'permission_callback' => function ($request) {
            return verificarAudio($request->get_param('token'));
        }
    ));
});

function tokenAudio($audio_id)
{
    guardarLog("Generando token para audio_id: $audio_id");

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        guardarLog("Error: audio_id inválido: $audio_id");
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

        guardarLog("Token generado exitosamente: $token");
        return $token;
    }
}

/*
Cuando la cache esta desactivida, ok, no se cachea, y esta bien, y tampoco permite acceder al enlace de los audios directamente para evitar la descarga, excelente, pero cuando la cache esta activida, si se cachea pero deja acceder directamente al enlace (algo peculiar es que si accede directamente la cache del audio no vuelve a cargar y se genera otro token al parecer, y si vuelvo acceder al mismo enlace ahora si evita el acceso), pero repito, cuando se cachea debería impedir el acceso directo al enlace, hay forma de hacer eso? 

https://2upra.com/wp-json/1/v1/2?token=Mjg2MzIxfDE5MjQ5MDU2MDB8Y2FjaGVkfDE3MzI2MjRiNzlmMGVhNTVmMzIwOTgxMTY0MzNkYWZhfDk5OTk5OXwzNjgzZDFlM2Y2YzU3YWVlNDAzMzRjNjhmYmI0NjI4ZThmOWE5MDU3YTBhNzQzNmRkNzcwMTZlZDgxZDYzYWZm&_wpnonce=5877b925e6

por cierto tengo esta configuracion 

    location /wp-content/uploads/ {
        valid_referers 2upra.com *.2upra.com;

        if ($invalid_referer) {
            return 403;
        }
        try_files $uri $uri/ =404;
    }

    location ~* \.(mp3|wav)$ {
        deny all;
        return 403;
    }

    location /wp-json/ {
        add_header 'Access-Control-Allow-Origin' 'https://2upra.com';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Conte>

        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' 'https://2upra.com';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,C>
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain; charset=utf-8';
            add_header 'Content-Length' 0;
            return 204;
        }

        if ($allow = 0) {
            return 403;
        }

        try_files $uri $uri/ /index.php?$args;
    }
*/

function verificarAudio($token)
{
    guardarLog("Verificando token: $token");

    if (empty($token)) {
        guardarLog("Error: token vacío");
        return false;
    }

    // Verificar referer primero, independientemente del modo de caché
    if (!isset($_SERVER['HTTP_REFERER'])) {
        guardarLog("Error: HTTP_REFERER no establecido");
        return false;
    }

    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    guardarLog("Referer host: $referer_host");
    
    if ($referer_host !== '2upra.com') {
        guardarLog("Error: referer no válido");
        return false;
    }

    $decoded = base64_decode($token);
    $parts = explode('|', $decoded);
    if (count($parts) !== 6) {
        guardarLog("Error: número incorrecto de partes en el token");
        return false;
    }

    list($audio_id, $expiration, $user_ip, $unique_id, $max_usos, $signature) = $parts;

    // Verificación básica del audio_id para ambos modos
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        guardarLog("Error: audio_id inválido");
        return false;
    }

    // Si el cacheo del navegador está activado
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        // Verificar que el unique_id corresponde al esperado
        $expected_unique_id = md5($audio_id . $_ENV['AUDIOCLAVE']);
        if ($unique_id !== $expected_unique_id) {
            guardarLog("Error: unique_id no válido");
            return false;
        }

        // Verificar la firma
        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $expected_signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (!hash_equals($expected_signature, $signature)) {
            guardarLog("Error: firma no válida");
            return false;
        }

        // Verificar si es la primera solicitud para este audio desde esta IP
        $cache_key = 'audio_access_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];
        $previous_access = get_transient($cache_key);
        
        if ($previous_access === false) {
            // Primera solicitud, almacenar en caché
            set_transient($cache_key, time(), 3600); // 1 hora de caché
            return true;
        } else {
            // Verificar si la solicitud viene del mismo referer
            if (isset($_SERVER['HTTP_REFERER']) && 
                parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) === '2upra.com') {
                return true;
            }
            guardarLog("Error: acceso directo detectado");
            return false;
        }
    } else {
        // Comportamiento original para modo sin caché
        if ($_SERVER['REMOTE_ADDR'] !== $user_ip) {
            guardarLog("Error: IP no coincide");
            return false;
        }

        if (time() > $expiration) {
            guardarLog("Error: token expirado");
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

function decrementaUsosToken($unique_id)
{
    // Solo decrementar si el cacheo del navegador está desactivado
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        return; // No decrementar para tokens cacheados
    }

    guardarLog("Decrementando usos del token: $unique_id");
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
        guardarLog("audioStreamEnd: Cargando audio desde el archivo en caché: $cache_file");
        $file = $cache_file;
    } else {
        $original_file = get_attached_file($audio_id);
        if (!file_exists($original_file)) {
            guardarLog("audioStreamEnd: Error - Archivo de audio original no encontrado para el audio ID: $audio_id");
            return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
        }
        if (!@copy($original_file, $cache_file)) {
            guardarLog("audioStreamEnd: Error - Fallo al copiar el archivo de audio al caché.");
            return new WP_Error('copy_failed', 'Error al copiar el archivo de audio al caché.', array('status' => 500));
        }

        guardarLog("audioStreamEnd: Archivo de audio copiado exitosamente al caché: $cache_file");
        $file = $cache_file;
    }

    $fp = @fopen($file, 'rb');
    if (!$fp) {
        guardarLog("audioStreamEnd: Error - No se pudo abrir el archivo de audio: $file");
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
        guardarLog("audioStreamEnd: HTTP Range solicitado: " . $_SERVER['HTTP_RANGE']);
        $c_start = $start;
        $c_end = $end;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            guardarLog("audioStreamEnd: Error - Rango solicitado no soportado.");
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
            guardarLog("audioStreamEnd: Error - Rango solicitado fuera de los límites.");
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

    guardarLog("audioStreamEnd: Transmisión del audio completada para el archivo: $file");
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

function usuarioEsAdminOPro($user_id)
{
    // Verificar que el ID de usuario sea válido
    if (empty($user_id) || !is_numeric($user_id)) {
        guardarLog("usuarioEsAdminOPro: Error - ID de usuario inválido.");
        return false;
    }

    // Obtener el objeto usuario
    $user = get_user_by('id', $user_id);

    // Verificar si el usuario existe
    if (!$user) {
        guardarLog("usuarioEsAdminOPro: Error - Usuario no encontrado para el ID: " . $user_id);
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($user->roles)) {
        guardarLog("usuarioEsAdminOPro: Error - Usuario sin roles asignados. ID: " . $user_id);
        guardarLog("usuarioEsAdminOPro: Información del usuario - " . print_r($user, true));
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $user->roles)) {
        guardarLog("usuarioEsAdminOPro: Usuario es administrador. ID: " . $user_id);
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($user_id, 'pro', true);
    if (!empty($is_pro)) {
        guardarLog("usuarioEsAdminOPro: Usuario tiene la meta 'pro'. ID: " . $user_id);
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    guardarLog("usuarioEsAdminOPro: Usuario no es administrador ni tiene la meta 'pro'. ID: " . $user_id);
    return false;
}
