<?php

/**
 * Controlador AJAX para el sistema de seguimiento de usuarios.
 * 
 * Maneja las solicitudes AJAX para seguir y dejar de seguir usuarios.
 *
 * @package Kamples\Controllers\Social
 * @since 1.0.0
 */

namespace Kamples\Controllers\Social;

use Kamples\Services\Usuario\SeguirService;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class SeguirController
{
    /**
     * Servicio de seguimiento.
     * 
     * @var SeguirService
     */
    private SeguirService $seguirService;

    /**
     * Constructor.
     *
     * @param SeguirService|null $seguirService Servicio de seguimiento.
     */
    public function __construct(?SeguirService $seguirService = null)
    {
        $this->seguirService = $seguirService ?? new SeguirService();
    }

    /**
     * Registrar acciones AJAX de WordPress.
     */
    public function registrar(): void
    {
        add_action('wp_ajax_seguir_usuario', [$this, 'seguirUsuario']);
        add_action('wp_ajax_dejar_de_seguir_usuario', [$this, 'dejarDeSeguirUsuario']);
    }

    /**
     * Manejar solicitud AJAX de seguir usuario.
     */
    public function seguirUsuario(): void
    {
        $datos = $this->obtenerDatosRequest();

        $resultado = $this->seguirService->seguir(
            $datos['seguidorId'],
            $datos['seguidoId']
        );

        wp_send_json([
            'success' => $resultado,
            'message' => $resultado
                ? 'Usuario seguido exitosamente'
                : 'Error al seguir usuario',
        ]);
    }

    /**
     * Manejar solicitud AJAX de dejar de seguir usuario.
     */
    public function dejarDeSeguirUsuario(): void
    {
        $datos = $this->obtenerDatosRequest();

        $resultado = $this->seguirService->dejarDeSeguir(
            $datos['seguidorId'],
            $datos['seguidoId']
        );

        wp_send_json([
            'success' => $resultado,
            'message' => $resultado
                ? 'Usuario dejado de seguir exitosamente'
                : 'Error al dejar de seguir usuario',
        ]);
    }

    /**
     * Obtener y sanitizar datos del request.
     *
     * @return array
     */
    private function obtenerDatosRequest(): array
    {
        return [
            'seguidorId' => $this->obtenerIdDesdePost('seguidor_id'),
            'seguidoId' => $this->obtenerIdDesdePost('seguido_id'),
        ];
    }

    /**
     * Obtener un ID de usuario desde POST de forma segura.
     *
     * @param string $key Clave del valor en POST.
     * @return int
     */
    private function obtenerIdDesdePost(string $key): int
    {
        return isset($_POST[$key]) ? absint($_POST[$key]) : 0;
    }
}
