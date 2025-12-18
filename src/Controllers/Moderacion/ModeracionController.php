<?php

/**
 * Controlador AJAX para moderacion de usuarios.
 * 
 * Maneja las peticiones AJAX relacionadas con el baneo y restriccion
 * de usuarios desde el panel de administracion.
 *
 * @package Kamples\Controllers\Moderacion
 * @since 1.0.0
 */

namespace Kamples\Controllers\Moderacion;

use Kamples\Services\Moderacion\ModeracionService;

class ModeracionController
{
    private ModeracionService $moderacionService;

    public function __construct()
    {
        $this->moderacionService = ModeracionService::obtenerInstancia();
        $this->registrarAjax();
    }

    /**
     * Registra los handlers AJAX.
     */
    private function registrarAjax(): void
    {
        add_action('wp_ajax_banearUsuario', [$this, 'handleBanearUsuario']);
    }

    /**
     * Handler AJAX: Banear al autor de un post.
     * 
     * Solo disponible para administradores.
     */
    public function handleBanearUsuario(): void
    {
        /* Verificar permisos */
        if (!current_user_can('administrator')) {
            wp_send_json_error('No tienes permisos para realizar esta accion.');
            wp_die();
        }

        /* Verificar nonce */
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'restringir_autor_nonce')) {
            wp_send_json_error('Nonce no valido.');
            wp_die();
        }

        /* Validar post_id */
        if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
            wp_send_json_error('No se proporciono un post_id.');
            wp_die();
        }

        $postId = intval($_POST['post_id']);
        $resultado = $this->moderacionService->banearAutorDePost($postId);

        if ($resultado && isset($resultado[$postId]['success']) && $resultado[$postId]['success']) {
            wp_send_json_success('El autor del post ha sido restringido correctamente.');
        } else {
            /* Buscar el resultado del primer usuario procesado */
            $mensaje = 'El autor del post ha sido restringido correctamente.';
            foreach ($resultado as $res) {
                if (isset($res['success'])) {
                    if ($res['success']) {
                        wp_send_json_success($res['mensaje'] ?? $mensaje);
                    } else {
                        wp_send_json_error($res['mensaje'] ?? 'Error al restringir usuario');
                    }
                    wp_die();
                }
            }
            wp_send_json_success($mensaje);
        }

        wp_die();
    }
}

/* Inicializar controlador */
new ModeracionController();
