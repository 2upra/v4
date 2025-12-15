<?php

/**
 * Servicio para el sistema de chat en tiempo real (Fachada).
 * 
 * Orquesta las operaciones de chat delegando a servicios especializados.
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ChatService
{
    private static ?ChatService $instancia = null;
    private ChatTokenService $tokenService;
    private ChatMensajeService $mensajeService;
    private ChatConversacionService $conversacionService;
    private \Logger $logger;

    private function __construct()
    {
        $this->tokenService = ChatTokenService::obtenerInstancia();
        $this->mensajeService = ChatMensajeService::obtenerInstancia();
        $this->conversacionService = ChatConversacionService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): ChatService
    {
        if (self::$instancia === null) {
            self::$instancia = new ChatService();
        }
        return self::$instancia;
    }

    /**
     * Verificar si la clave secreta está configurada.
     */
    public function tieneClaveSecreta(): bool
    {
        return $this->tokenService->tieneClaveSecreta();
    }

    /**
     * Generar un token de seguridad para el usuario.
     */
    public function generarToken(int $userId): string
    {
        return $this->tokenService->generarToken($userId);
    }

    /**
     * Verificar si un token es válido.
     */
    public function verificarToken(string $token, int $userId): bool
    {
        return $this->tokenService->verificarToken($token, $userId);
    }

    /**
     * Obtener información básica de un usuario para el chat.
     */
    public function obtenerInfoUsuario(int $userId): ?array
    {
        return $this->conversacionService->obtenerInfoUsuario($userId);
    }

    /**
     * Formatear tiempo relativo (hace X minutos).
     */
    public function tiempoRelativo($fecha): string
    {
        return $this->mensajeService->tiempoRelativo($fecha);
    }

    /**
     * Guardar un nuevo mensaje en la base de datos.
     */
    public function guardarMensaje(int $emisor, int $receptor, string $mensaje, $adjunto = null, $metadata = null, ?int $conversacionId = null): int
    {
        return $this->mensajeService->guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata, $conversacionId);
    }

    /**
     * Obtener o crear una conversación entre dos usuarios.
     */
    public function obtenerConversacionId(int $user1, int $user2, bool $crearSiNoExiste = false): ?int
    {
        return $this->conversacionService->obtenerConversacionId($user1, $user2, $crearSiNoExiste);
    }

    /**
     * Obtener mensajes de una conversación.
     */
    public function obtenerMensajes(int $conversacionId, int $page = 1, int $perPage = 20): array
    {
        return $this->mensajeService->obtenerMensajes($conversacionId, $page, $perPage);
    }

    /**
     * Marcar mensajes como leídos.
     */
    public function marcarComoLeido(int $conversacionId, int $lectorId): int
    {
        return $this->mensajeService->marcarComoLeido($conversacionId, $lectorId);
    }

    /**
     * Verificar si un usuario pertenece a una conversación.
     */
    public function validarParticipante(int $conversacionId, int $userId): bool
    {
        return $this->conversacionService->validarParticipante($conversacionId, $userId);
    }
}
