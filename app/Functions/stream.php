<?
define('ENABLE_BROWSER_AUDIO_CACHE', true);
define('ENABLE_AUDIO_ENCRYPTION', true);


function audioUrlSegura($audio_id)
{

    $user_id = get_current_user_id();
    if (usuarioEsAdminOPro($user_id)) {
        $url = site_url("/wp-json/1/v1/audio-pro/{$audio_id}");
        return $url;
    }

    // Generación del token de audio
    $token = tokenAudio($audio_id);
    if (!$token) {
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }

    // Generar timestamp y firma para la URL segura
    $timestamp = time();
    $signature = hash_hmac('sha256', "$audio_id|$timestamp", $_ENV['AUDIOCLAVE']);

    // Generar nonce para la seguridad de la URL
    $nonce = wp_create_nonce('wp_rest');
    $url = site_url("/wp-json/1/v1/2?token=" . urlencode($token) . '&_wpnonce=' . $nonce . '&ts=' . $timestamp . '&sig=' . $signature);

    return $url;
}

function verificarFirma($request)
{
    $timestamp = $request->get_param('ts');
    $signature = $request->get_param('sig');
    $audio_id = $request->get_param('audio_id');

    // Verificar que la firma no haya expirado (tiempo de 1 hora)
    if (time() - $timestamp > 3600) {
        return false;
    }

    // Generar la firma esperada y compararla con la recibida
    $expected_signature = hash_hmac('sha256', "$audio_id|$timestamp", $_ENV['AUDIOCLAVE']);
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }

    return true;
}



function tokenAudio($audio_id)
{

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
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
        $max_usos = 1;

        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);
        $token = base64_encode($data . '|' . $max_usos . '|' . $signature);

        set_transient('audio_token_' . $unique_id, $max_usos, 3600);

        return $token;
    }
}



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

    register_rest_route('1/v1', '/2', array(
        'methods' => 'GET',
        'callback' => 'audioStreamEnd',
        'args' => array(
            'token' => array(
                'required' => true,
                'validate_callback' => function ($param) {
                    return !empty($param) && is_string($param);
                }
            ),
        ),
        'permission_callback' => function ($request) {
            return verificarAudio($request->get_param('token'));
        }
    ));
});


function decrementaUsosToken($unique_id)
{
    // Solo decrementar si el cacheo del navegador está desactivado
    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        return; // No decrementar para tokens cacheados
    }

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

    if (empty($token)) {
        return false;
    }

    // Verificar headers esenciales mínimos
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || !isset($_SERVER['HTTP_REFERER'])) {
        return false;
    }

    // Verificar que sea una petición AJAX
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        return false;
    }

    // Verificar el origen de la petición
    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($referer_host !== '2upra.com') {
        return false;
    }

    // Verificar Origin si está presente, si no, verificar Referer
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        if ($_SERVER['HTTP_ORIGIN'] !== 'https://2upra.com') {
            return false;
        }
    } else {
        // Si no hay Origin, verificamos que el Referer sea del mismo dominio
        if (!preg_match('/^https?:\/\/2upra\.com\//', $_SERVER['HTTP_REFERER'])) {
            return false;
        }
    }

    // Verificar nonce de WordPress
    if (!check_ajax_referer('wp_rest', '_wpnonce', false)) {
        return false;
    }

    // Decodificar y verificar token
    $decoded = base64_decode($token);
    if ($decoded === false) {
        return false;
    }

    $parts = explode('|', $decoded);
    if (count($parts) !== 6) {
        return false;
    }

    list($audio_id, $expiration, $user_ip, $unique_id, $max_usos, $signature) = $parts;

    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        // Lógica para modo caché
        $session_key = 'audio_session_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];
        $cache_key = 'audio_access_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];

        // Verificar firma
        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $expected_signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }

        $current_session = get_transient($session_key);
        if ($current_session === false) {
            set_transient($session_key, $token, 7776000);
            set_transient($cache_key, 1, 7776000);
            header('Cache-Control: public, max-age=7776000');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 7776000) . ' GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
            return true;
        }

        $access_count = get_transient($cache_key);
        if ($access_count !== false) {
            set_transient($cache_key, $access_count + 1, 3600);
            return true;
        }

        return false;
    } else {
        // Lógica para modo sin caché
        if ($_SERVER['REMOTE_ADDR'] !== $user_ip) {
            return false;
        }

        if (time() > $expiration) {
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

function encryptChunk($chunk, $iv, $key)
{
    try {

        $binary_key = hex2bin($key);
        if ($binary_key === false) {
            throw new Exception('Error al convertir la clave hexadecimal a binario');
        }

        $encrypted = openssl_encrypt(
            $chunk,
            'AES-256-CBC',
            $binary_key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new Exception("Error en la encriptación: " . openssl_error_string());
        }

        $length_prefix = pack('N', strlen($encrypted));
        $final_data = $length_prefix . $encrypted;
        $encrypted_length = strlen($final_data);
        header('Content-Length: ' . $encrypted_length);
        streamLog("Encriptación exitosa - Longitud datos encriptados: " . strlen($final_data));
        return $final_data;
    } catch (Exception $e) {
        streamLog("Error en encryptChunk: " . $e->getMessage());
        throw $e;
    }
}

function audioStreamEnd($data)
{
    if (ob_get_level()) ob_end_clean();

    try {
        // Procesar token
        $token = $data['token'];
        $decoded = base64_decode($token);
        if ($decoded === false) {
            throw new Exception('Token inválido');
        }

        $parts = explode('|', $decoded);
        if (count($parts) < 1) {
            throw new Exception('Formato de token inválido');
        }

        $audio_id = $parts[0];

        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/audio_cache';
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }

        $cache_file = $cache_dir . '/audio_' . $audio_id . '.cache';
        $file = null;

        // Verificar caché
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < 24 * 60 * 60)) {
            $file = $cache_file;
        } else {
            $original_file = get_attached_file($audio_id);
            if (!file_exists($original_file)) {
                throw new Exception('Archivo de audio no encontrado');
            }
            if (!@copy($original_file, $cache_file)) {
                throw new Exception('Error al cachear el archivo');
            }
            $file = $cache_file;
        }

        // Abrir archivo
        $fp = @fopen($file, 'rb');
        if (!$fp) {
            throw new Exception('No se pudo abrir el archivo');
        }

        // Configuración de streaming
        $size = filesize($file);
        $length = $size;
        $start = 0;
        $end = $size - 1;
        $etag = '"' . md5($file . filemtime($file)) . '"';

        header('Content-Type: audio/mpeg');
        header('Accept-Ranges: bytes');
        header('X-Content-Type-Options: nosniff');

        if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
            $cache_time = 60 * 60 * 24; // 24 horas
            header('Cache-Control: private, must-revalidate, max-age=' . $cache_time);
            header('ETag: ' . $etag);

            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        } else {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        if (isset($_SERVER['HTTP_RANGE'])) {
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            if ($range == '-') {
                $c_start = $size - substr($range, 1);
                $c_end = $end;
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

        // Headers de contenido
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);

        // Logging de Content-Range y Content-Length
        streamLog("Content-Range: bytes $start-$end/$size");
        streamLog("Content-Length: $length");

        // Configuración de encriptación
        $buffer_size = 8192; // 8KB
        $sent = 0;
        $rate_limit = 512 * 1024; // 512KB por segundo
        $sleep_time = ($buffer_size / $rate_limit) * 1000000; // Convertir a microsegundos

        if (defined('ENABLE_AUDIO_ENCRYPTION') && ENABLE_AUDIO_ENCRYPTION) {
            $iv = openssl_random_pseudo_bytes(16);
            if ($iv === false) {
                throw new Exception('No se pudo generar el IV');
            }
            header('X-Encryption-IV: ' . base64_encode($iv));

            if (!isset($_ENV['AUDIOCLAVE'])) {
                throw new Exception('Clave de encriptación no configurada');
            }
            $key = $_ENV['AUDIOCLAVE'];

            while (!feof($fp) && $sent < $length) {
                $remaining = $length - $sent;
                $chunk_size = min($buffer_size, $remaining);
                $chunk = fread($fp, $chunk_size);

                if ($chunk === false) {
                    break;
                }

                // Encriptar chunk
                $encrypted_chunk = encryptChunk($chunk, $iv, $key);
                echo $encrypted_chunk;

                // Actualizar el total enviado usando longitud de chunk encriptado
                $sent += strlen($encrypted_chunk);

                // Log de depuración
                streamLog("Bytes enviados en este ciclo: " . strlen($encrypted_chunk) . " / Total enviados: $sent de $length");

                // Control de envío
                flush();
                if ($sleep_time > 0) {
                    usleep($sleep_time);
                }
            }
        } else {
            // Transmisión sin encriptación
            while (!feof($fp) && $sent < $length) {
                $remaining = $length - $sent;
                $chunk_size = min($buffer_size, $remaining);
                $chunk = fread($fp, $chunk_size);

                if ($chunk === false) {
                    break;
                }

                echo $chunk;
                $sent += strlen($chunk);

                flush();
                if ($sleep_time > 0) {
                    usleep($sleep_time);
                }
            }
        }


        // Logging y limpieza
        fclose($fp);
        exit();
    } catch (Exception $e) {

        if (ob_get_level()) ob_end_clean();
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage()
        ]);
        exit();
    }
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
        return false;
    }

    // Obtener el objeto usuario
    $user = get_user_by('id', $user_id);

    // Verificar si el usuario existe
    if (!$user) {
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($user->roles)) {
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $user->roles)) {
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($user_id, 'pro', true);
    if (!empty($is_pro)) {
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    return false;
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

add_action('init', function () {
    if (!defined('DOING_AJAX') && !defined('REST_REQUEST')) {
        return;
    }
    $user_id = wp_validate_auth_cookie('', 'logged_in');
    if ($user_id) {
        wp_set_current_user($user_id);
    }
});
add_action('init', 'bloquear_acceso_directo_archivos');
