<?php

/**
 * Controlador para el sistema de chat.
 * 
 * Maneja endpoints de REST API y AJAX relacionados con el chat,
 * incluyendo verificación de tokens y procesamiento de mensajes.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Controllers;

use Kamples\Services\ChatService;
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
        add_action('wp_ajax_obtenerChat', [$this, 'ajaxObtenerChat']);
        // obtenerChatColab parece ser una versión simplificada, podriamos redirigirla al mismo handler o unificar
        add_action('wp_ajax_obtenerChatColab', [$this, 'ajaxObtenerChat']);
        add_action('wp_ajax_actualizarConexion', [$this, 'ajaxActualizarConexion']);
        add_action('wp_ajax_verificarConexionReceptor', [$this, 'ajaxVerificarConexion']);
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

        // Endpoint: Procesar Mensaje
        register_rest_route('galle/v2', '/procesarmensaje', [
            'methods' => 'POST',
            'callback' => [$this, 'apiProcesarMensaje'],
            'permission_callback' => [$this, 'validarAccesoToken']
        ]);
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

    /**
     * Handler API: Procesar y guardar mensaje.
     * 
     * @param WP_REST_Request $request Solicitud REST.
     * @return WP_REST_Response|\WP_Error
     */
    public function apiProcesarMensaje(WP_REST_Request $request)
    {
        $usuarioActual = wp_get_current_user();

        // Manejo de autenticación similar al legacy
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

        // Validación básica
        if (!$emisor || !$mensaje) {
            return new \WP_Error('datos_incompletos', 'Faltan datos requeridos (emisor, mensaje)', ['status' => 400]);
        }

        if (!$receptor && !$conversacionId) {
            return new \WP_Error('datos_incompletos', 'Debe proporcionar receptor o conversacion_id', ['status' => 400]);
        }

        // Verificar identidad
        if ($emisor !== $usuarioActual->ID) {
            return new \WP_Error('emisor_no_autorizado', 'El emisor no coincide con el usuario autenticado', ['status' => 403]);
        }

        try {
            $mensajeId = $this->chatService->guardarMensaje(
                $emisor,
                // Si no hay receptor pero hay conversacionId, receptor puede ser 0
                // ChatService manejará la búsqueda o uso de conversacionId
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

    /**
     * Handler AJAX: Obtener historial de chat.
     */
    public function ajaxObtenerChat(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Usuario no autenticado.']);
            return; // wp_send_json_error mata el proceso, pero por si acaso
        }

        $userId = get_current_user_id();
        $conversacionId = isset($_POST['conversacion']) ? (int) $_POST['conversacion'] : (isset($_POST['conversacion_id']) ? (int) $_POST['conversacion_id'] : 0);
        $receptorId = isset($_POST['receptor']) ? (int) $_POST['receptor'] : 0;
        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;

        if ($conversacionId <= 0 && $receptorId <= 0) {
            wp_send_json_error(['message' => 'ID de conversación o receptor inválido.']);
            return;
        }

        // Resolver ID de conversación si no se dio
        if ($conversacionId <= 0) {
            $conversacionId = $this->chatService->obtenerConversacionId($userId, $receptorId, false);

            if (!$conversacionId) {
                // Si no existe conversación aún, devolver vacío
                wp_send_json_success(['mensajes' => [], 'conversacion' => null]);
                return;
            }
        }

        // Validar seguridad: El usuario debe ser parte de la conversación
        if (!$this->chatService->validarParticipante($conversacionId, $userId)) {
            wp_send_json_error(['message' => 'No autorizado para ver esta conversación.']);
            return;
        }

        // Marcar como leídos
        $this->chatService->marcarComoLeido($conversacionId, $userId);

        // Obtener mensajes
        $mensajes = $this->chatService->obtenerMensajes($conversacionId, $page, 20); // 20 por defecto

        // Formatear para el frontend (mantener compatibilidad con JS existente)
        foreach ($mensajes as $mensaje) {
            $mensaje->clase = ($mensaje->remitente == $userId) ? 'mensajeDerecha' : 'mensajeIzquierda';
            // adjunto y metadata ya decodificados por ChatService
        }

        wp_send_json_success([
            'mensajes' => $mensajes,
            'conversacion' => $conversacionId
        ]);
    }

    /**
     * Handler AJAX: Actualizar estado de conexión del usuario.
     */
    public function ajaxActualizarConexion(): void
    {
        if (!isset($_POST['user_id'])) {
            wp_send_json_error('No se proporcionó un ID de usuario.');
            return;
        }

        $userId = (int) $_POST['user_id'];
        $usuario = get_user_by('ID', $userId);

        if (!$usuario) {
            wp_send_json_error('Usuario no encontrado.');
            return;
        }

        update_user_meta($userId, 'onlineStatus', 'conectado');
        update_user_meta($userId, 'ultimaActividad', current_time('timestamp'));

        wp_send_json_success('Usuario actualizado como conectado.');
    }

    /**
     * Handler AJAX: Verificar conexión de un receptor.
     */
    public function ajaxVerificarConexion(): void
    {
        if (!isset($_POST['receptor_id'])) {
            wp_send_json_error('No se proporcionó un ID de receptor.');
            return;
        }

        $receptorId = (int) $_POST['receptor_id'];
        $usuario = get_user_by('ID', $receptorId);

        if (!$usuario) {
            wp_send_json_error('Receptor no encontrado.');
            return;
        }

        $ultimaActividad = get_user_meta($receptorId, 'ultimaActividad', true);
        $tiempoActual = current_time('timestamp');

        // Online si actividad en últimos 3 minutos
        $online = ($tiempoActual - $ultimaActividad) <= 180;

        wp_send_json_success(['online' => $online]);
    }
}
