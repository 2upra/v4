<?php

namespace Kamples\Controllers;

use Kamples\Services\IAService;

/**
 * Controlador para peticiones AJAX de IA.
 *
 * @since 1.0.0
 */
class IAController
{
    private IAService $iaService;
    private \Logger $logger;

    public function __construct()
    {
        $this->iaService = new IAService();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registra los hooks de AJAX.
     */
    public function registrarHooks(): void
    {
        add_action('wp_ajax_ai_request', [$this, 'manejarPeticionIA']);
        add_action('wp_ajax_nopriv_ai_request', [$this, 'manejarPeticionIA']);
    }

    /**
     * Maneja la petición AJAX de IA.
     */
    public function manejarPeticionIA(): void
    {
        if (!check_ajax_referer('ia_nonce', 'nonce', false)) {
            wp_send_json_error(['mensaje' => 'Nonce inválido']);
            return;
        }

        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $archivoPath = sanitize_text_field($_POST['archivo_path'] ?? '');
        $audioUri = esc_url_raw($_POST['audio_uri'] ?? '');

        if (empty($prompt)) {
            wp_send_json_error(['mensaje' => 'El prompt es requerido']);
            return;
        }

        $resultado = false;

        if (!empty($audioUri)) {
            $resultado = $this->iaService->generarDescripcionConUri($audioUri, $prompt);
        } elseif (!empty($archivoPath)) {
            $resultado = $this->iaService->generarDescripcion($archivoPath, $prompt);
        } else {
            wp_send_json_error(['mensaje' => 'Se requiere un archivo de audio o URI']);
            return;
        }

        if ($resultado === false) {
            wp_send_json_error(['mensaje' => 'Error al generar contenido']);
            return;
        }

        wp_send_json_success(['contenido' => $resultado]);
    }
}
