<?php

/**
 * Controlador de edición de posts
 * 
 * Maneja endpoints AJAX para edición de título, descripción y tags
 *
 * @package Kamples\Controllers
 * @since 1.0.0
 */

namespace Kamples\Controllers;

use Kamples\Services\PostEdicionService;

class PostEdicionController
{
    private PostEdicionService $postEdicionService;

    public function __construct()
    {
        $this->postEdicionService = PostEdicionService::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Registra los hooks AJAX
     */
    private function registrarHooks(): void
    {
        add_action('wp_ajax_cambiarDescripcion', [$this, 'manejarCambiarDescripcion']);
        add_action('wp_ajax_cambiarTitulo', [$this, 'manejarCambiarTitulo']);
        add_action('wp_ajax_corregirTags', [$this, 'manejarCorregirTags']);
    }

    /**
     * Maneja la solicitud AJAX de cambio de descripción
     */
    public function manejarCambiarDescripcion(): void
    {
        $userId = get_current_user_id();
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

        $resultado = $this->postEdicionService->cambiarDescripcion($userId, $postId, $descripcion);

        echo json_encode($resultado);
        wp_die();
    }

    /**
     * Maneja la solicitud AJAX de cambio de título
     */
    public function manejarCambiarTitulo(): void
    {
        $userId = get_current_user_id();
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';

        $resultado = $this->postEdicionService->cambiarTitulo($userId, $postId, $titulo);

        echo json_encode($resultado);
        wp_die();
    }

    /**
     * Maneja la solicitud AJAX de corrección de tags
     */
    public function manejarCorregirTags(): void
    {
        $userId = get_current_user_id();
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

        $resultado = $this->postEdicionService->corregirTags($userId, $postId, $descripcion);

        echo json_encode($resultado);
        wp_die();
    }
}
