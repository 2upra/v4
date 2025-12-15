<?php

/**
 * @deprecated Usar Kamples\Services\Social\ChatService en su lugar.
 * Este archivo se mantiene por compatibilidad. Será eliminado en futuras versiones.
 */

namespace Kamples\Services;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Services\Social\ChatService as NewChatService;

class ChatService
{
    private static ?ChatService $instancia = null;
    private NewChatService $servicio;

    public function __construct()
    {
        $this->servicio = NewChatService::obtenerInstancia();
    }

    public static function obtenerInstancia(): ChatService
    {
        if (self::$instancia === null) {
            self::$instancia = new ChatService();
        }
        return self::$instancia;
    }

    public function tieneClaveSecreta(): bool
    {
        return $this->servicio->tieneClaveSecreta();
    }

    public function generarToken(int $userId): string
    {
        return $this->servicio->generarToken($userId);
    }

    public function verificarToken(string $token, int $userId): bool
    {
        return $this->servicio->verificarToken($token, $userId);
    }

    public function obtenerInfoUsuario(int $userId): ?array
    {
        return $this->servicio->obtenerInfoUsuario($userId);
    }

    public function tiempoRelativo($fecha): string
    {
        return $this->servicio->tiempoRelativo($fecha);
    }

    public function guardarMensaje(int $emisor, int $receptor, string $mensaje, $adjunto = null, $metadata = null, ?int $conversacionId = null): int
    {
        return $this->servicio->guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata, $conversacionId);
    }

    public function obtenerConversacionId(int $user1, int $user2, bool $crearSiNoExiste = false): ?int
    {
        return $this->servicio->obtenerConversacionId($user1, $user2, $crearSiNoExiste);
    }

    public function obtenerMensajes(int $conversacionId, int $page = 1, int $perPage = 20): array
    {
        return $this->servicio->obtenerMensajes($conversacionId, $page, $perPage);
    }

    public function marcarComoLeido(int $conversacionId, int $lectorId): int
    {
        return $this->servicio->marcarComoLeido($conversacionId, $lectorId);
    }

    public function validarParticipante(int $conversacionId, int $userId): bool
    {
        return $this->servicio->validarParticipante($conversacionId, $userId);
    }
}
