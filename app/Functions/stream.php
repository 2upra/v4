<?

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

function proteger_endpoints()
{
    add_filter('rest_pre_dispatch', function ($result, $server, $request) {
        // Verificar origen de la petición
        if (
            !isset($_SERVER['HTTP_REFERER']) ||
            !preg_match('/^https?:\/\/(.*\.)?2upra\.com/', $_SERVER['HTTP_REFERER'])
        ) {
            return new WP_Error('unauthorized', 'Acceso no autorizado', array('status' => 403));
        }

        // Verificar rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        $rate_key = 'rate_limit_' . $ip;
        $rate_count = get_transient($rate_key);

        if ($rate_count === false) {
            set_transient($rate_key, 1, 60); // 1 minuto
        } else if ($rate_count > 10) { // máximo 10 peticiones por minuto
            return new WP_Error('rate_limit', 'Demasiadas peticiones', array('status' => 429));
        } else {
            set_transient($rate_key, $rate_count + 1, 60);
        }

        return $result;
    }, 10, 3);
}
add_action('init', 'proteger_endpoints');

//HE HECHO TODO LOS POSIBLE PARA EVITAR LAS DESCARGAS DIRECTAS; PERO NADA FUNCIONA; SE PUEDE IR A LOS ARCHIVOS DIRECTAMENTE Y DESCARGARLOS; COSA QUE NO QUIERO PORQUE TENGO MI PROPIO SISTEMA DE TOKEN QUE LOS SIRVE CUANDO SE QUIEREN DESCARGAR Y NO FUNCIONA ESTO BLOQUEAR EL ACCESO A LOS WAV Y MP3, Y LOS /wp-json/1/v1/2 ; O SEA LO UNICO QUE QUIERO ES QUE NO SE ACCEDA DIRECTAMAENTE A LOS ENLACES DE DESCARGA Y EL AUDIO QUE SE SIRVE EN LA API
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


/*
    #WP
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location /wp-content/uploads/audios/ {
        internal;
    }

    location /wp-content/uploads/ {
        valid_referers 2upra.com *.2upra.com;

        if ($invalid_referer) {
            return 403;
        }
        try_files $uri $uri/ =404;
    }

    despues de estos cambios, la api ya no funciona, como hago que funcione manteniendo el bloqueo directo
    location ~* \.(mp3|wav)$ {
        deny all;
        return 403;
    }

    # Bloquear acceso directo a la API excepto desde el dominio permitido
    location /wp-json/ {
        if ($http_referer !~ ^https?://([^/]+\.)?2upra\.com) {
        return 403;
    }
        try_files $uri $uri/ /index.php?$args;
    }
*/

// Registrar dos endpoints: uno para usuarios pro/admin y otro para usuarios normales
add_action('rest_api_init', function () {
    // Endpoint para usuarios pro/admin
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
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        return false;
    }

    $expiration = time() + 3600; // Expira en 1 hora
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $unique_id = uniqid('', true);
    $max_usos = 2; // Número máximo de usos permitidos
    $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id . '|' . $max_usos;
    $signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);
    return base64_encode($data . '|' . $signature);
}

// Función para verificar el token
function verificarAudio($token)
{
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

    if (!isset($_SERVER['HTTP_REFERER']) || !parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) === '2upra.com') {
        return false;
    }

    $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
    $expected_signature = hash_hmac('sha256', $data, ($_ENV['AUDIOCLAVE']));

    if (hash_equals($expected_signature, $signature)) {
        marcarTokenComoUsado($unique_id);
        return true;
    }
    return false;
}

function marcarTokenComoUsado($unique_id)
{
    set_transient('audio_token_' . $unique_id, true, 3600);
}

function tokenYaUsado($unique_id)
{
    return get_transient('audio_token_' . $unique_id) !== false;
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

    // Asegurarse de que no haya output antes de los headers

    // Modificar los headers para streaming
    header('Content-Type: ' . get_post_mime_type($audio_id));
    header('Accept-Ranges: bytes');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');


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
