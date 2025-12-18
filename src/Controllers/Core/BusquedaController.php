<?php

namespace Kamples\Controllers\Core;

use Kamples\Services\Core\BusquedaService;

/**
 * Controlador AJAX para busquedas.
 * 
 * Maneja las peticiones AJAX de busqueda de contenido.
 *
 * @package Kamples\Controllers\Core
 * @since 1.0.0
 */
class BusquedaController
{
    private BusquedaService $busquedaService;

    /**
     * Constructor e inicializacion de hooks AJAX.
     */
    public function __construct()
    {
        $this->busquedaService = BusquedaService::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Registra los hooks AJAX de WordPress.
     *
     * @return void
     */
    private function registrarHooks(): void
    {
        add_action('wp_ajax_buscarResultado', [$this, 'buscarResultados']);
        add_action('wp_ajax_nopriv_buscarResultado', [$this, 'buscarResultados']);
    }

    /**
     * Handler AJAX para busqueda de resultados.
     *
     * @return void
     */
    public function buscarResultados(): void
    {
        $texto = isset($_POST['busqueda']) ? sanitize_text_field($_POST['busqueda']) : '';

        if (empty($texto)) {
            wp_send_json(['success' => true, 'data' => '']);
            return;
        }

        $html = $this->busquedaService->buscarConCache($texto);

        wp_send_json(['success' => true, 'data' => $html]);
    }
}

/* 
 * Inicializar el controlador
 */
new BusquedaController();
