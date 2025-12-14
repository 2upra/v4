<?php

/**
 * Controlador para el sistema de chat.
 * 
 * Maneja endpoints de REST API y AJAX relacionados con el chat,
 * incluyendo verificación de tokens y procesamiento de mensajes.
 *
 * @package Theme_V4
 * @since 1.0.0
 */

namespace Theme\V4\Controllers;

use Theme\V4\Services\ChatService;
use WP_REST_Request;
use WP_REST_Response;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ChatController
{
    /**
     * Servicio de chat.
     * 
     * @var ChatService
     */
    private ChatService $chatService;

    /**
     * Constructor.
     *
     * @param ChatService|null $chatService Servicio de chat.
     */
    public function __construct(?ChatService $chatService = null)
    {
        $this->chatService = $chatService ?? new ChatService();
    }

    /**
     * Registrar rutas y acciones.
     */
    public function registrar(): void
    {
        // Registro de rutas REST API
        add_action('rest_api_init', [$this, 'registrarRutasApi']);

        // Registro de acciones AJAX
        add_action('wp_ajax_generarToken', [$this, 'ajaxGenerarToken']);
        add_action('wp_ajax_infoUsuario', [$this, 'ajaxInfoUsuario']);
    }

    /**
     * Registrar rutas de la API REST.
     */
    public function registrarRutasApi(): void
    {
        // Endpoint: Verificación de token
        register_rest_route('galle/v2', '/verificartoken', [
            'methods' => 'POST',
            'callback' => [$this, 'apiVerificarToken'],
            'permission_callback' => '__return_true'
        ]);

        // El endpoint procesarMensaje será migrado más adelante, 
        // pero mantenemos la lógica de validación de token aquí para uso futuro
    }

    /**
     * Handler API: Verificar token.
     * 
     * @param WP_REST_Request $request Solicitud REST.
     * @return WP_REST_Response
     */
    public function apiVerificarToken(WP_REST_Request $request): WP_REST_Response
    {
        // Obtener datos
        $token = $request->get_param('token') ?: $request->get_header('X-WP-Token');
        $userId = $request->get_param('user_id') ?: $request->get_header('X-User-ID');

        // Validar entradas básicas
        if (empty($token) || empty($userId)) {
            return new WP_REST_Response([
                'valid' => false,
                'message' => 'Token o ID de usuario faltante'
            ], 400);
        }

        // Verificar validez del token
        $esValido = $this->chatService->verificarToken($token, (int) $userId);

        if ($esValido) {
            return new WP_REST_Response([
                'valid' => true,
                'user_id' => $userId
            ], 200);
        } else {
            return new WP_REST_Response([
                'valid' => false,
                'message' => 'Token inválido'
            ], 401);
        }
    }

    /**
     * Handler AJAX: Generar token para usuario actual.
     */
    public function ajaxGenerarToken(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuario no autenticado');
            return;
        }

        $userId = get_current_user_id();

        if (!$this->chatService->tieneClaveSecreta()) {
            wp_send_json_error('Error interno del servidor: Clave no configurada');
            return;
        }

        $token = $this->chatService->generarToken($userId);

        wp_send_json_success([
            'token' => $token,
            'usu' => $userId
        ]);
    }

    /**
     * Handler AJAX: Obtener info de usuario.
     */
    public function ajaxInfoUsuario(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Usuario no autenticado.']);
            return;
        }

        $receptorId = isset($_POST['receptor']) ? (int) $_POST['receptor'] : 0;

        if ($receptorId <= 0) {
            wp_send_json_error(['message' => 'ID del receptor inválido.']);
            return;
        }

        $info = $this->chatService->obtenerInfoUsuario($receptorId);

        if ($info) {
            wp_send_json_success([
                'imagenPerfil' => $info['imagen'],
                'nombreUsuario' => $info['nombre']
            ]);
        } else {
            wp_send_json_error(['message' => 'Usuario no encontrado']);
        }
    }

    /**
     * Helper para validar verificación de token manualmente (usado por otros endpoints).
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function validarAccesoToken(WP_REST_Request $request): bool
    {
        $token = $request->get_header('X-WP-Token');
        $userId = $request->get_header('X-User-ID');

        if (!$token || !$userId) {
            return false;
        }

        return $this->chatService->verificarToken($token, (int) $userId);
    }
}
