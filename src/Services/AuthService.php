<?php

namespace Kamples\Services;

use Kamples\Services\Usuario\AuthService as NuevoAuthService;

/**
 * @deprecated Usar Kamples\Services\Usuario\AuthService
 * 
 * Wrapper de compatibilidad. Redirige todas las llamadas al nuevo servicio.
 */
class AuthService
{
    private static ?AuthService $instancia = null;
    private NuevoAuthService $nuevoServicio;

    public function __construct()
    {
        $this->nuevoServicio = NuevoAuthService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function iniciarSesion(string $username, string $password): \WP_User|\WP_Error
    {
        return $this->nuevoServicio->iniciarSesion($username, $password);
    }

    public function registrarUsuario(string $nombreUsuario, string $correo, string $contrasena, string $tipoUsuario = 'fan'): int|\WP_Error
    {
        return $this->nuevoServicio->registrarUsuario($nombreUsuario, $correo, $contrasena, $tipoUsuario);
    }

    public function procesarGoogleCallback(string $code): array|false
    {
        return $this->nuevoServicio->procesarGoogleCallback($code);
    }

    public function autenticarConGoogle(array $googleUserInfo): int|false
    {
        return $this->nuevoServicio->autenticarConGoogle($googleUserInfo);
    }

    public function obtenerUrlGoogleOAuth(): string
    {
        return $this->nuevoServicio->obtenerUrlGoogleOAuth();
    }

    public function generarTokenSeguro(int $userId): string|false
    {
        return $this->nuevoServicio->generarTokenSeguro($userId);
    }

    public function verificarToken(string $token): int|false
    {
        return $this->nuevoServicio->verificarToken($token);
    }

    public function guardarTokenFirebase(int $userId, string $firebaseToken): bool
    {
        return $this->nuevoServicio->guardarTokenFirebase($userId, $firebaseToken);
    }

    public function guardarVersionApp(int $userId, string $versionName, int $versionCode): void
    {
        $this->nuevoServicio->guardarVersionApp($userId, $versionName, $versionCode);
    }

    public function esAppElectron(): bool
    {
        return $this->nuevoServicio->esAppElectron();
    }

    public function registrarUserAgent(string $userAgent, string $tipo): void
    {
        $this->nuevoServicio->registrarUserAgent($userAgent, $tipo);
    }
}
