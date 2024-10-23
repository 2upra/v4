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
// Manejador de la API REST
add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/2', [
        'methods' => 'GET',
        'callback' => function ($request) {
            $token = $request->get_param('token');
            if (empty($token)) {
                return new WP_Error('token_missing', 'Token no proporcionado', ['status' => 400]);
            }
            return AudioSecureHandler::getInstance()->streamAudio($token);
        },
        'permission_callback' => '__return_true'
    ]);
});

function handle_secure_audio_stream()
{
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        wp_send_json_error('Token no proporcionado');
    }

    AudioSecureHandler::getInstance()->streamAudio($token);
    exit;
}

add_action('wp_ajax_stream_secure_audio', 'handle_secure_audio_stream');
add_action('wp_ajax_nopriv_stream_secure_audio', 'handle_secure_audio_stream');

// da este error testkit1/:1  Uncaught (in promise) EncodingError: Failed to execute 'decodeAudioData' on 'BaseAudioContext': Unable to decode audio data

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
    private const CACHE_TIME = 86400; // 24 horas
    private const CHUNK_SIZE = 1048576; // 1MB para streaming
    private const CACHE_VERSION = 'v1'; // Para invalidar cache cuando sea necesario
    private const MAX_REQUESTS_PER_HOUR = 100;
    private const MEMORY_LIMIT = '256M';
    private const PROCESS_TIMEOUT = 300;
    private const BLOCK_SIZE = 16;

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Añadimos el método getSecureUrl que estabas usando
    public function getSecureUrl($audio_id)
    {
        $token = $this->generateSimpleToken($audio_id);
        $nonce = wp_create_nonce('audio_stream_nonce');
        return add_query_arg([
            'token' => $token,
            'security_nonce' => $nonce,
            '_' => time() // Prevenir cacheo de la URL
        ], rest_url('1/v1/2'));
    }

    private function generateSimpleToken($audio_id)
    {
        $data = [
            'id' => $audio_id,
            'exp' => time() + 3600, // 1 hora de expiración
            'nonce' => wp_create_nonce('audio_stream_' . $audio_id)
        ];

        return base64_encode(json_encode($data)) . '.' .
            hash_hmac('sha256', $audio_id, $_ENV['AUDIOCLAVE']);
    }

    private function verifySimpleToken($token)
    {
        list($payload, $signature) = explode('.', $token);
        $data = json_decode(base64_decode($payload), true);

        if (!$data || time() > $data['exp']) {
            return false;
        }

        $expected_signature = hash_hmac('sha256', $data['id'], $_ENV['AUDIOCLAVE']);

        if (hash_equals($expected_signature, $signature)) {
            return $data['id'];
        }
        return false;
    }


    private function streamWithHybridCache($audio_id)
    {
        guardarLog("streamWithHybridCache: Verificando referer y token para el audio ID: $audio_id");

        // Verificar referer y token primero
        if (!$this->validateRequest()) {
            guardarLog("streamWithHybridCache: Error - Acceso no autorizado"); // Log de error importante
            header('HTTP/1.1 403 Forbidden');
            exit('Acceso no autorizado');
        }

        $cached_path = $this->getServerCachePath($audio_id);
        guardarLog("streamWithHybridCache: Ruta de caché obtenida: $cached_path");

        // Verificar si existe el archivo en caché, si no, crearlo
        if (!file_exists($cached_path)) {
            $original_path = get_attached_file($audio_id);
            guardarLog("streamWithHybridCache: Verificando archivo original en: $original_path");

            if (!file_exists($original_path)) {
                guardarLog("streamWithHybridCache: Error - Audio no encontrado para el audio ID: $audio_id"); // Log de error importante
                wp_die('Audio no encontrado', 'Error', ['response' => 404]);
            }

            guardarLog("streamWithHybridCache: Procesando y almacenando archivo original en caché: $cached_path");
            $this->processAndCacheFile($original_path, $cached_path);
        } else {
            guardarLog("streamWithHybridCache: Archivo en caché encontrado: $cached_path");
        }

        // Generar ETag único basado en el archivo y el usuario
        $etag = '"' . md5($cached_path . wp_get_current_user()->ID) . '"';
        guardarLog("streamWithHybridCache: ETag generado: $etag");

        // Configurar headers de caché más estrictos
        header('Cache-Control: private, must-revalidate, max-age=' . self::CACHE_TIME);
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($cached_path)) . ' GMT');
        header('Vary: User-Agent, Cookie');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Content-Disposition: inline; filename="audio.mp3"'); // Previene descarga directa

        // Verificar si podemos usar la caché del navegador
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            guardarLog("streamWithHybridCache: ETag coincide - Enviando 304 Not Modified");
            header('HTTP/1.1 304 Not Modified');
            exit;
        }

        // Stream el audio
        guardarLog("streamWithHybridCache: Iniciando transmisión del archivo: $cached_path");
        $this->streamFileWithRanges($cached_path);
    }

    private function validateRequest()
    {
        // Verificar referer
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (!$referer || parse_url($referer, PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
            return false;
        }

        // Verificar que sea una petición AJAX
        if (
            empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
        ) {
            return false;
        }

        // Verificar nonce
        $nonce = $_GET['security_nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'audio_stream_nonce')) {
            return false;
        }

        return true;
    }


    public function streamAudio($token)
    {
        // Log para verificar el token recibido
        guardarLog("streamAudio: Iniciando verificación del token: $token");

        // Verificar token
        $audio_id = $this->verifySimpleToken($token);
        if (!$audio_id) {
            guardarLog("streamAudio: Error - Token inválido"); // Log de error importante
            wp_die('Token inválido', 'Error', ['response' => 403]);
        }

        guardarLog("streamAudio: Token válido: $audio_id"); // Log para confirmar que el token es válido

        // Log antes de usar la caché híbrida
        guardarLog("streamAudio: Iniciando transmisión de audio usando la caché híbrida para el audio ID: $audio_id");

        // Usar la caché híbrida
        $this->streamWithHybridCache($audio_id);

        // Log para confirmar que el proceso de transmisión ha terminado
        guardarLog("streamAudio: Transmisión de audio completada para el audio ID: $audio_id");
    }


    private function getServerCachePath($audio_id)
    {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/audio_cache';

        // Crear estructura de directorios más segura
        $hash = md5($audio_id);
        $sub_dir = substr($hash, 0, 2);
        $full_cache_dir = $cache_dir . '/' . $sub_dir;

        if (!file_exists($full_cache_dir)) {
            if (!wp_mkdir_p($full_cache_dir)) {
                throw new Exception('No se pudo crear el directorio de caché');
            }
            // Crear .htaccess para proteger el directorio
            file_put_contents($full_cache_dir . '/.htaccess', 'Deny from all');
        }

        return $full_cache_dir . '/audio_' . $hash . '_' . self::CACHE_VERSION . '.cache';
    }

    private function processAndCacheFile($source_path, $cache_path)
    {
        // Aumentar límites para archivos grandes
        ini_set('memory_limit', '256M');
        set_time_limit(300); // 5 minutos

        $key = substr(hash('sha256', $_ENV['AUDIOCLAVE']), 0, 32);
        $iv = random_bytes(16);

        $source = fopen($source_path, 'rb');
        $cache = fopen($cache_path, 'wb');

        // Escribir IV al inicio
        fwrite($cache, $iv);

        // Encriptar por bloques
        while (!feof($source)) {
            $chunk = fread($source, self::CHUNK_SIZE);
            $padded = $this->addPKCS7Padding($chunk, 16);
            $encrypted = openssl_encrypt(
                $padded,
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            fwrite($cache, $encrypted);
        }

        fclose($source);
        fclose($cache);
        chmod($cache_path, 0600);
    }

    private function addPKCS7Padding($data, $blockSize)
    {
        $pad = $blockSize - (strlen($data) % $blockSize);
        return $data . str_repeat(chr($pad), $pad);
    }

    private function streamFileWithRanges($file_path)
    {
        if (!file_exists($file_path)) {
            header('HTTP/1.1 404 Not Found');
            exit('Archivo no encontrado');
        }

        $size = filesize($file_path);
        $fp = fopen($file_path, 'rb');

        // Leer el IV del inicio del archivo
        $iv = fread($fp, 16);
        $size -= 16; // Ajustar el tamaño total

        // Configurar la desencriptación
        $key = substr(hash('sha256', $_ENV['AUDIOCLAVE']), 0, 32);
        $cipher = 'AES-256-CBC';

        header('Content-Type: audio/mpeg');
        header('Accept-Ranges: bytes');

        if (isset($_SERVER['HTTP_RANGE'])) {
            list($start, $end) = $this->parseRange($_SERVER['HTTP_RANGE'], $size);

            // Ajustar inicio y fin para la encriptación en bloques
            $blockSize = 16;
            $adjustedStart = $start - ($start % $blockSize);
            $adjustedEnd = min($end + ($blockSize - ($end % $blockSize)), $size - 1);

            fseek($fp, $adjustedStart + 16); // +16 por el IV

            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$size");
            header('Content-Length: ' . ($end - $start + 1));

            // Leer y desencriptar por bloques
            $buffer = '';
            while (ftell($fp) <= $adjustedEnd + 16) {
                $chunk = fread($fp, self::CHUNK_SIZE);
                if ($chunk === false) break;

                $decrypted = openssl_decrypt(
                    $chunk,
                    $cipher,
                    $key,
                    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                    $iv
                );

                // Recortar al rango exacto solicitado
                if (strlen($buffer) === 0 && $start > $adjustedStart) {
                    $decrypted = substr($decrypted, $start - $adjustedStart);
                }
                if (ftell($fp) > $end + 16) {
                    $decrypted = substr($decrypted, 0, $end - $start + 1);
                }

                echo $decrypted;
                flush();
            }
        } else {
            header('Content-Length: ' . $size);

            // Streaming completo
            while (!feof($fp)) {
                $chunk = fread($fp, self::CHUNK_SIZE);
                if ($chunk === false) break;

                $decrypted = openssl_decrypt(
                    $chunk,
                    $cipher,
                    $key,
                    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                    $iv
                );

                echo $decrypted;
                flush();
            }
        }

        fclose($fp);
    }


    private function parseRange($range, $size)
    {
        $range = str_replace('bytes=', '', $range);
        list($start, $end) = explode('-', $range);
        $end = (empty($end)) ? $size - 1 : min((int)$end, $size - 1);
        $start = (empty($start)) ? 0 : min((int)$start, $size - 1);
        return [$start, $end];
    }
}


// Inicialización y hooks
