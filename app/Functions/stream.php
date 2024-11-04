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
    guardarLog("Generando URL segura para audio ID: " . $audio_id);

    $user_id = get_current_user_id();
    if (usuarioEsAdminOPro($user_id)) {
        $url = site_url("/wp-json/1/v1/audio-pro/{$audio_id}");
        guardarLog("URL generada para admin/pro: " . $url);
        return $url;
    }

    $token = tokenAudio($audio_id);
    if (!$token) {
        guardarLog("Error generando token para audio ID: " . $audio_id);
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }

    $nonce = wp_create_nonce('wp_rest');
    $url = site_url("/wp-json/1/v1/2?token=" . urlencode($token) . '&_wpnonce=' . $nonce);
    guardarLog("URL generada para usuario normal: " . $url);
    return $url;
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

add_action('rest_api_init', function () {
    guardarLog('Registrando rutas REST API');

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
            guardarLog('Verificando permiso para token: ' . $request->get_param('token'));
            return verificarAudio($request->get_param('token'));
        }
    ));

    guardarLog('Rutas REST API registradas');
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

estoy haciendo todo lo posible para que se puedan cachear los audios en el navegador y manteniendo la protección para no se accedan directamente al enlace, pero no se cachean cuando define('ENABLE_BROWSER_AUDIO_CACHE', TRUE);

los audios se piden asi

        fetch(audioUrl, {
            method: 'GET',
            credentials: 'same-origin', // Cambiado de 'include' a 'same-origin'
            headers: {
                'X-WP-Nonce': audioSettings.nonce,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'audio/mpeg,audio/*;q=0.9,*/*;q=0.8',
                'Cache-Control': 'no-cache',
                Pragma: 'no-cache'
            }
        })

    # Bloquear acceso directo a endpoints de audio # 
    location ~ /wp-json/1/v1/2 {
    # Permitir OPTIONS para CORS
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' 'https://2upra.com';
            add_header 'Access-Control-Allow-Methods' 'GET, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'X-Requested-With, X-WP-Nonce';
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain charset=UTF-8';
            add_header 'Content-Length' 0;
            return 204;
        }

        # Verificar que sea una petici      n AJAX y el referer correcto
        set $allow 1;

        if ($http_x_requested_with != "XMLHttpRequest") {
            set $allow 0;
        }

        if ($http_referer !~ ^https?://2upra\.com/) {
            set $allow 0;
        }

        # Si no cumple las condiciones, devolver 403
        if ($allow = 0) {
            return 403;
        }

        # Headers CORS para peticiones v      lidas
        add_header 'Access-Control-Allow-Origin' 'https://2upra.com' always;
        add_header 'Access-Control-Allow-Methods' 'GET, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'X-Requested-With, X-WP-Nonce' always;

        # Procesar la petici      n
        try_files $uri $uri/ /index.php?$args;
    }

    2024-11-04 21:07:50 - URL generada para usuario normal: https://2upra.com/wp-json/1/v1/2?token=Mjc5NDMzfDE5MjQ5MDU2MDB8Y2FjaGVkfDAyMTU5ODUyYjE4MzdmMzE3OWZmMGIzOWIxOWUwNzg4fDk5OTk5OXxmMzE4YjE0YTc2ZDY2NDk0ZDlmNjdmODcxNjNjYmRhY2UxYzUyNjJkMzEzNDgyNGJlNDk3NjhhZmU1ODlkMTNl&_wpnonce=5877b925e6
    2024-11-04 21:07:57 - Registrando rutas REST API
    2024-11-04 21:07:57 - Rutas REST API registradas
    2024-11-04 21:07:57 - Verificando permiso para token: Mjg3MjI2fDE5MjQ5MDU2MDB8Y2FjaGVkfDFjYzU5ZWEwMzYzNzU5YThhYWVmOGEwMjQ4YTFmZmYzfDk5OTk5OXwzZTczMWZjOGYzYTYzM2UxNTU3OWI4YmE3Y2JkOWEwNjlhM2U4MmVlNmJiODBiNTg4YmEzN2QzZmRiZjdlOGU5
    2024-11-04 21:07:57 - Verificando token: Mjg3MjI2fDE5MjQ5MDU2MDB8Y2FjaGVkfDFjYzU5ZWEwMzYzNzU5YThhYWVmOGEwMjQ4YTFmZmYzfDk5OTk5OXwzZTczMWZjOGYzYTYzM2UxNTU3OWI4YmE3Y2JkOWEwNjlhM2U4MmVlNmJiODBiNTg4YmEzN2QzZmRiZjdlOGU5
    2024-11-04 21:07:57 - Iniciando verificación de audio
    2024-11-04 21:07:57 - Token recibido: Mjg3MjI2fDE5MjQ5MDU2MDB8Y2FjaGVkfDFjYzU5ZWEwMzYzNzU5YThhYWVmOGEwMjQ4YTFmZmYzfDk5OTk5OXwzZTczMWZjOGYzYTYzM2UxNTU3OWI4YmE3Y2JkOWEwNjlhM2U4MmVlNmJiODBiNTg4YmEzN2QzZmRiZjdlOGU5
    2024-11-04 21:07:57 - Headers recibidos: Array
(
    [Cookie] => __stripe_mid=5e66430b-ad6a-41fc-999f-a47705efb90a48c927; cf_clearance=hGYFyhOjXKKdVaH4fyKkwKAxrZ6.WduWKylyjH5oAtc-1725223500-1.2.1.1-L0aOM9aWlCiHNTlxzYa0dKMwrRJ4otC_a0VQASaTTkj0S1CpVzh9hfdzcL01rh5t3upVC1ZmOKL5qgNyGFNkKtKITzRRjRIAgbjcMRBddYICg8k2u3aZ7KHnHZNr8D1UoGlI9XkHL3D_9.n1uj7tYC3dtS6hxem9nRRJOPO_MGKlDDj0QpmGouwQP08Ffl5OfVSd0NNWgGh5GuGMwY5FiDf9QoMRZWlli5uC7tNmFwmpqz.CZea0NFo.96VvqVm7EvHn.KptmdKevjzFa06dTeib1LCJR3u8GGtE4FjQ.KuPWToDGh_FIh2RtZQvW1_34b_3KbOUc_6ozdysFYv2p3FaUGNiwu5c3EfAIHL4LtKAD5ps_Ed9FRv5EL3l426FxJNe4UJMnz1SLIu.vyJsEg; wordpress_test_cookie=WP%20Cookie%20check; wordpress_logged_in_171212ad992468e5f38a03c9cec52973=temporal08%7C1730920768%7CFRHrZtDbOmJ6XD2DyZuvrDGh1gr32TumqNRizsr8IVh%7Cec5cc0a1dceb8bfcc6508447949fdd3961e076974431a78f733f037889041093; __stripe_sid=1e6bd3ec-916c-4092-9040-5839b790018999bfa5
    [Accept-Language] => es-419,es;q=0.9,es-ES;q=0.8,en;q=0.7,en-GB;q=0.6,en-US;q=0.5
    [Accept-Encoding] => gzip, deflate, br, zstd
    [Referer] => https://2upra.com/sample/hypnotic-dark-808/
    [Sec-Fetch-Dest] => empty
    [Sec-Fetch-Mode] => cors
    [Sec-Fetch-Site] => same-origin
    [Accept] => audio/mpeg,audio/ 
    [User-Agent] => Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0
    [X-Requested-With] => XMLHttpRequest
    [X-Wp-Nonce] => 5877b925e6
    [Sec-Ch-Ua-Mobile] => ?0
    [Sec-Ch-Ua] => "Chromium";v="130", "Microsoft Edge";v="130", "Not?A_Brand";v="99"
    [Pragma] => no-cache
    [Cache-Control] => no-cache
    [Sec-Ch-Ua-Platform] => "Windows"
    [Connection] => keep-alive
    [Host] => 2upra.com
    [Content-Length] => 
    [Content-Type] => 
)
2024-11-04 21:07:57 - Partes del token: Array
(
    [0] => 287226
    [1] => 1924905600
    [2] => cached
    [3] => 1cc59ea0363759a8aaef8a0248a1fff3
    [4] => 999999
    [5] => 3e731fc8f3a633e15579b8ba7cbd9a069a3e82ee6bb80b588ba37d3fdbf7e8e9
)
2024-11-04 21:07:57 - audioStreamEnd: Cargando audio desde el archivo en caché: /var/www/wordpress/wp-content/uploads/audio_cache/audio_287226.cache
2024-11-04 21:07:57 - audioStreamEnd: Transmisión del audio completada para el archivo: /var/www/wordpress/wp-content/uploads/audio_cache/audio_287226.cache
*/

function verificarAudio($token)
{
    guardarLog("Verificando token: $token");
    guardarLog("Iniciando verificación de audio");
    guardarLog("Token recibido: " . $token);
    guardarLog("Headers recibidos: " . print_r(getallheaders(), true));

    if (empty($token)) {
        guardarLog("Error: token vacío");
        return false;
    }

    // Verificar referer y headers
    if (!isset($_SERVER['HTTP_REFERER']) || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        guardarLog("Error: Faltan headers requeridos");
        return false;
    }

    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($referer_host !== '2upra.com') {
        guardarLog("Error: referer no válido");
        return false;
    }

    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        guardarLog("Error: No es una petición AJAX");
        return false;
    }

    $decoded = base64_decode($token);
    $parts = explode('|', $decoded);
    if (count($parts) !== 6) {
        guardarLog("Error: número incorrecto de partes en el token");
        return false;
    }
    guardarLog("Partes del token: " . print_r($parts, true));

    list($audio_id, $expiration, $user_ip, $unique_id, $max_usos, $signature) = $parts;

    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        // Generar una clave única para esta sesión y audio
        $session_key = 'audio_session_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];
        $cache_key = 'audio_access_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];

        // Verificar la firma
        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $expected_signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (!hash_equals($expected_signature, $signature)) {
            guardarLog("Error: firma no válida");
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

            guardarLog("Error: acceso directo o no autorizado");
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
        //guardarLog("usuarioEsAdminOPro: Error - ID de usuario inválido.");
        return false;
    }

    // Obtener el objeto usuario
    $user = get_user_by('id', $user_id);

    // Verificar si el usuario existe
    if (!$user) {
        //guardarLog("usuarioEsAdminOPro: Error - Usuario no encontrado para el ID: " . $user_id);
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($user->roles)) {
        //guardarLog("usuarioEsAdminOPro: Error - Usuario sin roles asignados. ID: " . $user_id);
        //guardarLog("usuarioEsAdminOPro: Información del usuario - " . print_r($user, true));
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $user->roles)) {
        //guardarLog("usuarioEsAdminOPro: Usuario es administrador. ID: " . $user_id);
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($user_id, 'pro', true);
    if (!empty($is_pro)) {
        //guardarLog("usuarioEsAdminOPro: Usuario tiene la meta 'pro'. ID: " . $user_id);
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    //guardarLog("usuarioEsAdminOPro: Usuario no es administrador ni tiene la meta 'pro'. ID: " . $user_id);
    return false;
}
