<?php

namespace Kamples\Controllers;

use Kamples\Services\NotificacionService;

/**
 * Controlador de notificaciones.
 * 
 * Maneja las peticiones AJAX relacionadas con notificaciones.
 *
 * @since 2.0.0
 */
class NotificacionController
{
    private NotificacionService $notificacionService;
    private \Logger $logger;

    public function __construct()
    {
        $this->notificacionService = NotificacionService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Registra los hooks de AJAX.
     */
    private function registrarHooks(): void
    {
        add_action('wp_ajax_marcar_notificacion_vista', [$this, 'marcarNotificacionVista']);
        add_action('wp_ajax_cargar_notificaciones', [$this, 'cargarNotificaciones']);
        add_action('wp_ajax_verificar_notificaciones', [$this, 'verificarNotificaciones']);

        add_action('wp_enqueue_notifications', [$this, 'procesarNotificacionesCron']);
    }

    /**
     * Handler AJAX: Marcar notificación como vista.
     */
    public function marcarNotificacionVista(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'No tienes permiso para realizar esta accion.'], 403);
        }

        $notificacionId = isset($_POST['notificacionId']) ? intval($_POST['notificacionId']) : 0;
        $userId = get_current_user_id();

        $resultado = $this->notificacionService->marcarComoVista($notificacionId, $userId);

        if ($resultado['success']) {
            wp_send_json_success([
                'message'        => $resultado['message'],
                'notificacionId' => $resultado['notificacionId'] ?? $notificacionId
            ]);
        } else {
            wp_send_json_error(['message' => $resultado['message']], $resultado['code'] ?? 400);
        }
    }

    /**
     * Handler AJAX: Cargar notificaciones del usuario.
     */
    public function cargarNotificaciones(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('No tienes permiso para realizar esta accion.');
            wp_die();
        }

        $usuarioId = get_current_user_id();
        $pagina = isset($_POST['pagina']) ? intval($_POST['pagina']) : 1;

        $html = \Kamples\Views\Components\NotificacionComponents::listarNotificaciones($usuarioId, $pagina);

        wp_send_json_success($html);
        wp_die();
    }

    /**
     * Handler AJAX: Verificar si hay notificaciones no vistas.
     * 
     * Usa long polling para detectar nuevas notificaciones.
     */
    public function verificarNotificaciones(): void
    {
        $userId = get_current_user_id();
        $timeout = 30;
        $startTime = time();

        while (time() - $startTime < $timeout) {
            $hayNoVistas = $this->notificacionService->tieneNotificacionesNoVistas($userId);

            if ($hayNoVistas) {
                wp_send_json(['hay_no_vistas' => true]);
                return;
            }

            sleep(1);
        }

        wp_send_json(['hay_no_vistas' => false]);
    }

    /**
     * Handler CRON: Procesar notificaciones pendientes.
     */
    public function procesarNotificacionesCron(): void
    {
        $this->notificacionService->procesarNotificacionesPendientes();
    }
}

new NotificacionController();
