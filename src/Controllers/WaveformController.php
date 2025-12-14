<?php

namespace Kamples\Controllers;

use Kamples\Services\WaveformService;

/**
 * Controlador para peticiones AJAX de waveforms.
 *
 * @since 1.0.0
 */
class WaveformController
{
    private WaveformService $waveformService;
    private \Logger $logger;

    public function __construct()
    {
        $this->waveformService = new WaveformService();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registra los hooks de AJAX.
     */
    public function registrarHooks(): void
    {
        add_action('wp_ajax_save_waveform_image', [$this, 'guardarWaveform']);
        add_action('wp_ajax_nopriv_save_waveform_image', [$this, 'guardarWaveform']);
    }

    /**
     * Guarda una imagen de waveform.
     */
    public function guardarWaveform(): void
    {
        if (!isset($_FILES['image']) || !isset($_POST['post_id'])) {
            wp_send_json_error('Datos incompletos');
            return;
        }

        $postId = intval($_POST['post_id']);

        if ($postId <= 0) {
            wp_send_json_error('ID de post inválido');
            return;
        }

        $resultado = $this->waveformService->guardarWaveform($_FILES['image'], $postId);

        if ($resultado === false) {
            wp_send_json_error('Error al subir la imagen');
            return;
        }

        wp_send_json_success([
            'message' => 'Imagen guardada correctamente',
            'url' => $resultado['url'],
            'size' => $resultado['size']
        ]);
    }
}
