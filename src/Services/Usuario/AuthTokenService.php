<?php

namespace Kamples\Services\Usuario;

/**
 * Servicio de gestion de tokens de sesion.
 * 
 * Responsabilidad unica: tokens de sesion, Firebase y datos de app.
 *
 * @since 1.0.0
 */
class AuthTokenService
{
    private static ?AuthTokenService $instancia = null;
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
     * Genera un token seguro para sesion.
     *
     * @param int $userId ID del usuario.
     * @return string|false Token generado o false en caso de error.
     */
    public function generarTokenSeguro(int $userId): string|false
    {
        $token = bin2hex(random_bytes(32));
        $expiration = time() + 36000; /* 10 horas */

        delete_user_meta($userId, 'session_token');
        delete_user_meta($userId, 'session_token_expiration');

        /* Verificar eliminacion */
        $existingToken = get_user_meta($userId, 'session_token', true);
        $existingExpiration = get_user_meta($userId, 'session_token_expiration', true);

        if ($existingToken || $existingExpiration) {
            $this->logger->error('auth', 'No se pudieron eliminar tokens duplicados');
            return false;
        }

        update_user_meta($userId, 'session_token', $token);
        update_user_meta($userId, 'session_token_expiration', $expiration);

        $this->logger->debug('auth', "Token generado para usuario {$userId}");
        return $token;
    }

    /**
     * Verifica un token de sesion.
     *
     * @param string $token Token a verificar.
     * @return int|false ID del usuario o false si es invalido.
     */
    public function verificarToken(string $token): int|false
    {
        global $wpdb;

        $userId = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id
             FROM {$wpdb->usermeta}
             WHERE meta_key = 'session_token'
             AND meta_value = %s
             AND CAST((
                 SELECT MAX(meta_value)
                 FROM {$wpdb->usermeta}
                 WHERE user_id = {$wpdb->usermeta}.user_id
                 AND meta_key = 'session_token_expiration'
             ) AS UNSIGNED) > %d",
            $token,
            time()
        ));

        if ($userId) {
            $this->logger->debug('auth', "Token valido para usuario {$userId}");
            return (int) $userId;
        }

        $this->logger->warning('auth', 'Token invalido');
        return false;
    }

    /**
     * Guarda o actualiza el token de Firebase para notificaciones push.
     *
     * @param int $userId ID del usuario.
     * @param string $firebaseToken Token de Firebase.
     * @return bool True si se guardo correctamente.
     */
    public function guardarTokenFirebase(int $userId, string $firebaseToken): bool
    {
        if (!get_userdata($userId)) {
            $this->logger->error('auth', "Usuario invalido: {$userId}");
            return false;
        }

        $currentToken = get_user_meta($userId, 'firebase_token', true);

        if ($currentToken !== $firebaseToken) {
            $result = update_user_meta($userId, 'firebase_token', $firebaseToken);
            $this->logger->info('auth', "Token Firebase actualizado para usuario {$userId}");
            return (bool) $result;
        }

        return true;
    }

    /**
     * Guarda la version de la app del usuario.
     *
     * @param int $userId ID del usuario.
     * @param string $versionName Nombre de la version.
     * @param int $versionCode Codigo de la version.
     */
    public function guardarVersionApp(int $userId, string $versionName, int $versionCode): void
    {
        if ($versionName) {
            $current = get_user_meta($userId, 'app_version_name', true);
            if ($current !== $versionName) {
                update_user_meta($userId, 'app_version_name', $versionName);
            }
        }

        if ($versionCode) {
            $current = get_user_meta($userId, 'app_version_code', true);
            if ($current !== $versionCode) {
                update_user_meta($userId, 'app_version_code', $versionCode);
            }
        }
    }

    /**
     * Verifica si la peticion viene de la app Electron.
     *
     * @return bool True si es app Electron.
     */
    public function esAppElectron(): bool
    {
        return isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true';
    }

    /**
     * Registra un user agent para analisis.
     *
     * @param string $userAgent User agent string.
     * @param string $tipo Tipo de deteccion.
     */
    public function registrarUserAgent(string $userAgent, string $tipo): void
    {
        $this->logger->info('auth', "UserAgent detectado ({$tipo}): {$userAgent}");
    }
}
