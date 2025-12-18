<?php

namespace Kamples\Controllers\Social;

use Kamples\Services\Social\ChatService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controlador de endpoints REST para el chat.
 * 
 * Maneja la verificación de tokens y procesamiento de mensajes.
 *
 * @since 1.0.0
 */
class ChatApiController
{
    private ChatService $chatService;

    public function __construct(?ChatService $chatService = null)
    {
        $this->chatService = $chatService ?? new ChatService();
    }

    /**
     * Registrar rutas de la API REST.
     */
    public function registrarEndpoints(): void
    {
        register_rest_route('galle/v2', '/verificartoken', [
            'methods' => 'POST',
            'callback' => [$this, 'verificarToken'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('galle/v2', '/procesarmensaje', [
            'methods' => 'POST',
            'callback' => [$this, 'procesarMensaje'],
            'permission_callback' => [$this, 'validarAccesoToken']
        ]);
    }

    /**
     * Verificar token de usuario.
     */
    public function verificarToken(WP_REST_Request $request): WP_REST_Response
    {
        $token = $request->get_param('token') ?: $request->get_header('X-WP-Token');
        $userId = $request->get_param('user_id') ?: $request->get_header('X-User-ID');

        if (empty($token) || empty($userId)) {
            return new WP_REST_Response([
                'valid' => false,
                'message' => 'Token o ID de usuario faltante'
            ], 400);
        }

        $esValido = $this->chatService->verificarToken($token, (int) $userId);

        if ($esValido) {
            return new WP_REST_Response([
                'valid' => true,
                'user_id' => $userId
            ], 200);
        }

        return new WP_REST_Response([
            'valid' => false,
            'message' => 'Token inválido'
        ], 401);
    }

    /**
     * Validar acceso por token (permission callback).
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

    /**
     * Procesar y guardar mensaje.
     */
    public function procesarMensaje(WP_REST_Request $request)
    {
        $usuarioActual = wp_get_current_user();

        if (!$usuarioActual->exists()) {
            $userIdHeader = $request->get_header('X-User-ID');
            if ($userIdHeader) {
                wp_set_current_user($userIdHeader);
                $usuarioActual = wp_get_current_user();
            }

            if (!$usuarioActual->exists()) {
                return new \WP_Error('usuario_no_autenticado', 'Usuario no autenticado', ['status' => 403]);
            }
        }

        $params = $request->get_json_params();

        $emisor = isset($params['emisor']) ? (int) $params['emisor'] : null;
        $receptor = isset($params['receptor']) ? (int) $params['receptor'] : null;
        $mensaje = isset($params['mensaje']) ? $params['mensaje'] : null;
        $adjunto = isset($params['adjunto']) ? $params['adjunto'] : null;
        $metadata = isset($params['metadata']) ? $params['metadata'] : null;
        $conversacionId = isset($params['conversacion_id']) ? (int) $params['conversacion_id'] : null;

        if (!$emisor || !$mensaje) {
            return new \WP_Error('datos_incompletos', 'Faltan datos requeridos (emisor, mensaje)', ['status' => 400]);
        }

        if (!$receptor && !$conversacionId) {
            return new \WP_Error('datos_incompletos', 'Debe proporcionar receptor o conversacion_id', ['status' => 400]);
        }

        if ($emisor !== $usuarioActual->ID) {
            return new \WP_Error('emisor_no_autorizado', 'El emisor no coincide con el usuario autenticado', ['status' => 403]);
        }

        try {
            $mensajeId = $this->chatService->guardarMensaje(
                $emisor,
                $receptor ?: 0,
                $mensaje,
                $adjunto,
                $metadata,
                $conversacionId
            );

            return new WP_REST_Response(['success' => true, 'mensaje_id' => $mensajeId], 200);
        } catch (\Exception $e) {
            return new \WP_Error('error_guardado', 'No se pudo guardar el mensaje: ' . $e->getMessage(), ['status' => 500]);
        }
    }
}
