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
class AudioSecureHandler {
    private static $instance = null;
    private $cache_enabled;
    private $is_admin;
    private const CACHE_TIME = 86400; // 24 horas
    private const BUFFER_SIZE = 8192; // 8KB
    private const TOKEN_EXPIRY = 3600; // 1 hora

    private function __construct() {
        $this->is_admin = current_user_can('administrator');
        $this->cache_enabled = defined('AUDIO_CACHE_ENABLED') && AUDIO_CACHE_ENABLED;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function generateToken($audio_id) {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $audio_id)) return false;

        $data = [
            'id' => $audio_id,
            'exp' => time() + self::TOKEN_EXPIRY,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'uid' => uniqid(),
            'adm' => $this->is_admin
        ];

        $payload = json_encode($data);
        $signature = hash_hmac('sha256', $payload, $_ENV['AUDIOCLAVE']);
        return base64_encode($payload) . '.' . $signature;
    }

    public function verifyToken($token) {
        list($payload, $signature) = explode('.', $token);
        
        $data = json_decode(base64_decode($payload), true);
        if (!$data) return false;

        if (!$this->is_admin && $data['ip'] !== $_SERVER['REMOTE_ADDR']) return false;
        if (time() > $data['exp']) return false;
        if ($this->tokenIsUsed($data['uid']) && !$this->is_admin) return false;

        $expected_signature = hash_hmac('sha256', base64_decode($payload), $_ENV['AUDIOCLAVE']);
        
        if (hash_equals($expected_signature, $signature)) {
            if (!$this->is_admin) $this->markTokenAsUsed($data['uid']);
            return $data;
        }
        return false;
    }

    private function tokenIsUsed($uid) {
        return get_transient('audio_token_' . $uid) !== false;
    }

    private function markTokenAsUsed($uid) {
        set_transient('audio_token_' . $uid, true, self::TOKEN_EXPIRY);
    }

    public function streamAudio($token) {
        $data = $this->verifyToken($token);
        if (!$data) return new WP_Error('invalid_token', 'Token inválido');

        $audio_id = $data['id'];
        $file_path = $this->getAudioPath($audio_id);
        
        if (!$file_path) {
            return new WP_Error('file_not_found', 'Audio no encontrado');
        }

        $this->sendHeaders($audio_id, $file_path);
        $this->streamFile($file_path);
        exit;
    }

    private function getAudioPath($audio_id) {
        if ($this->is_admin && $this->cache_enabled) {
            $cache_path = $this->getCachePath($audio_id);
            if (file_exists($cache_path) && (time() - filemtime($cache_path) < self::CACHE_TIME)) {
                return $cache_path;
            }
        }

        $original_path = get_attached_file($audio_id);
        if (!file_exists($original_path)) return false;

        if ($this->is_admin && $this->cache_enabled) {
            $this->cacheFile($original_path, $cache_path);
            return $cache_path;
        }

        return $original_path;
    }

    private function getCachePath($audio_id) {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/audio_cache';
        if (!file_exists($cache_dir)) wp_mkdir_p($cache_dir);
        return $cache_dir . '/audio_' . $audio_id . '.cache';
    }

    private function cacheFile($source, $destination) {
        copy($source, $destination);
    }

    private function sendHeaders($audio_id, $file_path) {
        $size = filesize($file_path);
        header('Content-Type: ' . get_post_mime_type($audio_id));
        header('Accept-Ranges: bytes');
        
        if ($this->is_admin && $this->cache_enabled) {
            header('Cache-Control: public, max-age=' . self::CACHE_TIME);
        } else {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        // Manejo de ranges para streaming parcial
        if (isset($_SERVER['HTTP_RANGE'])) {
            $this->handleRangeRequest($file_path, $size);
        } else {
            header('Content-Length: ' . $size);
        }
    }

    private function streamFile($file_path) {
        $fp = fopen($file_path, 'rb');
        while (!feof($fp)) {
            echo fread($fp, self::BUFFER_SIZE);
            flush();
        }
        fclose($fp);
    }
}

// Inicialización y hooks
add_action('rest_api_init', function() {
    register_rest_route('1/v1', '/2', [
        'methods' => 'GET',
        'callback' => function($request) {
            return AudioSecureHandler::getInstance()->streamAudio($request->get_param('token'));
        },
        'permission_callback' => '__return_true'
    ]);
});

function get_secure_audio_url($audio_id) {
    $token = AudioSecureHandler::getInstance()->generateToken($audio_id);
    return $token ? site_url("/wp-json/1/v1/2?token=" . urlencode($token)) : false;
}