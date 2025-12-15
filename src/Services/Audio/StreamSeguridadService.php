<?php

/**
 * Servicio de seguridad para streaming de audio
 * 
 * Gestiona rate limiting, bloqueo de IPs y verificacion de headers
 *
 * @package Kamples\Services\Audio
 * @since 1.0.0
 */

namespace Kamples\Services\Audio;

class StreamSeguridadService
{
    private static ?StreamSeguridadService $instancia = null;

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
     * Verifica rate limiting para una IP
     */
    public function verificarRateLimiting(string $ip): bool
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
    public function incrementarIntentosFallidos(string $ip): void
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
     * Verifica headers de la solicitud
     */
    public function verificarHeaders(): bool
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
     * Desbloquea una IP manualmente
     *
     * @param string $ip IP a desbloquear
     */
    public function desbloquearIp(string $ip): void
    {
        delete_transient('blocked_ip_' . $ip);
        delete_transient('failed_attempts_' . $ip);
    }

    /**
     * Verifica si una IP esta bloqueada
     *
     * @param string $ip IP a verificar
     * @return bool True si esta bloqueada
     */
    public function ipEstaBloqueada(string $ip): bool
    {
        return get_transient('blocked_ip_' . $ip) !== false;
    }

    /**
     * Bloquea acceso directo a archivos de audio
     */
    public function bloquearAccesoDirecto(StreamTokenService $tokenService): void
    {
        if (strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/') !== false) {
            $token = $_GET['token'] ?? $_COOKIE['audio_token'] ?? null;
            if (!$token || !$tokenService->verificarToken($token, $this)) {
                wp_die('Acceso denegado', 'Acceso denegado', ['response' => 403]);
            }
        }
    }
}
