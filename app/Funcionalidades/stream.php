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
    $urlAudioSegura = $audio_handler->getSecureUrl($audio_id_lite);

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

//aqui hay 2 problemas graves, 1) el audio no se esta cacheando en ningun momento, segundo, puedo acceder directamente al enlace https://2upra.com/wp-admin/admin-ajax.php?action=stream_secure_audio&token=ejemplo&security_nonce=ejemplo sin problema y descargar el audio cosa que no debería

/*
function inicializarWaveforms() {
    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                const container = entry.target;
                const postId = container.getAttribute('postIDWave');
                const audioUrl = container.getAttribute('data-audio-url');

                if (entry.isIntersecting) {
                    if (!container.dataset.loadTimeoutSet) {
                        const loadTimeout = setTimeout(() => {
                            if (!container.dataset.audioLoaded) {
                                loadAudio(postId, audioUrl, container);
                            }
                        }, 20000); // Carga el audio después de 20 segundos de estar en el viewport

                        container.dataset.loadTimeout = loadTimeout;
                        container.dataset.loadTimeoutSet = 'true';
                    }
                } else {
                    if (container.dataset.loadTimeoutSet) {
                        clearTimeout(container.dataset.loadTimeout);
                        delete container.dataset.loadTimeout;
                        delete container.dataset.loadTimeoutSet;
                    }
                }
            });
        },
        {threshold: 0.5}
    );

    document.querySelectorAll('.waveform-container').forEach(container => {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');
        if (postId && audioUrl && !container.dataset.initialized) {
            container.dataset.initialized = 'true';
            observer.observe(container);
            container.addEventListener('click', () => {
                if (!container.dataset.audioLoaded) {
                    if (container.dataset.loadTimeoutSet) {
                        clearTimeout(container.dataset.loadTimeout);
                        delete container.dataset.loadTimeout;
                        delete container.dataset.loadTimeoutSet;
                    }
                    loadAudio(postId, audioUrl, container);
                }
            });
        }
    });
}

function loadAudio(postId, audioUrl, container) {
    if (!container.dataset.audioLoaded) {
        const secureUrl = audioUrl + (audioUrl.includes('?') ? '&' : '?') + 'security_nonce=' + audioSecurityVars.nonce;
        window.we(postId, secureUrl);
        container.dataset.audioLoaded = 'true';
    }
}

window.we = function (postId, audioUrl) {
    const container = document.getElementById(`waveform-${postId}`);
    const MAX_RETRIES = 3;
    let wavesurfer;

    const loadAndPlayAudioStream = (retryCount = 0) => {
        if (retryCount >= MAX_RETRIES) {
            console.error('No se pudo cargar el audio después de varios intentos');
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-message').style.display = 'block';
            container.querySelector('.waveform-message').textContent = 'Error al cargar el audio.';
            return;
        }

        window.audioLoading = true;

        fetch(audioUrl, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'audio/*' // Añadido para especificar que esperamos audio
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                console.log('Content-Type:', response.headers.get('content-type'));
                return response.arrayBuffer();
            })
            .then(buffer => {
                // Crear un blob con el tipo explícito
                const blob = new Blob([buffer], {type: 'audio/mpeg'}); // o el tipo que corresponda

                const audioBlobUrl = URL.createObjectURL(blob);
                wavesurfer = initWavesurfer(container);

                // Manejar errores de decodificación
                wavesurfer.on('error', err => {
                    console.error('Error en wavesurfer:', err);
                    if (retryCount < MAX_RETRIES) {
                        setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                    }
                });

                // Cargar el audio
                wavesurfer.load(audioBlobUrl);

                const waveformBackground = container.querySelector('.waveform-background');
                if (waveformBackground) {
                    waveformBackground.style.display = 'none';
                }

                wavesurfer.on('ready', () => {
                    window.audioLoading = false;
                    container.dataset.audioLoaded = 'true';
                    container.querySelector('.waveform-loading').style.display = 'none';
                    const waveCargada = container.getAttribute('data-wave-cargada') === 'true';

                    const isMobile = /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);

                    if (!waveCargada && !isMobile) {
                        setTimeout(() => {
                            const image = generateWaveformImage(wavesurfer);
                            sendImageToServer(image, postId);
                        }, 1);
                    }

                    container.addEventListener('click', () => {
                        if (wavesurfer.isPlaying()) {
                            wavesurfer.pause();
                        } else {
                            wavesurfer.play();
                        }
                    });
                });
            })
            .catch(error => {
                console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`, error);
                if (retryCount < MAX_RETRIES) {
                    setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                }
            });
    };

    loadAndPlayAudioStream();
};

// La función que inicializa WaveSurfer con los estilos y configuraciones deseados
function initWavesurfer(container) {
    const containerHeight = container.classList.contains('waveform-container-venta') ? 60 : 102;
    const ctx = document.createElement('canvas').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 500);
    const progressGradient = ctx.createLinearGradient(0, 0, 0, 500);

    // Configuración de los colores del gradiente
    gradient.addColorStop(0, '#FFFFFF');
    gradient.addColorStop(0.55, '#FFFFFF');
    gradient.addColorStop(0.551, '#d43333');
    gradient.addColorStop(1, '#d43333');

    progressGradient.addColorStop(0, '#d43333');
    progressGradient.addColorStop(1, '#d43333');

    return WaveSurfer.create({
        container: container,
        waveColor: gradient,
        progressColor: progressGradient,
        backend: 'WebAudio',
        interact: true,
        barWidth: 2,
        height: containerHeight,
        partialRender: true
    });
}

*/

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
        $this->cache_enabled = true; // Forzamos el cacheo
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
            return false;
        }


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
            wp_die('Token inválido', 'Error', ['response' => 403]);
        }

        $audio_id = $data['id'];
        $file_path = get_attached_file($audio_id);
        
        if (!file_exists($file_path)) {
            wp_die('Audio no encontrado', 'Error', ['response' => 404]);
        }

        $this->sendHeaders($file_path);
        $this->streamFileWithRangeSupport($file_path);
        exit;
    }


    private function validateReferer($referer) 
    {
        $allowed_domains = [
            parse_url(home_url(), PHP_URL_HOST),
            // Añadir otros dominios permitidos si es necesario
        ];
        
        $referer_host = parse_url($referer, PHP_URL_HOST);
        return in_array($referer_host, $allowed_domains);
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

    private function cacheFile($source_path, $cache_path) 
    {
        if (!file_exists(dirname($cache_path))) {
            wp_mkdir_p(dirname($cache_path));
        }

        // Aplicar encriptación básica al archivo cacheado
        $key = substr(hash('sha256', $_ENV['AUDIOCLAVE']), 0, 32);
        $iv = random_bytes(16);
        
        $source = fopen($source_path, 'rb');
        $cache = fopen($cache_path, 'wb');
        
        // Escribir IV al inicio del archivo
        fwrite($cache, $iv);
        
        while (!feof($source)) {
            $chunk = fread($source, self::BUFFER_SIZE);
            $encrypted = openssl_encrypt(
                $chunk,
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            fwrite($cache, $encrypted);
        }
        
        fclose($source);
        fclose($cache);
        
        // Establecer permisos restrictivos
        chmod($cache_path, 0600);
    }

    private function sendHeaders($file_path)
    {
        $size = filesize($file_path);
        $mime = mime_content_type($file_path);

        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, max-age=' . self::CACHE_TIME);
        header('Content-Length: ' . $size);
        
        // Prevenir cacheo en navegadores
        header('Expires: 0');
        header('Pragma: no-cache');
        
        // Seguridad adicional
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
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

    private function streamFileWithRangeSupport($file_path)
    {
        $fp = fopen($file_path, 'rb');
        $size = filesize($file_path);
        $length = $size;
        $start = 0;
        $end = $size - 1;

        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end = $end;

            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            if ($range[0] == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size - 1;
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
            header("Content-Range: bytes $start-$end/$size");
        }

        header('Content-Length: ' . $length);
        $buffer = 1024 * 8;
        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            echo fread($fp, $buffer);
            flush();
        }
        fclose($fp);
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
