<?php

/**
 * Controlador de descargas
 * 
 * Maneja endpoints AJAX y redirects para descargas de audio
 *
 * @package Kamples\Controllers\Core
 * @since 1.0.0
 */

namespace Kamples\Controllers\Core;

use Kamples\Services\Core\DescargaService;

class DescargaController
{
    private DescargaService $descargaService;

    public function __construct()
    {
        $this->descargaService = DescargaService::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Registra los hooks de WordPress
     */
    private function registrarHooks(): void
    {
        add_action('wp_ajax_descargar_audio', [$this, 'manejarDescargaAjax']);
        add_action('template_redirect', [$this, 'manejarDescargaDirecta']);
    }

    /**
     * Maneja la solicitud AJAX de descarga
     */
    public function manejarDescargaAjax(): void
    {
        $userId = get_current_user_id();
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $esColeccion = isset($_POST['coleccion']) && $_POST['coleccion'] === 'true';
        $sync = isset($_POST['sync']) && $_POST['sync'] === 'true';

        $resultado = $this->descargaService->procesarDescarga(
            $userId,
            $postId,
            $esColeccion,
            $sync
        );

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Maneja la descarga directa por token
     */
    public function manejarDescargaDirecta(): void
    {
        if (isset($_GET['descarga_token'])) {
            $token = sanitize_text_field($_GET['descarga_token']);
            $this->descargaService->manejarDescarga($token);
        }
    }
}
