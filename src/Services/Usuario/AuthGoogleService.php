<?php

namespace Kamples\Services\Usuario;

/**
 * Servicio de autenticacion con Google OAuth.
 * 
 * Responsabilidad unica: autenticacion y creacion de usuarios via Google.
 *
 * @since 1.0.0
 */
class AuthGoogleService
{
    private static ?AuthGoogleService $instancia = null;
    private \Logger $logger;
    private string $googleClientId = '84327954353-lb14ubs4vj4q2q57pt3sdfmapfhdq7ef.apps.googleusercontent.com';

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
     * Procesa el callback de Google OAuth.
     *
     * @param string $code Codigo de autorizacion.
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
            $this->logger->error('auth', 'Error en autenticacion Google', ['error' => $response->get_error_message()]);
            return false;
        }

        $tokenData = json_decode($response['body']);

        if (!isset($tokenData->access_token)) {
            $this->logger->error('auth', 'No se recibio token de acceso de Google');
            return false;
        }

        $userInfoResponse = wp_remote_get('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $tokenData->access_token);

        if (is_wp_error($userInfoResponse)) {
            $this->logger->error('auth', 'Error al obtener info de usuario de Google', ['error' => $userInfoResponse->get_error_message()]);
            return false;
        }

        $userInfo = json_decode($userInfoResponse['body']);

        if (!$userInfo || !isset($userInfo->email)) {
            $this->logger->error('auth', 'No se pudo obtener la informacion del usuario de Google');
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
     * @param array $googleUserInfo Informacion del usuario de Google.
     * @return int|false ID del usuario o false en caso de error.
     */
    public function autenticarConGoogle(array $googleUserInfo): int|false
    {
        $email = $googleUserInfo['email'];
        $name = $googleUserInfo['name'];

        $user = get_user_by('email', $email);

        if ($user) {
            /* Caso especial: redireccion de usuario 355 a 1 */
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
     * Obtiene la URL de OAuth de Google.
     *
     * @return string URL de autenticacion.
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
