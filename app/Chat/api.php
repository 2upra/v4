<?php

/**
 * API del sistema de chat (Archivo Legacy).
 * 
 * Este archivo mantiene funciones wrapper para compatibilidad y registra rutas
 * usando el nuevo ChatController.
 *
 * @package Theme_V4
 * @since 1.0.0
 * @deprecated Funciones globales serán eliminadas. Usar ChatController y ChatService.
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Theme\V4\Controllers\ChatController;
use Theme\V4\Services\ChatService;

/* 
 *
 * Inicialización del controlador
 *
 */

$chatController = new ChatController();
$chatController->registrar();

/*
 * Registro adicional para endpoints antiguos que aún no se migran completamente
 * (como procesarMensaje, si existe en global scope)
 */
add_action('rest_api_init', function () use ($chatController) {
    if (function_exists('procesarMensaje')) {
        register_rest_route('galle/v2', '/procesarmensaje', array(
            'methods' => 'POST',
            'callback' => 'procesarMensaje',
            'permission_callback' => function ($request) use ($chatController) {
                return $chatController->validarAccesoToken($request);
            }
        ));
    }
});

/* 
 *
 * Funciones wrapper para compatibilidad
 *
 */

/**
 * Verificar token (Wrapper).
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 * @deprecated Usar ChatController::apiVerificarToken
 */
function verificarToken($request)
{
    $controller = new ChatController();
    return $controller->apiVerificarToken($request);
}

/**
 * Generar token (Wrapper).
 * 
 * @deprecated Usar ChatController::ajaxGenerarToken
 */
function generarToken()
{
    $controller = new ChatController();
    $controller->ajaxGenerarToken();
}
