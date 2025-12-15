<?php

/**
 * Servicio de tokens para streaming de audio
 * 
 * Genera y verifica tokens de acceso para archivos de audio
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class StreamTokenService
{
    private static ?StreamTokenService $instancia = null;
    private const ENABLE_BROWSER_CACHE = true;

    private function __construct() {}

    /**
     * Obtiene la instancia unica del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Verifica si el cache del navegador esta habilitado
     */
    public function cacheHabilitado(): bool
    {
        return self::ENABLE_BROWSER_CACHE;
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
     * Verifica si un token de audio es valido
     *
     * @param string $token Token a verificar
     * @param StreamSeguridadService $seguridadService Servicio de seguridad
     * @return bool True si el token es valido
     */
    public function verificarToken(string $token, StreamSeguridadService $seguridadService): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        /* Verificar rate limiting */
        if (!$seguridadService->verificarRateLimiting($ip)) {
            header("HTTP/1.1 429 Too Many Requests");
            return false;
        }

        if (empty($token)) {
            $seguridadService->incrementarIntentosFallidos($ip);
            header("HTTP/1.1 401 Unauthorized");
            return false;
        }

        /* Verificar referer y headers */
        if (!$seguridadService->verificarHeaders()) {
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
     * Extrae el audioId de un token
     *
     * @param string $token Token codificado
     * @return string|null ID del audio o null
     */
    public function extraerAudioId(string $token): ?string
    {
        $parts = explode('|', base64_decode($token));
        return $parts[0] ?? null;
    }
}
