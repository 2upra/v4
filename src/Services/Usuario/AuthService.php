<?php

namespace Kamples\Services\Usuario;

/**
 * Servicio fachada de autenticacion.
 * 
 * Orquesta los servicios especializados:
 * - AuthCredencialesService: Login/Registro local
 * - AuthGoogleService: OAuth de Google
 * - AuthTokenService: Tokens de sesion y Firebase
 *
 * @since 1.0.0
 */
class AuthService
{
    private static ?AuthService $instancia = null;
    private AuthCredencialesService $credencialesService;
    private AuthGoogleService $googleService;
    private AuthTokenService $tokenService;

    private function __construct()
    {
        $this->credencialesService = AuthCredencialesService::obtenerInstancia();
        $this->googleService = AuthGoogleService::obtenerInstancia();
        $this->tokenService = AuthTokenService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /* 
     * Metodos de credenciales locales (delegados a AuthCredencialesService)
     */

    public function iniciarSesion(string $username, string $password): \WP_User|\WP_Error
    {
        return $this->credencialesService->iniciarSesion($username, $password);
    }

    public function registrarUsuario(string $nombreUsuario, string $correo, string $contrasena, string $tipoUsuario = 'fan'): int|\WP_Error
    {
        return $this->credencialesService->registrarUsuario($nombreUsuario, $correo, $contrasena, $tipoUsuario);
    }

    /* 
     * Metodos de Google OAuth (delegados a AuthGoogleService)
     */

    public function procesarGoogleCallback(string $code): array|false
    {
        return $this->googleService->procesarGoogleCallback($code);
    }

    public function autenticarConGoogle(array $googleUserInfo): int|false
    {
        return $this->googleService->autenticarConGoogle($googleUserInfo);
    }

    public function obtenerUrlGoogleOAuth(): string
    {
        return $this->googleService->obtenerUrlGoogleOAuth();
    }

    /* 
     * Metodos de tokens (delegados a AuthTokenService)
     */

    public function generarTokenSeguro(int $userId): string|false
    {
        return $this->tokenService->generarTokenSeguro($userId);
    }

    public function verificarToken(string $token): int|false
    {
        return $this->tokenService->verificarToken($token);
    }

    public function guardarTokenFirebase(int $userId, string $firebaseToken): bool
    {
        return $this->tokenService->guardarTokenFirebase($userId, $firebaseToken);
    }

    public function guardarVersionApp(int $userId, string $versionName, int $versionCode): void
    {
        $this->tokenService->guardarVersionApp($userId, $versionName, $versionCode);
    }

    public function esAppElectron(): bool
    {
        return $this->tokenService->esAppElectron();
    }

    public function registrarUserAgent(string $userAgent, string $tipo): void
    {
        $this->tokenService->registrarUserAgent($userAgent, $tipo);
    }
}
