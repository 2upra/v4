<?php

/**
 * Servicio de streaming de audio
 * 
 * Gestiona tokens de acceso, verificación y streaming de archivos de audio
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class StreamService
{
    private static ?StreamService $instancia = null;
    private const ENABLE_BROWSER_CACHE = true;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->inicializarAutenticacion();
    }

    /**
     * Obtiene la instancia única del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa la autenticación para solicitudes AJAX/REST
     */
    private function inicializarAutenticacion(): void
    {
        add_action('init', function () {
            if (!defined('DOING_AJAX') && !defined('REST_REQUEST')) {
                return;
            }
            $userId = wp_validate_auth_cookie('', 'logged_in');
            if ($userId) {
                wp_set_current_user($userId);
            }
        });

        add_action('init', [$this, 'bloquearAccesoDirecto']);
    }

    /**
     * Bloquea acceso directo a archivos de audio
     */
    public function bloquearAccesoDirecto(): void
    {
        if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/') !== false) {
            $token = $_GET['token'] ?? $_COOKIE['audio_token'] ?? null;
            if (!$token || !$this->verificarToken($token)) {
                wp_die('Acceso denegado', 'Acceso denegado', ['response' => 403]);
            }
        }
    }

    /**
     * Genera una URL segura para un audio
     *
     * @param string|int $audioId ID del archivo de audio
     * @return string|\WP_Error URL segura o error
     */
    public function generarUrlSegura($audioId)
    {
        $token = $this->generarToken($audioId);
        if (!$token) {
            return new \WP_Error('invalid_audio_id', 'Audio ID inválido.');
        }

        $nonce = wp_create_nonce('wp_rest');
        return site_url("/wp-json/1/v1/2?token=" . urlencode($token) . '&_wpnonce=' . $nonce);
    }

    /**
     * Genera un token de acceso para un audio
     *
     * @param string|int $audioId ID del audio
     * @return string|false Token generado o false si hay error
     */
    public function generarToken($audioId)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', (string)$audioId)) {
            return false;
        }

        $audioClave = $_ENV['AUDIOCLAVE'] ?? '';

        if (self::ENABLE_BROWSER_CACHE) {
            /* Token persistente para cache del navegador */
            $expiration = strtotime('2030-12-31');
            $userIp = 'cached';
            $uniqueId = md5($audioId . $audioClave);
            $maxUsos = 999999;
        } else {
            /* Token temporal para modo sin cache */
            $expiration = time() + 3600;
            $userIp = $_SERVER['REMOTE_ADDR'];
            $uniqueId = uniqid('', true);
            $maxUsos = 3;

            set_transient('audio_token_' . $uniqueId, $maxUsos, 3600);
        }

        $data = $audioId . '|' . $expiration . '|' . $userIp . '|' . $uniqueId;
        $signature = hash_hmac('sha256', $data, $audioClave);

        return base64_encode($data . '|' . $maxUsos . '|' . $signature);
    }

    /**
     * Verifica si un token de audio es válido
     *
     * @param string $token Token a verificar
     * @return bool True si el token es válido
     */
    public function verificarToken(string $token): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        /* Verificar rate limiting */
        if (!$this->verificarRateLimiting($ip)) {
            header("HTTP/1.1 429 Too Many Requests");
            return false;
        }

        if (empty($token)) {
            $this->incrementarIntentosFallidos($ip);
            header("HTTP/1.1 401 Unauthorized");
            return false;
        }

        /* Verificar referer y headers */
        if (!$this->verificarHeaders()) {
            return false;
        }

        /* Decodificar y validar token */
        $decoded = base64_decode($token);
        $parts = explode('|', $decoded);

        if (count($parts) !== 6) {
            return false;
        }

        list($audioId, $expiration, $userIp, $uniqueId, $maxUsos, $signature) = $parts;
        $audioClave = $_ENV['AUDIOCLAVE'] ?? '';

        if (self::ENABLE_BROWSER_CACHE) {
            return $this->verificarTokenConCache($audioId, $expiration, $userIp, $uniqueId, $signature, $audioClave);
        } else {
            return $this->verificarTokenSinCache($audioId, $expiration, $userIp, $uniqueId, $signature, $audioClave);
        }
    }

    /**
     * Verifica token con cache de navegador habilitado
     */
    private function verificarTokenConCache(
        string $audioId,
        string $expiration,
        string $userIp,
        string $uniqueId,
        string $signature,
        string $audioClave
    ): bool {
        $sessionKey = 'audio_session_' . $audioId . '_' . $_SERVER['REMOTE_ADDR'];
        $cacheKey = 'audio_access_' . $audioId . '_' . $_SERVER['REMOTE_ADDR'];

        $data = $audioId . '|' . $expiration . '|' . $userIp . '|' . $uniqueId;
        $expectedSignature = hash_hmac('sha256', $data, $audioClave);

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $currentSession = get_transient($sessionKey);

        if ($currentSession === false) {
            set_transient($sessionKey, true, 3600);
            set_transient($cacheKey, 1, 3600);
            return true;
        }

        $accessCount = get_transient($cacheKey);

        if (
            $accessCount !== false &&
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' &&
            parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')
        ) {
            set_transient($cacheKey, $accessCount + 1, 3600);
            return true;
        }

        return false;
    }

    /**
     * Verifica token sin cache de navegador
     */
    private function verificarTokenSinCache(
        string $audioId,
        string $expiration,
        string $userIp,
        string $uniqueId,
        string $signature,
        string $audioClave
    ): bool {
        if ($_SERVER['REMOTE_ADDR'] !== $userIp) {
            return false;
        }

        if (time() > (int)$expiration) {
            return false;
        }

        $usosRestantes = get_transient('audio_token_' . $uniqueId);
        if ($usosRestantes === false || $usosRestantes <= 0) {
            return false;
        }

        $data = $audioId . '|' . $expiration . '|' . $userIp . '|' . $uniqueId;
        $expectedSignature = hash_hmac('sha256', $data, $audioClave);

        if (hash_equals($expectedSignature, $signature)) {
            $this->decrementarUsosToken($uniqueId);
            return true;
        }

        return false;
    }

    /**
     * Verifica headers de la solicitud
     */
    private function verificarHeaders(): bool
    {
        if (!isset($_SERVER['HTTP_REFERER']) || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return false;
        }

        $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $currentHost = $_SERVER['HTTP_HOST'];

        if ($refererHost !== $currentHost) {
            return false;
        }

        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            return false;
        }

        return true;
    }

    /**
     * Verifica rate limiting para una IP
     */
    private function verificarRateLimiting(string $ip): bool
    {
        $blockedKey = 'blocked_ip_' . $ip;

        if (get_transient($blockedKey) !== false) {
            return false;
        }

        return true;
    }

    /**
     * Incrementa intentos fallidos y bloquea si es necesario
     */
    private function incrementarIntentosFallidos(string $ip): void
    {
        $limit = 10;
        $timeWindow = 10;
        $blockDuration = 300;

        $attemptsKey = 'failed_attempts_' . $ip;
        $attempts = get_transient($attemptsKey) ?: 0;
        $attempts++;

        set_transient($attemptsKey, $attempts, $timeWindow);

        if ($attempts >= $limit) {
            set_transient('blocked_ip_' . $ip, true, $blockDuration);
        }
    }

    /**
     * Decrementa los usos disponibles de un token
     */
    private function decrementarUsosToken(string $uniqueId): void
    {
        if (self::ENABLE_BROWSER_CACHE) {
            return;
        }

        $key = 'audio_token_' . $uniqueId;
        $usosRestantes = get_transient($key);

        if ($usosRestantes !== false && $usosRestantes > 0) {
            $usosRestantes--;
            if ($usosRestantes > 0) {
                set_transient($key, $usosRestantes, get_option('transient_timeout_' . $key));
            } else {
                delete_transient($key);
            }
        }
    }

    /**
     * Realiza el streaming del archivo de audio
     *
     * @param string $token Token de acceso
     * @return \WP_Error|void Error si falla, o realiza streaming y termina
     */
    public function streamAudio(string $token)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $parts = explode('|', base64_decode($token));
        $audioId = $parts[0];

        $uploadDir = wp_upload_dir();
        $cacheDir = $uploadDir['basedir'] . '/audio_cache';

        if (!file_exists($cacheDir)) {
            wp_mkdir_p($cacheDir);
        }

        $cacheFile = $cacheDir . '/audio_' . $audioId . '.cache';

        /* Cargar desde cache o archivo original */
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 24 * 60 * 60)) {
            $file = $cacheFile;
        } else {
            $originalFile = get_attached_file($audioId);
            if (!file_exists($originalFile)) {
                return new \WP_Error('no_audio', 'Archivo de audio no encontrado.', ['status' => 404]);
            }

            if (!@copy($originalFile, $cacheFile)) {
                return new \WP_Error('copy_failed', 'Error al copiar el archivo de audio al caché.', ['status' => 500]);
            }

            $file = $cacheFile;
        }

        $this->enviarArchivoAudio($file, $audioId);
    }

    /**
     * Envía el archivo de audio con soporte para range requests
     */
    private function enviarArchivoAudio(string $file, $audioId): void
    {
        $fp = @fopen($file, 'rb');
        if (!$fp) {
            wp_die('No se pudo abrir el archivo de audio.');
        }

        $size = filesize($file);
        $length = $size;
        $start = 0;
        $end = $size - 1;

        $etag = '"' . md5($file . filemtime($file)) . '"';

        /* Headers básicos */
        header('Content-Type: ' . get_post_mime_type($audioId));
        header('Accept-Ranges: bytes');

        /* Headers de cache */
        if (self::ENABLE_BROWSER_CACHE) {
            $cacheTime = 60 * 60 * 2190;
            header('Cache-Control: public, max-age=' . $cacheTime);
            header('Pragma: public');
            header('ETag: ' . $etag);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');

            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        } else {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
        }

        /* Manejar Range Requests */
        if (isset($_SERVER['HTTP_RANGE'])) {
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            if ($range == '-') {
                $start = $size - substr($range, 1);
            } else {
                $rangeParts = explode('-', $range);
                $start = (int)$rangeParts[0];
                $end = isset($rangeParts[1]) && is_numeric($rangeParts[1]) ? (int)$rangeParts[1] : $size;
            }

            $end = min($end, $size - 1);

            if ($start > $end || $start > $size - 1) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }

        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);

        /* Streaming con rate limiting */
        $buffer = 1024 * 8;
        $sleep = 1000;
        $sent = 0;

        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            echo fread($fp, $buffer);
            $sent += $buffer;
            flush();

            if ($sent >= 64 * 1024) {
                usleep($sleep);
                $sent = 0;
            }
        }

        fclose($fp);
        exit();
    }

    /**
     * Verifica si un usuario es admin o tiene suscripción pro
     *
     * @param int $userId ID del usuario
     * @return bool True si es admin o pro
     */
    public function usuarioEsAdminOPro(int $userId): bool
    {
        if (empty($userId)) {
            return false;
        }

        $user = get_user_by('id', $userId);
        if (!$user || empty($user->roles)) {
            return false;
        }

        if (in_array('administrator', (array)$user->roles)) {
            return true;
        }

        $isPro = get_user_meta($userId, 'pro', true);
        return !empty($isPro);
    }

    /**
     * Limpia el cache de audio
     */
    public function limpiarCache(): void
    {
        $uploadDir = wp_upload_dir();
        $cacheDir = $uploadDir['basedir'] . '/audio_cache';

        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            $now = time();

            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) > 7 * 24 * 60 * 60)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Programa la limpieza automática del cache
     */
    public function programarLimpiezaCache(): void
    {
        if (!wp_next_scheduled('audio_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'audio_cache_cleanup');
        }
    }
}
