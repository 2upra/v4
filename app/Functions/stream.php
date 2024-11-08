<?
define('ENABLE_BROWSER_AUDIO_CACHE', true);
define('ENABLE_AUDIO_ENCRYPTION', true);


function audioUrlSegura($audio_id)
{
    streamLog("Generando URL segura para audio ID: " . $audio_id);

    $user_id = get_current_user_id();
    if (usuarioEsAdminOPro($user_id)) {
        $url = site_url("/wp-json/1/v1/audio-pro/{$audio_id}");
        streamLog("URL generada para admin/pro: " . $url);
        return $url;
    }

    // Generación del token de audio
    $token = tokenAudio($audio_id);
    if (!$token) {
        streamLog("Error generando token para audio ID: " . $audio_id);
        return new WP_Error('invalid_audio_id', 'Audio ID inválido.');
    }

    // Generar timestamp y firma para la URL segura
    $timestamp = time();
    $signature = hash_hmac('sha256', "$audio_id|$timestamp", $_ENV['AUDIOCLAVE']);

    // Generar nonce para la seguridad de la URL
    $nonce = wp_create_nonce('wp_rest');
    $url = site_url("/wp-json/1/v1/2?token=" . urlencode($token) . '&_wpnonce=' . $nonce . '&ts=' . $timestamp . '&sig=' . $signature);

    streamLog("URL generada para usuario normal: " . $url);
    return $url;
}

function verificarFirma($request)
{
    $timestamp = $request->get_param('ts');
    $signature = $request->get_param('sig');
    $audio_id = $request->get_param('audio_id');

    // Verificar que la firma no haya expirado (tiempo de 1 hora)
    if (time() - $timestamp > 3600) {
        streamLog("Firma expiró para audio ID: " . $audio_id);
        return false;
    }

    // Generar la firma esperada y compararla con la recibida
    $expected_signature = hash_hmac('sha256', "$audio_id|$timestamp", $_ENV['AUDIOCLAVE']);
    if (!hash_equals($expected_signature, $signature)) {
        streamLog("Firma no válida para audio ID: " . $audio_id);
        return false;
    }

    streamLog("Firma verificada con éxito para audio ID: " . $audio_id);
    return true;
}



function tokenAudio($audio_id)
{
    streamLog("Generando token para audio_id: $audio_id");

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) {
        streamLog("Error: audio_id inválido: $audio_id");
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

        streamLog("Token generado exitosamente: $token");
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
            streamLog('Verificando permiso para token: ' . $request->get_param('token'));
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

    streamLog("Decrementando usos del token: $unique_id");
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
    streamLog("Verificando token: $token");
    streamLog("Iniciando verificación de audio");
    streamLog("Token recibido: " . $token);
    streamLog("Headers recibidos: " . print_r(getallheaders(), true));

    if (empty($token)) {
        streamLog("Error: token vacío");
        return false;
    }

    // Verificar headers esenciales mínimos
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || !isset($_SERVER['HTTP_REFERER'])) {
        streamLog("Error: Faltan headers básicos requeridos");
        return false;
    }

    // Verificar que sea una petición AJAX
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        streamLog("Error: No es una petición AJAX");
        return false;
    }

    // Verificar el origen de la petición
    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($referer_host !== '2upra.com') {
        streamLog("Error: referer no válido");
        return false;
    }

    // Verificar Origin si está presente, si no, verificar Referer
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        if ($_SERVER['HTTP_ORIGIN'] !== 'https://2upra.com') {
            streamLog("Error: Origin no válido");
            return false;
        }
    } else {
        // Si no hay Origin, verificamos que el Referer sea del mismo dominio
        if (!preg_match('/^https?:\/\/2upra\.com\//', $_SERVER['HTTP_REFERER'])) {
            streamLog("Error: Referer no válido para same-origin request");
            return false;
        }
    }

    // Verificar nonce de WordPress
    if (!check_ajax_referer('wp_rest', '_wpnonce', false)) {
        streamLog("Error: Nonce no válido");
        return false;
    }

    // Decodificar y verificar token
    $decoded = base64_decode($token);
    if ($decoded === false) {
        streamLog("Error: token no es base64 válido");
        return false;
    }

    $parts = explode('|', $decoded);
    if (count($parts) !== 6) {
        streamLog("Error: número incorrecto de partes en el token");
        return false;
    }
    streamLog("Partes del token: " . print_r($parts, true));

    list($audio_id, $expiration, $user_ip, $unique_id, $max_usos, $signature) = $parts;

    if (defined('ENABLE_BROWSER_AUDIO_CACHE') && ENABLE_BROWSER_AUDIO_CACHE) {
        // Lógica para modo caché
        $session_key = 'audio_session_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];
        $cache_key = 'audio_access_' . $audio_id . '_' . $_SERVER['REMOTE_ADDR'];

        // Verificar firma
        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $expected_signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (!hash_equals($expected_signature, $signature)) {
            streamLog("Error: firma no válida");
            return false;
        }

        $current_session = get_transient($session_key);
        if ($current_session === false) {
            set_transient($session_key, $token, 7776000);
            set_transient($cache_key, 1, 7776000);
            header('Cache-Control: public, max-age=7776000');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 7776000) . ' GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
            streamLog("Nueva sesión iniciada para audio_id: $audio_id");
            return true;
        }

        $access_count = get_transient($cache_key);
        if ($access_count !== false) {
            set_transient($cache_key, $access_count + 1, 3600);
            streamLog("Acceso permitido - Contador: " . ($access_count + 1));
            return true;
        }

        streamLog("Error: acceso no autorizado");
        return false;
    } else {
        // Lógica para modo sin caché
        if ($_SERVER['REMOTE_ADDR'] !== $user_ip) {
            streamLog("Error: IP no coincide");
            return false;
        }

        if (time() > $expiration) {
            streamLog("Error: token expirado");
            return false;
        }

        $usos_restantes = get_transient('audio_token_' . $unique_id);
        if ($usos_restantes === false || $usos_restantes <= 0) {
            streamLog("Error: sin usos restantes");
            return false;
        }

        $data = $audio_id . '|' . $expiration . '|' . $user_ip . '|' . $unique_id;
        $expected_signature = hash_hmac('sha256', $data, $_ENV['AUDIOCLAVE']);

        if (hash_equals($expected_signature, $signature)) {
            decrementaUsosToken($unique_id);
            streamLog("Acceso permitido - Modo sin caché");
            return true;
        }

        streamLog("Error: firma no válida en modo sin caché");
        return false;
    }
}

function encryptChunk($chunk, $iv, $key) {
    try {
        streamLog("Iniciando encriptación de chunk con parámetros:");
        streamLog("Longitud del chunk: " . strlen($chunk));
        streamLog("Longitud del IV: " . strlen($iv));
        streamLog("Longitud de la clave hex: " . strlen($key));
        
        
        // Convertir clave hex a binario
        $binary_key = hex2bin($key);
        streamLog("Longitud de la clave binaria: " . strlen($binary_key));
        
        if ($binary_key === false) {
            throw new Exception('Error al convertir la clave hexadecimal a binario');
        }
        
        // Asegurar que el padding sea consistente
        $blockSize = 16;
        $pad = $blockSize - (strlen($chunk) % $blockSize);
        $chunk = $chunk . str_repeat(chr($pad), $pad);
        
        // Encriptar
        $encrypted = openssl_encrypt(
            $chunk,
            'AES-256-CBC',
            $binary_key,
            OPENSSL_RAW_DATA, // Remover OPENSSL_ZERO_PADDING
            $iv
        );
        
        if ($encrypted === false) {
            throw new Exception("Error en la encriptación: " . openssl_error_string());
        }
        header('X-Original-Length: ' . strlen($chunk));
        header('X-Encrypted-Length: ' . strlen($encrypted));
        streamLog("Encriptación exitosa - Longitud datos encriptados: " . strlen($encrypted));
        streamLog("Primeros bytes encriptados (hex): " . bin2hex(substr($encrypted, 0, 16)));
        
        return $encrypted;
    } catch (Exception $e) {
        streamLog("Error en encryptChunk: " . $e->getMessage());
        throw $e;
    }
}

function audioStreamEnd($data)
{
    if (ob_get_level()) ob_end_clean();

    try {
        // Decodificar y validar token
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
        streamLog("Procesando transmisión para audio_id: $audio_id");

        // Gestión de caché
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/audio_cache';
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
            streamLog("Directorio de caché creado: $cache_dir");
        }

        $cache_file = $cache_dir . '/audio_' . $audio_id . '.cache';
        $file = null;

        // Verificar caché
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < 24 * 60 * 60)) {
            streamLog("Usando archivo cacheado: $cache_file");
            $file = $cache_file;
        } else {
            $original_file = get_attached_file($audio_id);
            if (!file_exists($original_file)) {
                throw new Exception('Archivo de audio no encontrado');
            }

            if (!@copy($original_file, $cache_file)) {
                throw new Exception('Error al cachear el archivo');
            }

            streamLog("Archivo cacheado correctamente: $cache_file");
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

        streamLog("Tamaño del archivo: $size bytes");

        // Headers básicos
        header('Content-Type: audio/mpeg');
        header('Accept-Ranges: bytes');
        header('X-Content-Type-Options: nosniff');

        // Gestión de caché del navegador
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

        // Manejo de ranges para streaming
        if (isset($_SERVER['HTTP_RANGE'])) {
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            streamLog("Solicitando rango: $range");

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
            streamLog("Rango ajustado: $start - $end");
            header('HTTP/1.1 206 Partial Content');
        }

        // Headers de contenido
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);

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
            
            
            // Transmisión encriptada
            while (!feof($fp) && $sent < $length) {
                $remaining = $length - $sent;
                $chunk_size = min($buffer_size, $remaining);
                $chunk = fread($fp, $chunk_size);
        
                if ($chunk === false) {
                    break;
                }
        
                $encrypted_chunk = encryptChunk($chunk, $iv, $key);
                echo $encrypted_chunk;
                $sent += strlen($encrypted_chunk);
        
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
                    streamLog("Error al leer el archivo");
                    break;
                }

                echo $chunk;
                $sent += strlen($chunk);
                streamLog("Chunk sin encriptar enviado: " . strlen($chunk) . " bytes");

                flush();
                if ($sleep_time > 0) {
                    usleep($sleep_time);
                }
            }
        }

        // Logging y limpieza
        streamLog("Transmisión completada: $sent bytes enviados");
        fclose($fp);
        exit();
    } catch (Exception $e) {
        streamLog("Error en audioStreamEnd: " . $e->getMessage());

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
        //streamLog("usuarioEsAdminOPro: Error - ID de usuario inválido.");
        return false;
    }

    // Obtener el objeto usuario
    $user = get_user_by('id', $user_id);

    // Verificar si el usuario existe
    if (!$user) {
        //streamLog("usuarioEsAdminOPro: Error - Usuario no encontrado para el ID: " . $user_id);
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($user->roles)) {
        //streamLog("usuarioEsAdminOPro: Error - Usuario sin roles asignados. ID: " . $user_id);
        //streamLog("usuarioEsAdminOPro: Información del usuario - " . print_r($user, true));
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $user->roles)) {
        //streamLog("usuarioEsAdminOPro: Usuario es administrador. ID: " . $user_id);
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($user_id, 'pro', true);
    if (!empty($is_pro)) {
        //streamLog("usuarioEsAdminOPro: Usuario tiene la meta 'pro'. ID: " . $user_id);
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    //streamLog("usuarioEsAdminOPro: Usuario no es administrador ni tiene la meta 'pro'. ID: " . $user_id);
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
