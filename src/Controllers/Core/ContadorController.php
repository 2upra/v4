<?php

namespace Kamples\Controllers\Core;

use Kamples\Services\Core\ContadorService;

/**
 * Controlador AJAX para conteo de posts.
 *
 * @package Kamples\Controllers\Core
 * @since 1.0.0
 */
class ContadorController
{
    private ContadorService $contadorService;

    /**
     * Constructor e inicializacion de hooks AJAX.
     */
    public function __construct()
    {
        $this->contadorService = ContadorService::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Registra los hooks AJAX de WordPress.
     *
     * @return void
     */
    private function registrarHooks(): void
    {
        add_action('wp_ajax_contarPostsFiltrados', [$this, 'contarPostsFiltrados']);
        add_action('wp_ajax_nopriv_contarPostsFiltrados', [$this, 'contarPostsFiltrados']);
    }

    /**
     * Handler AJAX para contar posts filtrados.
     *
     * @return void
     */
    public function contarPostsFiltrados(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Acceso no autorizado.']);
            return;
        }

        $userId = get_current_user_id();
        $postType = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'social_post';
        $busqueda = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filtros = isset($_POST['filters']) ? (array)$_POST['filters'] : [];

        $total = $this->contadorService->contarPostsFiltrados($userId, $postType, $busqueda, $filtros);

        wp_send_json_success(['total' => $total]);
    }
}

/* 
 * Inicializar el controlador
 */
new ContadorController();
