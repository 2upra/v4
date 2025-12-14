<?php

namespace Kamples\Controllers;

use Kamples\Services\ReporteService;

/**
 * Controlador de reportes.
 * 
 * Maneja los endpoints AJAX para el sistema de reportes.
 *
 * @since 1.0.0
 */
class ReporteController
{
    private static bool $registrado = false;
    private ReporteService $servicio;

    /**
     * Constructor e inicialización de hooks.
     */
    public function __construct()
    {
        $this->servicio = ReporteService::obtenerInstancia();
        $this->registrarHooks();
    }

    /**
     * Inicializa el controlador (Singleton-like).
     *
     * @return void
     */
    public static function inicializar(): void
    {
        if (!self::$registrado) {
            new self();
            self::$registrado = true;
        }
    }

    /**
     * Registra los hooks de WordPress.
     *
     * @return void
     */
    private function registrarHooks(): void
    {
        add_action('wp_ajax_guardarReporte', [$this, 'guardarReporte']);
    }

    /**
     * Guarda un reporte de contenido.
     *
     * @return void
     */
    public function guardarReporte(): void
    {
        $userId = get_current_user_id();
        if (!$userId) {
            wp_send_json_error('Usuario no autenticado.');
            return;
        }

        $idContenido = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $tipoContenido = isset($_POST['tipoContenido']) ? sanitize_text_field($_POST['tipoContenido']) : '';
        $detalles = isset($_POST['detalles']) ? sanitize_textarea_field($_POST['detalles']) : '';

        if (!$idContenido) {
            wp_send_json_error('ID de contenido requerido.');
            return;
        }

        $resultado = $this->servicio->guardarReporte($userId, $idContenido, $tipoContenido, $detalles);

        if ($resultado['success']) {
            wp_send_json_success($resultado['message']);
        } else {
            wp_send_json_error($resultado['message']);
        }
    }
}

/* Inicializar el controlador */
ReporteController::inicializar();
