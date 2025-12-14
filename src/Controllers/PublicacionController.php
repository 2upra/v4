<?php

namespace Kamples\Controllers;

use Kamples\Services\PublicacionService;

/**
 * Controlador de publicaciones.
 * 
 * Maneja las peticiones AJAX para cargar publicaciones.
 *
 * @since 1.0.0
 */
class PublicacionController
{
    private PublicacionService $publicacionService;
    private static bool $registrado = false;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->publicacionService = PublicacionService::obtenerInstancia();
    }

    /**
     * Registra los hooks de WordPress.
     *
     * @return void
     */
    public static function registrar(): void
    {
        if (self::$registrado) {
            return;
        }

        $controller = new self();

        add_action('wp_ajax_cargar_mas_publicaciones', [$controller, 'cargarMasPublicaciones']);
        add_action('wp_ajax_nopriv_cargar_mas_publicaciones', [$controller, 'cargarMasPublicaciones']);

        self::$registrado = true;
    }

    /**
     * Maneja la petición AJAX para cargar más publicaciones.
     *
     * @return void
     */
    public function cargarMasPublicaciones(): void
    {
        $paged = isset($_POST['paged']) ? (int)$_POST['paged'] : 1;
        $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
        $tipoPost = isset($_POST['posttype']) ? sanitize_text_field($_POST['posttype']) : '';
        $dataIdentifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
        $tabId = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
        $userId = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';

        $publicacionesCargadas = [];
        if (isset($_POST['cargadas']) && is_array($_POST['cargadas'])) {
            $publicacionesCargadas = array_map('intval', $_POST['cargadas']);
        }

        $similarTo = isset($_POST['similar_to']) ? intval($_POST['similar_to']) : null;
        $colec = isset($_POST['colec']) ? intval($_POST['colec']) : null;
        $idea = isset($_POST['idea']) ? filter_var($_POST['idea'], FILTER_VALIDATE_BOOLEAN) : false;
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;

        $args = [
            'filtro' => $filtro,
            'post_type' => $tipoPost,
            'tab_id' => $tabId,
            'user_id' => $userId,
            'identifier' => $dataIdentifier,
            'exclude' => $publicacionesCargadas,
            'similar_to' => $similarTo,
            'colec' => $colec,
            'idea' => $idea,
            'id' => $id,
        ];

        $this->publicacionService->obtener($args, true, $paged);
    }
}
