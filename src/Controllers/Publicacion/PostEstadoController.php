<?php

namespace Kamples\Controllers\Publicacion;

use Kamples\Services\Publicacion\PostEstadoService;

/**
 * Controlador AJAX para gestion de estados de posts.
 * 
 * Maneja todas las peticiones AJAX relacionadas con
 * cambios de estado, verificacion e imagenes de posts.
 *
 * @package Kamples\Controllers\Publicacion
 * @since 1.0.0
 */
class PostEstadoController
{
    private PostEstadoService $estadoService;

    /**
     * Constructor e inicializacion de hooks AJAX.
     */
    public function __construct()
    {
        $this->estadoService = PostEstadoService::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Registra los hooks AJAX de WordPress.
     *
     * @return void
     */
    private function registrarHooks(): void
    {
        add_action('wp_ajax_verificarPost', [$this, 'verificarPost']);
        add_action('wp_ajax_permitirDescarga', [$this, 'cambioDeEstado']);
        add_action('wp_ajax_aceptarcolab', [$this, 'cambioDeEstado']);
        add_action('wp_ajax_rechazarcolab', [$this, 'cambioDeEstado']);
        add_action('wp_ajax_toggle_post_status', [$this, 'cambioDeEstado']);
        add_action('wp_ajax_reject_post', [$this, 'cambioDeEstado']);
        add_action('wp_ajax_request_post_deletion', [$this, 'cambioDeEstado']);
        add_action('wp_ajax_eliminarPostRs', [$this, 'cambioDeEstado']);
        add_action('wp_ajax_cambiar_imagen_post', [$this, 'cambiarImagenPost']);
    }

    /**
     * Handler AJAX para verificar un post.
     *
     * @return void
     */
    public function verificarPost(): void
    {
        if (!isset($_POST['post_id'])) {
            echo json_encode(['success' => false, 'message' => 'Post ID is missing']);
            wp_die();
        }

        $postId = intval($_POST['post_id']);
        $userId = get_current_user_id();

        $resultado = $this->estadoService->verificarPost($postId, $userId);

        echo json_encode($resultado);
        wp_die();
    }

    /**
     * Handler AJAX para cambio de estado.
     *
     * @return void
     */
    public function cambioDeEstado(): void
    {
        if (!isset($_POST['post_id'])) {
            echo json_encode(['success' => false, 'message' => 'Post ID is missing']);
            wp_die();
        }

        $postId = intval($_POST['post_id']);
        $accion = sanitize_text_field($_POST['action']);
        $estadoActual = isset($_POST['current_status']) ? sanitize_text_field($_POST['current_status']) : null;

        $resultado = $this->estadoService->procesarCambioEstado($postId, $accion, $estadoActual);

        echo json_encode($resultado);
        wp_die();
    }

    /**
     * Handler AJAX para cambiar la imagen de un post.
     *
     * @return void
     */
    public function cambiarImagenPost(): void
    {
        if (empty($_POST['post_id']) || empty($_FILES['imagen'])) {
            wp_send_json_error(['message' => 'Faltan datos necesarios.']);
            return;
        }

        $postId = intval($_POST['post_id']);
        $userId = get_current_user_id();

        $resultado = $this->estadoService->cambiarImagenPost($postId, $_FILES['imagen'], $userId);

        if ($resultado['success']) {
            wp_send_json_success(['new_image_url' => $resultado['new_image_url']]);
        } else {
            wp_send_json_error(['message' => $resultado['message']]);
        }
    }
}

/* 
 * Inicializar el controlador
 */
new PostEstadoController();
