<?php

namespace Kamples\Controllers\Core;

use Kamples\Services\Core\VistaService;

/**
 * Controlador para peticiones AJAX de vistas.
 *
 * @package Kamples\Controllers\Core
 * @since 1.0.0
 */
class VistaController
{
    private VistaService $vistaService;
    private \Logger $logger;

    public function __construct()
    {
        $this->vistaService = new VistaService();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registra los hooks de AJAX.
     */
    public function registrarHooks(): void
    {
        add_action('wp_ajax_guardar_vistas', [$this, 'guardarVista']);
        add_action('wp_ajax_nopriv_guardar_vistas', [$this, 'guardarVista']);
    }

    /**
     * Guarda una vista de un post.
     */
    public function guardarVista(): void
    {
        $postId = isset($_POST['id_post']) ? intval($_POST['id_post']) : 0;

        if ($postId <= 0) {
            wp_send_json_error(['mensaje' => 'ID de post invalido']);
            return;
        }

        $userId = get_current_user_id();
        $resultado = $this->vistaService->registrarVista($postId, $userId);

        wp_send_json_success($resultado);
    }
}
