<?php

/**
 * Controlador AJAX para el sistema de likes.
 * 
 * Maneja las solicitudes AJAX relacionadas con likes,
 * favoritos y dislikes de posts.
 *
 * @package Kamples\Controllers\Social
 * @since 1.0.0
 */

namespace Kamples\Controllers\Social;

use Kamples\Services\Social\LikeService;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class LikeController
{
    /**
     * Servicio de likes.
     * 
     * @var LikeService
     */
    private LikeService $likeService;

    /**
     * Instancia del Logger.
     * 
     * @var \Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param LikeService|null $likeService Servicio de likes (inyeccion de dependencias).
     */
    public function __construct(?LikeService $likeService = null)
    {
        $this->likeService = $likeService ?? new LikeService();

        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
    }

    /**
     * Registrar acciones AJAX de WordPress.
     */
    public function registrar(): void
    {
        add_action('wp_ajax_like', [$this, 'manejarLike']);
    }

    /**
     * Manejar solicitud AJAX de like.
     * 
     * Procesa la accion de like/unlike desde el frontend.
     */
    public function manejarLike(): void
    {
        if ($this->logger) {
            $this->logger->info('post', '[LikeController] Iniciando solicitud de like');
        }

        $respuesta = ['success' => false];

        if (!is_user_logged_in()) {
            $this->enviarRespuesta($respuesta, 'not_logged_in');
            return;
        }

        if (!check_ajax_referer('like_post_nonce', 'nonce', false)) {
            $this->enviarRespuesta($respuesta, 'invalid_nonce');
            return;
        }

        $datos = $this->obtenerDatosRequest();

        if (!$this->likeService->esTipoValido($datos['tipo'])) {
            $this->enviarRespuesta($respuesta, 'error_like_type');
            return;
        }

        if (empty($datos['postId'])) {
            $this->enviarRespuesta($respuesta, 'missing_post_id');
            return;
        }

        $accion = $datos['estado'] ? $datos['tipo'] : 'unlike';

        $this->likeService->ejecutarAccion(
            $datos['postId'],
            $datos['userId'],
            $accion,
            $datos['tipo']
        );

        $contadores = $this->likeService->obtenerContadores($datos['postId']);

        $respuesta['success'] = true;
        $respuesta['counts'] = $contadores;

        if ($this->logger) {
            $this->logger->info('post', '[LikeController] Like procesado exitosamente. Tipo: ' . $datos['tipo']);
        }

        $this->enviarRespuesta($respuesta);
    }

    /**
     * Obtener y sanitizar datos de la solicitud.
     *
     * @return array
     */
    private function obtenerDatosRequest(): array
    {
        return [
            'userId' => get_current_user_id(),
            'postId' => isset($_POST['post_id']) ? absint($_POST['post_id']) : 0,
            'estado' => isset($_POST['like_state'])
                ? filter_var($_POST['like_state'], FILTER_VALIDATE_BOOLEAN)
                : false,
            'tipo' => isset($_POST['like_type'])
                ? sanitize_text_field($_POST['like_type'])
                : 'like',
        ];
    }

    /**
     * Enviar respuesta JSON y terminar ejecucion.
     *
     * @param array       $respuesta Datos de respuesta.
     * @param string|null $error     Codigo de error (opcional).
     */
    private function enviarRespuesta(array $respuesta, ?string $error = null): void
    {
        if ($error !== null) {
            $respuesta['error'] = $error;
        }

        echo json_encode($respuesta);
        wp_die();
    }
}
