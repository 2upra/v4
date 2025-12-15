<?php

namespace Kamples\Controllers\Social;

use Kamples\Services\ChatService;

/**
 * Controlador de handlers AJAX para el chat.
 * 
 * Maneja generación de tokens, información de usuario,
 * historial de chat y estado de conexión.
 *
 * @since 1.0.0
 */
class ChatAjaxController
{
    private ChatService $chatService;

    public function __construct(?ChatService $chatService = null)
    {
        $this->chatService = $chatService ?? new ChatService();
    }

    /**
     * Registrar acciones AJAX.
     */
    public function registrarAjax(): void
    {
        add_action('wp_ajax_generarToken', [$this, 'generarToken']);
        add_action('wp_ajax_infoUsuario', [$this, 'infoUsuario']);
        add_action('wp_ajax_obtenerChat', [$this, 'obtenerChat']);
        add_action('wp_ajax_obtenerChatColab', [$this, 'obtenerChat']);
        add_action('wp_ajax_actualizarConexion', [$this, 'actualizarConexion']);
        add_action('wp_ajax_verificarConexionReceptor', [$this, 'verificarConexion']);
    }

    /**
     * Generar token para usuario actual.
     */
    public function generarToken(): void
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
     * Obtener info de usuario receptor.
     */
    public function infoUsuario(): void
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
     * Obtener historial de chat.
     */
    public function obtenerChat(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Usuario no autenticado.']);
            return;
        }

        $userId = get_current_user_id();
        $conversacionId = isset($_POST['conversacion']) ? (int) $_POST['conversacion'] : (isset($_POST['conversacion_id']) ? (int) $_POST['conversacion_id'] : 0);
        $receptorId = isset($_POST['receptor']) ? (int) $_POST['receptor'] : 0;
        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;

        if ($conversacionId <= 0 && $receptorId <= 0) {
            wp_send_json_error(['message' => 'ID de conversación o receptor inválido.']);
            return;
        }

        /* Resolver ID de conversación si no se dio */
        if ($conversacionId <= 0) {
            $conversacionId = $this->chatService->obtenerConversacionId($userId, $receptorId, false);

            if (!$conversacionId) {
                wp_send_json_success(['mensajes' => [], 'conversacion' => null]);
                return;
            }
        }

        /* Validar seguridad */
        if (!$this->chatService->validarParticipante($conversacionId, $userId)) {
            wp_send_json_error(['message' => 'No autorizado para ver esta conversación.']);
            return;
        }

        $this->chatService->marcarComoLeido($conversacionId, $userId);
        $mensajes = $this->chatService->obtenerMensajes($conversacionId, $page, 20);

        foreach ($mensajes as $mensaje) {
            $mensaje->clase = ($mensaje->remitente == $userId) ? 'mensajeDerecha' : 'mensajeIzquierda';
        }

        wp_send_json_success([
            'mensajes' => $mensajes,
            'conversacion' => $conversacionId
        ]);
    }

    /**
     * Actualizar estado de conexión del usuario.
     */
    public function actualizarConexion(): void
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
     * Verificar conexión de un receptor.
     */
    public function verificarConexion(): void
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

        $online = ($tiempoActual - $ultimaActividad) <= 180;

        wp_send_json_success(['online' => $online]);
    }
}
