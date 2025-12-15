<?php

/**
 * Servicio de tokens para el sistema de chat.
 * 
 * Maneja la generación y verificación de tokens de seguridad
 * para autenticación en el chat.
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ChatTokenService
{
    private static ?ChatTokenService $instancia = null;
    private string $secretKey;
    private \Logger $logger;

    private function __construct()
    {
        $this->secretKey = $_ENV['GALLEKEY'] ?? '';
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): ChatTokenService
    {
        if (self::$instancia === null) {
            self::$instancia = new ChatTokenService();
        }
        return self::$instancia;
    }

    /**
     * Verificar si la clave secreta está configurada.
     *
     * @return bool
     */
    public function tieneClaveSecreta(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Generar un token de seguridad para el usuario.
     *
     * @param int $userId ID del usuario.
     * @return string Token SHA256 HMAC.
     */
    public function generarToken(int $userId): string
    {
        if (!$this->tieneClaveSecreta()) {
            $this->logger->error('chat', 'La clave secreta GALLEKEY no está definida.');
            return '';
        }

        $tiempoRedondeado = floor(time() / 86400); // Token válido por día

        return hash_hmac('sha256', $userId . $tiempoRedondeado, $this->secretKey);
    }

    /**
     * Verificar si un token es válido.
     * 
     * Acepta tokens del día actual y del día anterior para evitar problemas
     * en el cambio de día.
     *
     * @param string $token  Token recibido.
     * @param int    $userId ID del usuario.
     * @return bool True si el token es válido.
     */
    public function verificarToken(string $token, int $userId): bool
    {
        if (!$this->tieneClaveSecreta() || empty($token)) {
            return false;
        }

        $currentTime = time();
        $roundedTime = floor($currentTime / 86400);

        // Generar token esperado para hoy
        $expectedToken = hash_hmac('sha256', $userId . $roundedTime, $this->secretKey);

        // Generar token esperado para ayer (tolerancia de cambio de día)
        $previousRoundedTime = $roundedTime - 1;
        $previousExpectedToken = hash_hmac('sha256', $userId . $previousRoundedTime, $this->secretKey);

        // Verificar coincidencia usando comparación segura
        if (hash_equals($expectedToken, $token) || hash_equals($previousExpectedToken, $token)) {
            return true;
        }

        $this->logger->warning('chat', "Token inválido para usuario $userId");
        return false;
    }
}
