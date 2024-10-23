<?

// Función para verificar si el usuario es administrador o tiene la meta `pro`
function usuarioEsAdminOPro() {
    // Obtener el usuario actual de WordPress
    $current_user = wp_get_current_user();

    // Comprobar si el usuario está logueado
    if (!$current_user || 0 == $current_user->ID) {
        guardarLog("usuarioEsAdminOPro: Error - Usuario no logueado.");
        return false;
    }

    // Verificar si el usuario tiene roles asignados
    if (empty($current_user->roles)) {
        guardarLog("usuarioEsAdminOPro: Error - Usuario logueado pero sin roles asignados.");
        guardarLog("usuarioEsAdminOPro: Información del usuario - " . print_r($current_user, true)); // Log para verificar el contenido del usuario
        return false;
    }

    // Verificar si el usuario es administrador
    if (in_array('administrator', (array) $current_user->roles)) {
        guardarLog("usuarioEsAdminOPro: Usuario es administrador.");
        return true;
    }

    // Verificar si tiene la meta `pro`
    $is_pro = get_user_meta($current_user->ID, 'pro', true);
    if (!empty($is_pro)) {
        guardarLog("usuarioEsAdminOPro: Usuario tiene la meta 'pro'.");
        return true;
    }

    // Si no es administrador ni tiene la meta 'pro'
    guardarLog("usuarioEsAdminOPro: Usuario no es administrador ni tiene la meta 'pro'.");
    return false;
}


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

    header('Content-Type: ' . get_post_mime_type($audio_id));
    header("Accept-Ranges: bytes");

    // Si el usuario es admin o tiene meta `pro`, permitir caché del navegador
    if (usuarioEsAdminOPro()) {
        guardarLog("audioStreamEnd: Cargando con caché del navegador habilitada para el usuario admin/pro");
        header("Cache-Control: public, max-age=15768000"); 
    } else {
        guardarLog("audioStreamEnd: Cargando con caché del navegador deshabilitada para el usuario no admin/pro");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
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