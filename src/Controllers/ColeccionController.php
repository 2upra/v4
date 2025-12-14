<?php

/**
 * Controlador AJAX para el sistema de colecciones.
 * 
 * Maneja las solicitudes AJAX relacionadas con la creación,
 * edición, eliminación y gestión de colecciones de samples.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Controllers;

use Kamples\Services\ColeccionService;

/* Evitar acceso directo */

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColeccionController
{
    /**
     * Servicio de colecciones.
     */
    private ColeccionService $coleccionService;

    /**
     * Logger para registro de eventos.
     */
    private $logger;

    /**
     * Constructor.
     * 
     * @param ColeccionService|null $coleccionService Servicio (inyección de dependencias).
     */
    public function __construct(?ColeccionService $coleccionService = null)
    {
        $this->coleccionService = $coleccionService ?? new ColeccionService();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Registrar acciones AJAX de WordPress.
     */
    public function registrar(): void
    {
        add_action('wp_ajax_crearColeccion', [$this, 'crearColeccion']);
        add_action('wp_ajax_editarColeccion', [$this, 'editarColeccion']);
        add_action('wp_ajax_borrarColec', [$this, 'eliminarColeccion']);
        add_action('wp_ajax_guardarSampleEnColec', [$this, 'guardarSample']);
        add_action('wp_ajax_eliminarSampledeColec', [$this, 'eliminarSample']);
        add_action('wp_ajax_verificar_sample_en_colecciones', [$this, 'verificarSampleEnColecciones']);
        add_action('wp_ajax_obtenerListaColec', [$this, 'obtenerListaColecciones']);
    }

    /**
     * Crear una nueva colección.
     */
    public function crearColeccion(): void
    {
        if (!is_user_logged_in()) {
            $this->logger->warning('guardar', 'Usuario no autenticado intentó crear colección');
            wp_send_json_error(['error' => 'Usuario no autenticado']);
            return;
        }

        $datos = [
            'userId'      => get_current_user_id(),
            'titulo'      => $_POST['titulo'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'sampleId'    => isset($_POST['colecSampleId']) ? intval($_POST['colecSampleId']) : 0,
            'imgColec'    => $_POST['imgColec'] ?? '',
            'imgColecId'  => $_POST['imgColecId'] ?? '',
            'privado'     => isset($_POST['privado']) ? intval($_POST['privado']) : 0,
        ];

        $resultado = $this->coleccionService->crearColeccion($datos);

        if ($resultado['exito']) {
            wp_send_json_success(['message' => $resultado['mensaje']]);
        } else {
            wp_send_json_error(['error' => $resultado['mensaje']]);
        }

        wp_die();
    }

    /**
     * Editar una colección existente.
     */
    public function editarColeccion(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Usuario no autenticado']);
            return;
        }

        $datos = [
            'userId'      => get_current_user_id(),
            'coleccionId' => isset($_POST['coleccionId']) ? intval($_POST['coleccionId']) : 0,
            'nombre'      => $_POST['nameColec'] ?? '',
            'descripcion' => $_POST['descriptionColec'] ?? '',
            'tags'        => $_POST['tagsColec'] ?? [],
            'imagen'      => $_POST['image'] ?? '',
        ];

        $resultado = $this->coleccionService->editarColeccion($datos);

        if ($resultado['exito']) {
            wp_send_json_success(['success' => true]);
        } else {
            wp_send_json_error(['error' => $resultado['mensaje']]);
        }

        wp_die();
    }

    /**
     * Eliminar una colección.
     */
    public function eliminarColeccion(): void
    {
        if (!is_user_logged_in()) {
            $this->logger->warning('guardar', 'Usuario no autenticado intentó eliminar colección');
            wp_send_json_error(['message' => 'Usuario no autenticado']);
            return;
        }

        $coleccionId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $userId = get_current_user_id();

        if (!$coleccionId) {
            $this->logger->warning('guardar', 'ID de colección no válido');
            wp_send_json_error(['message' => 'ID de colección no válido']);
            return;
        }

        $resultado = $this->coleccionService->eliminarColeccion($coleccionId, $userId);

        if ($resultado['exito']) {
            wp_send_json_success(['message' => $resultado['mensaje']]);
        } else {
            wp_send_json_error(['message' => $resultado['mensaje']]);
        }

        wp_die();
    }

    /**
     * Guardar un sample en una colección.
     */
    public function guardarSample(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Usuario no autorizado']);
            return;
        }

        $sampleId = isset($_POST['colecSampleId']) ? intval($_POST['colecSampleId']) : 0;
        $colecId  = $_POST['colecSelecionado'] ?? '';
        $userId   = get_current_user_id();

        if (!$sampleId || !$colecId) {
            wp_send_json_error(['message' => 'Datos inválidos']);
            return;
        }

        $resultado = $this->coleccionService->guardarSample($colecId, $sampleId, $userId);

        if ($resultado['exito']) {
            wp_send_json_success([
                'message' => $resultado['mensaje'],
                'samples' => $resultado['samples']
            ]);
        } else {
            wp_send_json_error(['message' => $resultado['mensaje']]);
        }

        wp_die();
    }

    /**
     * Eliminar un sample de una colección.
     */
    public function eliminarSample(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Usuario no autenticado']);
            return;
        }

        $coleccionId = isset($_POST['coleccion_id']) ? intval($_POST['coleccion_id']) : 0;
        $sampleId    = isset($_POST['sample_id']) ? intval($_POST['sample_id']) : 0;
        $userId      = get_current_user_id();

        $resultado = $this->coleccionService->eliminarSampleDeColeccion($coleccionId, $sampleId, $userId);

        if ($resultado['exito']) {
            wp_send_json_success(['message' => $resultado['mensaje']]);
        } else {
            wp_send_json_error(['message' => $resultado['mensaje']]);
        }

        wp_die();
    }

    /**
     * Verificar en qué colecciones está un sample.
     */
    public function verificarSampleEnColecciones(): void
    {
        $sampleId = isset($_POST['sample_id']) ? intval($_POST['sample_id']) : 0;
        $userId   = get_current_user_id();

        if (!$sampleId) {
            wp_send_json_success(['colecciones' => []]);
            return;
        }

        $colecciones = $this->coleccionService->verificarSampleEnColecciones($sampleId, $userId);

        wp_send_json_success(['colecciones' => $colecciones]);
    }

    /**
     * Obtener lista de colecciones del usuario en formato HTML.
     */
    public function obtenerListaColecciones(): void
    {
        $userId = get_current_user_id();

        if (!$userId) {
            $this->logger->warning('guardar', 'No se pudo obtener ID del usuario');
            wp_send_json_error('Error: No se pudo obtener el ID del usuario actual.');
            return;
        }

        $colecciones = $this->coleccionService->obtenerColeccionesUsuario($userId);
        $html = $this->renderizarListaColecciones($colecciones);

        wp_send_json_success($html);
        wp_die();
    }

    /**
     * Renderizar lista de colecciones en HTML.
     * 
     * @param array $colecciones Array de colecciones.
     * @return string HTML de la lista.
     */
    private function renderizarListaColecciones(array $colecciones): string
    {
        if (empty($colecciones)) {
            return '<li>No se encontraron colecciones.</li>';
        }

        $iconPapelera = $GLOBALS['iconPapelera'] ?? '';
        ob_start();

        foreach ($colecciones as $coleccion) {
?>
            <li class="coleccion borde" data-post_id="<?php echo esc_attr($coleccion['id']); ?>">
                <img src="<?php echo esc_url($coleccion['imagen']); ?>" alt="">
                <span><?php echo esc_html($coleccion['titulo']); ?></span>
                <button class="borrarColec" data-post_id="<?php echo esc_attr($coleccion['id']); ?>">
                    <?php echo $iconPapelera; ?>
                </button>
            </li>
<?php
        }

        return ob_get_clean();
    }
}
