<?php

/**
 * Servicio para el sistema de chat en tiempo real.
 * 
 * Encapsula la lógica de seguridad (tokens), mensajería y utilidades
 * relacionadas con el chat Galle v2.
 *
 * @package Theme_V4
 * @since 1.0.0
 */

namespace Theme\V4\Services;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ChatService
{
    /**
     * Clave secreta para generación de tokens.
     * 
     * @var string
     */
    private string $secretKey;

    /**
     * Instancia del Logger.
     * 
     * @var \Logger|null
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Obtener clave secreta del entorno
        $this->secretKey = $_ENV['GALLEKEY'] ?? '';

        // Inicializar logger si está disponible
        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
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
            $this->log('Error: La clave secreta GALLEKEY no está definida.', 'ERROR');
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

        $this->log("Token inválido para usuario $userId. Recibido: $token, Esperado: $expectedToken", 'WARNING');
        return false;
    }

    /**
     * Obtener información básica de un usuario para el chat.
     *
     * @param int $userId ID del usuario.
     * @return array|null Datos del usuario o null si no existe.
     */
    public function obtenerInfoUsuario(int $userId): ?array
    {
        $usuario = get_userdata($userId);

        if (!$usuario) {
            return null;
        }

        $nombre = !empty($usuario->display_name) ? $usuario->display_name : $usuario->user_login;
        $imagen = function_exists('imagenPerfil') ? imagenPerfil($userId) : '';

        return [
            'id' => $userId,
            'nombre' => $nombre,
            'imagen' => $imagen ?: 'ruta_por_defecto.jpg', // Placeholder
        ];
    }

    /**
     * Formatear tiempo relativo (hace X minutos).
     *
     * @param string|int $fecha Timestamp o string de fecha.
     * @return string Tiempo relativo.
     */
    public function tiempoRelativo($fecha): string
    {
        $timestamp = is_numeric($fecha) ? (int)$fecha : strtotime($fecha);
        $diferencia = time() - $timestamp;

        if ($diferencia < 60) {
            return 'unos segundos';
        } elseif ($diferencia < 3600) {
            $minutos = floor($diferencia / 60);
            return "$minutos minuto" . ($minutos > 1 ? 's' : '');
        } elseif ($diferencia < 86400) {
            $horas = floor($diferencia / 3600);
            return "$horas hora" . ($horas > 1 ? 's' : '');
        } elseif ($diferencia < 604800) {
            $dias = floor($diferencia / 86400);
            return "$dias día" . ($dias > 1 ? 's' : '');
        } else {
            $semanas = floor($diferencia / 604800);
            return "$semanas semana" . ($semanas > 1 ? 's' : '');
        }
    }

    /**
     * Registrar mensaje en el log del sistema.
     *
     * @param string $mensaje Mensaje a loggear.
     * @param string $nivel   Nivel de log (INFO, ERROR, WARNING).
     */
    private function log(string $mensaje, string $nivel = 'INFO'): void
    {
        if ($this->logger) {
            $metodo = strtolower($nivel);
            if (method_exists($this->logger, $metodo)) {
                $this->logger->$metodo('chat', $mensaje);
                return;
            }
        }

        // Fallback si no hay logger configurado
        if (function_exists('chatLog')) {
            chatLog($mensaje);
        } else {
            error_log("[ChatService] $mensaje");
        }
    }
}
