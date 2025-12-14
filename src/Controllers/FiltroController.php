<?php

namespace Kamples\Controllers;

use Kamples\Services\FiltroService;

/**
 * Controlador AJAX para gestión de filtros.
 * 
 * Maneja todas las peticiones AJAX relacionadas con
 * filtros de posts y preferencias del usuario.
 *
 * @since 1.0.0
 */
class FiltroController
{
    private FiltroService $filtroService;

    /**
     * Constructor e inicialización de hooks AJAX.
     */
    public function __construct()
    {
        $this->filtroService = FiltroService::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Registra los hooks AJAX de WordPress.
     *
     * @return void
     */
    private function registrarHooks(): void
    {
        add_action('wp_ajax_obtenerFiltroActual', [$this, 'obtenerFiltroActual']);
        add_action('wp_ajax_obtenerFiltrosTotal', [$this, 'obtenerFiltrosTotal']);
        add_action('wp_ajax_obtenerFiltros', [$this, 'obtenerFiltros']);
        add_action('wp_ajax_guardarFiltro', [$this, 'guardarFiltro']);
        add_action('wp_ajax_guardarFiltroPost', [$this, 'guardarFiltroPost']);
        add_action('wp_ajax_restablecerFiltros', [$this, 'restablecerFiltros']);
    }

    /**
     * Obtiene el filtro de tiempo actual del usuario.
     *
     * @return void
     */
    public function obtenerFiltroActual(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Usuario no autenticado']);
            return;
        }

        $userId = get_current_user_id();
        $datos = $this->filtroService->obtenerFiltroActual($userId);

        wp_send_json_success($datos);
    }

    /**
     * Obtiene todos los filtros del usuario.
     *
     * @return void
     */
    public function obtenerFiltrosTotal(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuario no autenticado');
            return;
        }

        $userId = get_current_user_id();
        $datos = $this->filtroService->obtenerFiltrosTotal($userId);

        wp_send_json_success($datos);
    }

    /**
     * Obtiene los filtros de post del usuario.
     *
     * @return void
     */
    public function obtenerFiltros(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuario no autenticado');
            return;
        }

        $userId = get_current_user_id();
        $filtros = $this->filtroService->obtenerFiltros($userId);

        wp_send_json_success(['filtros' => $filtros]);
    }

    /**
     * Guarda el filtro de tiempo del usuario.
     *
     * @return void
     */
    public function guardarFiltro(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Usuario no autenticado']);
            return;
        }

        if (!isset($_POST['filtroTiempo'])) {
            wp_send_json_error(['message' => 'Valor de filtroTiempo no especificado']);
            return;
        }

        $userId = get_current_user_id();
        $filtroTiempo = intval($_POST['filtroTiempo']);

        if ($this->filtroService->guardarFiltroTiempo($userId, $filtroTiempo)) {
            wp_send_json_success([
                'message' => 'Filtro guardado correctamente',
                'filtroTiempo' => $filtroTiempo,
                'userId' => $userId
            ]);
        } else {
            wp_send_json_error(['message' => 'Error al guardar el filtro']);
        }
    }

    /**
     * Guarda los filtros de post del usuario.
     *
     * @return void
     */
    public function guardarFiltroPost(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuario no autenticado');
            return;
        }

        $filtrosRaw = isset($_POST['filtros']) ? stripslashes($_POST['filtros']) : '[]';
        $filtros = json_decode($filtrosRaw, true);

        if (!is_array($filtros)) {
            $filtros = [];
        }

        $userId = get_current_user_id();

        if ($this->filtroService->guardarFiltroPost($userId, $filtros)) {
            wp_send_json_success(['message' => 'Filtros guardados correctamente']);
        } else {
            wp_send_json_error('Error al guardar los filtros');
        }
    }

    /**
     * Restablece los filtros del usuario.
     *
     * @return void
     */
    public function restablecerFiltros(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuario no autenticado');
            return;
        }

        $userId = get_current_user_id();
        $restablecerPost = isset($_POST['post']) && $_POST['post'] === 'true';
        $restablecerColeccion = isset($_POST['coleccion']) && $_POST['coleccion'] === 'true';

        $this->filtroService->restablecerFiltros($userId, $restablecerPost, $restablecerColeccion);

        wp_send_json_success(['message' => 'Filtros restablecidos']);
    }
}

/* 
 * Inicializar el controlador
 */
new FiltroController();
