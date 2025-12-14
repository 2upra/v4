<?php

namespace Kamples\Controllers;

use Kamples\Services\UtilService;

/**
 * Controlador para peticiones AJAX de utilidades.
 *
 * @since 1.0.0
 */
class UtilController
{
    private UtilService $utilService;

    public function __construct()
    {
        $this->utilService = UtilService::obtenerInstancia();
    }

    /**
     * Registra los hooks de AJAX.
     */
    public function registrarHooks(): void
    {
        add_action('wp_ajax_ajustar_zona_horaria', [$this, 'ajustarZonaHoraria']);
        add_action('wp_ajax_nopriv_ajustar_zona_horaria', [$this, 'ajustarZonaHoraria']);
    }

    /**
     * Ajusta la zona horaria del usuario mediante cookie.
     */
    public function ajustarZonaHoraria(): void
    {
        $zonaHoraria = sanitize_text_field($_POST['timezone'] ?? 'UTC');

        /* Validar que sea una zona horaria válida */
        try {
            new \DateTimeZone($zonaHoraria);
        } catch (\Exception $e) {
            $zonaHoraria = 'UTC';
        }

        setcookie('usuario_zona_horaria', $zonaHoraria, time() + 86400, '/');

        wp_send_json_success(['zona_horaria' => $zonaHoraria]);
    }
}
