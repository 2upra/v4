<?php

namespace Kamples\Services\Usuario;

/**
 * Servicio de autenticacion con credenciales locales.
 * 
 * Responsabilidad unica: login y registro con usuario/password.
 *
 * @since 1.0.0
 */
class AuthCredencialesService
{
    private static ?AuthCredencialesService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicia sesion con credenciales de usuario.
     *
     * @param string $username Nombre de usuario.
     * @param string $password Contraseña.
     * @return \WP_User|\WP_Error Usuario o error.
     */
    public function iniciarSesion(string $username, string $password): \WP_User|\WP_Error
    {
        $user = wp_signon([
            'user_login' => sanitize_user($username),
            'user_password' => $password
        ], false);

        if (!is_wp_error($user)) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            $this->logger->info('auth', "Usuario {$user->ID} inicio sesion correctamente");
        }

        return $user;
    }

    /**
     * Registra un nuevo usuario.
     *
     * @param string $nombreUsuario Nombre de usuario.
     * @param string $correo Correo electronico.
     * @param string $contrasena Contraseña.
     * @param string $tipoUsuario Tipo de usuario (artista/fan).
     * @return int|\WP_Error ID del usuario o error.
     */
    public function registrarUsuario(string $nombreUsuario, string $correo, string $contrasena, string $tipoUsuario = 'fan'): int|\WP_Error
    {
        $nombreUsuario = str_replace(' ', '', sanitize_user($nombreUsuario, true));
        $correo = sanitize_email($correo);
        $tipoUsuario = sanitize_text_field($tipoUsuario);

        if (username_exists($nombreUsuario)) {
            return new \WP_Error('username_exists', 'El nombre de usuario ya esta en uso.');
        }

        if (email_exists($correo)) {
            return new \WP_Error('email_exists', 'El correo electronico ya esta en uso.');
        }

        $userId = wp_create_user($nombreUsuario, $contrasena, $correo);

        if (!is_wp_error($userId)) {
            update_user_meta($userId, 'tipo_usuario', $tipoUsuario);

            if ($tipoUsuario === 'fan') {
                update_user_meta($userId, 'fan', true);
            }

            $this->logger->info('auth', "Nuevo usuario registrado: {$userId}");
        }

        return $userId;
    }
}
