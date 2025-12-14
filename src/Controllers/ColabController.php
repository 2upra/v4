<?php

/**
 * Controlador AJAX para el sistema de colaboraciones.
 * 
 * Maneja las solicitudes AJAX relacionadas con la creación
 * y gestión de colaboraciones entre usuarios.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Controllers;

use Kamples\Services\ColabService;

/* Evitar acceso directo */

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColabController
{
    /**
     * Servicio de colaboraciones.
     */
    private ColabService $colabService;

    /**
     * Logger para registro de eventos.
     */
    private $logger;

    /**
     * Constructor.
     * 
     * @param ColabService|null $colabService Servicio (inyección de dependencias).
     */
    public function __construct(?ColabService $colabService = null)
    {
        $this->colabService = $colabService ?? new ColabService();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registrar acciones AJAX de WordPress.
     */
    public function registrar(): void
    {
        add_action('wp_ajax_empezarColab', [$this, 'empezarColab']);
    }

    /**
     * Iniciar una nueva colaboración.
     * Solo usuarios logueados.
     */
    public function empezarColab(): void
    {
        /* Verificar autenticación */
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
            return;
        }

        /* Obtener y sanitizar datos */
        $datos = $this->obtenerDatosCreacion();

        /* Validar post ID */
        if (empty($datos['postId'])) {
            $this->logger->warning('colab', 'No se proporcionó ID de publicación');
            wp_send_json_error(['message' => 'No se ha proporcionado el ID de la publicación']);
            return;
        }

        /* Crear colaboración */
        $resultado = $this->colabService->crearColaboracion($datos);

        if ($resultado['exito']) {
            wp_send_json_success(['message' => $resultado['mensaje']]);
        } else {
            wp_send_json_error(['message' => $resultado['mensaje']]);
        }

        wp_die();
    }

    /**
     * Obtener y sanitizar datos de creación de colaboración.
     * 
     * @return array Datos sanitizados.
     */
    private function obtenerDatosCreacion(): array
    {
        return [
            'postId'  => isset($_POST['postId']) ? intval($_POST['postId']) : 0,
            'fileId'  => isset($_POST['fileId']) ? intval($_POST['fileId']) : 0,
            'mensaje' => isset($_POST['mensaje']) ? sanitize_textarea_field($_POST['mensaje']) : '',
            'fileUrl' => isset($_POST['fileUrl']) ? esc_url_raw($_POST['fileUrl']) : '',
            'userId'  => get_current_user_id(),
        ];
    }
}
