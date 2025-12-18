<?php

/**
 * Controlador de Autenticación.
 * 
 * Maneja endpoints REST y hooks de autenticación.
 *
 * @package Kamples\Controllers\Usuario
 * @since 1.0.0
 */

namespace Kamples\Controllers\Usuario;

use Kamples\Services\Usuario\AuthService;

class AuthController
{
    private AuthService $authService;
    private \Logger $logger;

    public function __construct()
    {
        $this->authService = AuthService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Inicializa los hooks del controlador.
     */
    public static function inicializar(): void
    {
        $controller = new self();
        $controller->registrarHooks();
    }

    /**
     * Registra todos los hooks necesarios.
     */
    public function registrarHooks(): void
    {
        /* REST API endpoints */
        add_action('rest_api_init', [$this, 'registrarEndpointsRest']);

        /* Google callback */
        add_action('init', [$this, 'manejarGoogleCallback']);

        /* User ID en head */
        add_action('wp_head', [$this, 'inyectarUserId']);
    }

    /**
     * Registra los endpoints REST.
     */
    public function registrarEndpointsRest(): void
    {
        register_rest_route('myplugin/v1', '/log-user-agent', [
            'methods' => 'POST',
            'callback' => [$this, 'logUserAgent'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('2upra/v1', '/verify_token', [
            'methods' => 'POST',
            'callback' => [$this, 'verificarTokenEndpoint'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('custom/v1', '/save-token', [
            'methods' => 'POST',
            'callback' => [$this, 'guardarTokenFirebase'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Maneja el callback de Google OAuth.
     */
    public function manejarGoogleCallback(): void
    {
        if (!isset($_GET['code']) || !str_contains($_SERVER['REQUEST_URI'], 'google-callback')) {
            return;
        }

        $code = sanitize_text_field($_GET['code']);
        $googleUserInfo = $this->authService->procesarGoogleCallback($code);

        if (!$googleUserInfo) {
            wp_redirect(home_url('/iniciar-sesion?error=google'));
            exit;
        }

        $userId = $this->authService->autenticarConGoogle($googleUserInfo);

        if (!$userId) {
            wp_redirect(home_url('/iniciar-sesion?error=auth'));
            exit;
        }

        $token = $this->authService->generarTokenSeguro($userId);
        $url = home_url();

        if ($this->authService->esAppElectron() && $token) {
            $url = home_url('/app?token=' . $token);
        }

        if (!headers_sent()) {
            wp_redirect($url);
        } else {
            echo "<script>window.location.href = '{$url}';</script>";
        }
        exit;
    }

    /**
     * Registra un user agent.
     */
    public function logUserAgent(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_json_params();
        $userAgent = sanitize_text_field($params['userAgent'] ?? '');
        $tipo = sanitize_text_field($params['type'] ?? '');

        $this->authService->registrarUserAgent($userAgent, $tipo);

        return new \WP_REST_Response(['message' => 'UserAgent registrado correctamente'], 200);
    }

    /**
     * Verifica un token de sesión.
     */
    public function verificarTokenEndpoint(\WP_REST_Request $request): \WP_REST_Response
    {
        $token = sanitize_text_field($request->get_param('token'));
        $userId = $this->authService->verificarToken($token);

        if ($userId) {
            return new \WP_REST_Response(['user_id' => $userId, 'status' => 'valid'], 200);
        }

        return new \WP_REST_Response(['message' => 'Token inválido', 'status' => 'invalid'], 401);
    }

    /**
     * Guarda token de Firebase para notificaciones push.
     */
    public function guardarTokenFirebase(\WP_REST_Request $request): \WP_REST_Response
    {
        $userId = intval($request->get_param('userId'));
        $firebaseToken = sanitize_text_field($request->get_param('token'));

        if (!$userId) {
            return new \WP_REST_Response(['error' => 'invalid_user', 'message' => 'El usuario no existe o el ID es inválido.'], 400);
        }

        if (!$firebaseToken) {
            return new \WP_REST_Response(['error' => 'no_token', 'message' => 'El token es requerido.'], 400);
        }

        $resultado = $this->authService->guardarTokenFirebase($userId, $firebaseToken);

        if (!$resultado) {
            return new \WP_REST_Response(['error' => 'save_failed', 'message' => 'No se pudo guardar el token.'], 500);
        }

        /* Guardar versión de la app si viene en la petición */
        $versionName = sanitize_text_field($request->get_param('appVersionName') ?? '');
        $versionCode = intval($request->get_param('appVersionCode') ?? 0);

        $this->authService->guardarVersionApp($userId, $versionName, $versionCode);

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Token y versión de la app guardados correctamente.',
        ], 200);
    }

    /**
     * Inyecta el ID del usuario en el head.
     */
    public function inyectarUserId(): void
    {
        if (is_user_logged_in()) {
            $userId = get_current_user_id();
            echo "<script>window.userId = '{$userId}';</script>";
        }
    }
}
