<?php

/**
 * Funciones de Chat DEPRECADAS.
 * 
 * Este archivo contiene wrappers temporales para compatibilidad.
 * Todas las funciones aquí están marcadas como @deprecated y serán eliminadas.
 * 
 * USO CORRECTO:
 * - Lógica: Kamples\Services\ChatService
 * - AJAX/REST: Kamples\Controllers\ChatController
 *
 * @package Kamples
 * @since 1.0.0
 * @deprecated Este archivo será eliminado una vez se actualicen todas las referencias.
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Controllers\ChatController;
use Kamples\Services\ChatService;

/* 
 *
 * Inicialización del controlador
 *
 */

$chatController = new ChatController();
$chatController->registrar();

/* 
 *
 * Funciones wrapper DEPRECADAS
 *
 */

/**
 * Wrapper de compatibilidad para procesarMensaje.
 *
 * @deprecated Usar Kamples\Controllers\ChatController::apiProcesarMensaje
 */
function procesarMensaje($request)
{
    $controller = new ChatController();
    return $controller->apiProcesarMensaje($request);
}

/**
 * Wrapper de compatibilidad para guardarMensaje.
 *
 * @deprecated Usar Kamples\Services\ChatService::guardarMensaje
 */
function guardarMensaje($emisor, $receptor, $mensaje, $adjunto = null, $metadata = null, $conversacion_id = null)
{
    try {
        $service = new ChatService();
        return $service->guardarMensaje(
            (int) $emisor,
            (int) $receptor,
            $mensaje,
            $adjunto,
            $metadata,
            $conversacion_id ? (int) $conversacion_id : null
        );
    } catch (Exception $e) {
        error_log("Error en guardarMensaje (wrapper): " . $e->getMessage());
        return false;
    }
}

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

/**
 * Utilidad: Tiempo relativo.
 * 
 * @deprecated Usar ChatService::tiempoRelativo()
 */
function tiempoRelativo($fecha)
{
    $service = new ChatService();
    return $service->tiempoRelativo($fecha);
}

/**
 * Utilidad: Nombre de usuario.
 * 
 * @deprecated Usar ChatService::obtenerInfoUsuario()
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

/**
 * Obtener chats de un usuario (para wrappers legacy).
 *
 * @param int $usuarioId
 * @param int $pagina
 * @param int $resultadosPorPagina
 * @return array
 * @deprecated Usar Kamples\Views\Components\ChatList::obtenerConversaciones()
 */
function obtenerChats($usuarioId, $pagina = 1, $resultadosPorPagina = 10)
{
    $chatList = new \Kamples\Views\Components\ChatList();
    return $chatList->obtenerConversaciones((int)$usuarioId, $pagina, $resultadosPorPagina);
}

/**
 * Render lista de chats.
 * 
 * @deprecated Usar Kamples\Views\Components\ChatList::render()
 */
function renderListaChats($conversaciones, $usuarioId)
{
    // Ya no usamos el array de conversaciones directamente, delegamos al componente
    return \Kamples\Views\Components\ChatList::mostrar((int)$usuarioId);
}

/**
 * Renderizar conversaciones de un usuario.
 * 
 * @param int $usuarioId ID del usuario.
 * @return string HTML de las conversaciones.
 * @deprecated Usar Kamples\Views\Components\ChatList::mostrar()
 */
if (!function_exists('conversacionesUsuario')) {
    function conversacionesUsuario($usuarioId): string
    {
        return \Kamples\Views\Components\ChatList::mostrar((int)$usuarioId);
    }
}

/**
 * Renderizar el modal de chat.
 * 
 * @return string HTML del chat.
 * @deprecated Usar Kamples\Views\Components\ChatBox::mostrar()
 */
if (!function_exists('renderChat')) {
    function renderChat(): string
    {
        return \Kamples\Views\Components\ChatBox::mostrar();
    }
}
