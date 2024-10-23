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


function wave($audio_url, $audio_id_lite, $post_id)
{
    $audio_handler = AudioSecureHandler::getInstance();
    $wave = get_post_meta($post_id, 'waveform_image_url', true);
    $waveCargada = get_post_meta($post_id, 'waveCargada', true);
    $urlAudioSegura = $audio_handler->getSecureUrl($post_id);

    // Verificación de error
    if (!$urlAudioSegura) {
        error_log("Error generando URL segura para audio ID: " . $audio_id_lite);
        return; // O maneja el error como prefieras
    }
?>
    <div id="waveform-<?php echo esc_attr($post_id); ?>"
        class="waveform-container without-image"
        postIDWave="<?php echo esc_attr($post_id); ?>"
        data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>"
        data-audio-url="<?php echo esc_url($urlAudioSegura); ?>">
        <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
        <div class="waveform-message"></div>
        <div class="waveform-loading" style="display: none;">Cargando...</div>
    </div>
<?php
}

function handle_secure_audio_stream()
{
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        wp_send_json_error('Token no proporcionado');
    }

    $handler = AudioSecureHandler::getInstance();
    $result = $handler->streamAudio($token);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
}

add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/2', [
        'methods' => 'GET',
        'callback' => function ($request) {
            return AudioSecureHandler::getInstance()->streamAudio($request->get_param('token'));
        },
        'permission_callback' => '__return_true'
    ]);
});

add_action('wp_ajax_stream_secure_audio', 'handle_secure_audio_stream');
add_action('wp_ajax_nopriv_stream_secure_audio', 'handle_secure_audio_stream');


class AudioSecureHandler
{
    private static $instance = null;
    private $cache_enabled;
    private $is_admin;
    private const CACHE_TIME = 86400; // 24 horas
    private const BUFFER_SIZE = 8192; // 8KB
    private const TOKEN_EXPIRY = 3600; // 1 hora
    private const CHUNK_SIZE = 1048576; // 1MB para streaming

    private function __construct()
    {
        $this->is_admin = current_user_can('administrator');
        $this->cache_enabled = defined('AUDIO_CACHE_ENABLED') && AUDIO_CACHE_ENABLED;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function generateToken($audio_id)
    {
        $mime_type = get_post_mime_type($audio_id);
        if (strpos($mime_type, 'audio') === false) {
            guardarLog('generateToken: audio_id no es un archivo de audio, MIME: ' . $mime_type);
            return false;
        }
        guardarLog('generateToken: MIME type correcto: ' . $mime_type);
        // Log para comprobar que el audio_id es válido
        guardarLog('generateToken: audio_id válido: ' . $audio_id);

        // Continuar con el proceso de tokenización...
        $data = [
            'id' => $audio_id,
            'exp' => time() + self::TOKEN_EXPIRY,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'uid' => uniqid('', true),
            'adm' => $this->is_admin,
            'nonce' => wp_create_nonce('audio_stream_' . $audio_id)
        ];

        $payload = json_encode($data);
        $signature = hash_hmac('sha256', $payload, $_ENV['AUDIOCLAVE']);
        return base64_encode($payload) . '.' . $signature;
    }



    public function verifyToken($token)
    {
        list($payload, $signature) = explode('.', $token);

        $data = json_decode(base64_decode($payload), true);
        if (!$data) return false;

        // Verificaciones de seguridad
        if (!$this->is_admin) {
            if ($data['ip'] !== $_SERVER['REMOTE_ADDR']) return false;
            if (time() > $data['exp']) return false;
            if ($this->tokenIsUsed($data['uid'])) return false;
            if (!wp_verify_nonce($data['nonce'], 'audio_stream_' . $data['id'])) return false;
        }

        $expected_signature = hash_hmac('sha256', base64_decode($payload), $_ENV['AUDIOCLAVE']);

        if (hash_equals($expected_signature, $signature)) {
            if (!$this->is_admin) $this->markTokenAsUsed($data['uid']);
            return $data;
        }
        return false;
    }

    private function tokenIsUsed($uid)
    {
        return get_transient('audio_token_' . $uid) !== false;
    }

    private function markTokenAsUsed($uid)
    {
        set_transient('audio_token_' . $uid, true, self::TOKEN_EXPIRY);
    }

    public function streamAudio($token)
    {
        $data = $this->verifyToken($token);
        if (!$data) {
            guardarLog('Token verification failed');
            return new WP_Error('invalid_token', 'Token inválido');
        }

        $audio_id = $data['id'];
        $file_path = $this->getAudioPath($audio_id);

        if (!$file_path) {
            return new WP_Error('file_not_found', 'Audio no encontrado');
        }

        guardarLog('Streaming audio file: ' . $file_path);
        guardarLog('Audio ID: ' . $audio_id);
        guardarLog('MIME Type: ' . mime_content_type($file_path));
        $this->rateLimiter();

        $this->sendHeaders($audio_id, $file_path);
        $this->streamFile($file_path);
        exit;
    }

    private function rateLimiter()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'audio_rate_' . $ip;
        $requests = (int)get_transient($key) ?: 0;

        if ($requests > 100) { // Límite de 100 solicitudes por hora
            http_response_code(429);
            die('Too Many Requests');
        }

        set_transient($key, $requests + 1, 3600);
    }

    private function getAudioPath($audio_id)
    {
        if ($this->cache_enabled) {
            $cache_path = $this->getCachePath($audio_id);
            if (file_exists($cache_path) && (time() - filemtime($cache_path) < self::CACHE_TIME)) {
                return $cache_path;
            }
        }

        $original_path = get_attached_file($audio_id);
        if (!file_exists($original_path)) return false;

        if ($this->cache_enabled) {
            $this->cacheFile($original_path, $cache_path);
            return $cache_path;
        }

        return $original_path;
    }

    private function getCachePath($audio_id)
    {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/audio_cache';
        if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);

        // Añadir sal aleatoria al nombre del archivo en caché
        $salt = substr(md5(uniqid()), 0, 8);
        return $cache_dir . '/audio_' . $audio_id . '_' . $salt . '.cache';
    }

    private function cacheFile($source, $destination)
    {
        if (!copy($source, $destination)) return false;
        // Establecer permisos restrictivos
        chmod($destination, 0640);
        return true;
    }

    private function sendHeaders($audio_id, $file_path)
    {
        $size = filesize($file_path);
        $mime = get_post_mime_type($audio_id);

        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Access-Control-Allow-Origin: *');

        if ($this->cache_enabled) {
            header('Cache-Control: private, max-age=' . self::CACHE_TIME);
            header('ETag: "' . md5_file($file_path) . '"');
        } else {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        if (isset($_SERVER['HTTP_RANGE'])) {
            $this->handleRangeRequest($file_path, $size);
        } else {
            header('Content-Length: ' . $size);
        }

        guardarLog('Sending audio file: ' . $file_path);
        guardarLog('MIME Type: ' . $mime);
        guardarLog('File size: ' . $size);
    }

    private function handleRangeRequest($file_path, $size)
    {
        $range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);
        list($start, $end) = explode('-', $range);

        $end = (empty($end)) ? $size - 1 : min((int)$end, $size - 1);
        $start = (empty($start)) ? $size - $end : min((int)$start, $size - 1);

        header('HTTP/1.1 206 Partial Content');
        header(sprintf('Content-Range: bytes %d-%d/%d', $start, $end, $size));
        header('Content-Length: ' . ($end - $start + 1));
    }

    private function streamFile($file_path)
    {
        if (!file_exists($file_path)) {
            guardarLog('El archivo no existe: ' . $file_path);
            return;
        }

        $mime_type = mime_content_type($file_path);
        if (!strpos($mime_type, 'audio/') === 0) {
            guardarLog('Tipo MIME no válido: ' . $mime_type);
            return;
        }

        $fp = fopen($file_path, 'rb');
        if (!$fp) {
            guardarLog('No se pudo abrir el archivo: ' . $file_path);
            return;
        }
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);
            list($start,) = explode('-', $range);
            fseek($fp, (int)$start);
        }

        // Deshabilitar el tiempo límite de ejecución para archivos grandes
        set_time_limit(0);

        // Limpiar cualquier salida anterior
        ob_clean();
        flush();

        // Streaming del archivo
        while (!feof($fp) && connection_status() == 0) {
            $buffer = fread($fp, self::BUFFER_SIZE);
            echo $buffer;
            ob_flush();
            flush();

            // Pequeña pausa para control de velocidad
            if (!$this->is_admin) {
                usleep(50); // 50 microsegundos de pausa
            }
        }

        fclose($fp);
        return true;
    }

    public function getSecureUrl($audio_id)
    {
        $token = $this->generateToken($audio_id);
        return add_query_arg([
            'action' => 'stream_secure_audio',
            'token' => $token
        ], admin_url('admin-ajax.php'));
    }
}


// Inicialización y hooks
