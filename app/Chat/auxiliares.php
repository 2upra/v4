<?php

use Theme\V4\Controllers\ChatController;
use Theme\V4\Services\ChatService;

/**
 * Utilidad: Tiempo relativo.
 * Mantenida como global por estar en uso posiblemente extendido.
 */
function tiempoRelativo($fecha)
{
    $service = new ChatService();
    return $service->tiempoRelativo($fecha);
}

/**
 * Utilidad: Nombre de usuario.
 * Mantenida como global por compatibilidad.
 */
function obtenerNombreUsuario($usuarioId)
{
    $service = new ChatService();
    $info = $service->obtenerInfoUsuario((int)$usuarioId);
    return $info ? $info['nombre'] : 'Usuario desconocido';
}

/**
 * Handler AJAX: Info usuario (Legacy Wrapper).
 * 
 * @deprecated Usar ChatController::ajaxInfoUsuario
 */
function infoUsuario()
{
    $controller = new ChatController();
    $controller->ajaxInfoUsuario();
}

// Action hook removed from here as it is registered in ChatController
// add_action('wp_ajax_infoUsuario', 'infoUsuario'); 