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

use Kamples\Controllers\Social\ChatController;
use Kamples\Controllers\Social\ChatApiController;
use Kamples\Controllers\Social\ChatAjaxController;
use Kamples\Services\Social\ChatService;

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
 * @deprecated Usar Kamples\Controllers\Social\ChatApiController::procesarMensaje
 */
function procesarMensaje($request)
{
    $controller = new ChatApiController();
    return $controller->procesarMensaje($request);
}

/**
 * Wrapper de compatibilidad para guardarMensaje.
 *
 * @deprecated Usar Kamples\Services\Social\ChatService::guardarMensaje
 */
function guardarMensaje($emisor, $receptor, $mensaje, $adjunto = null, $metadata = null, $conversacion_id = null)
{
    try {
        $service = ChatService::obtenerInstancia();
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
 * @deprecated Usar ChatApiController::verificarToken
 */
function verificarToken($request)
{
    $controller = new ChatApiController();
    return $controller->verificarToken($request);
}

/**
 * Generar token (Wrapper).
 * 
 * @deprecated Usar ChatAjaxController::generarToken
 */
function generarToken()
{
    $controller = new ChatAjaxController();
    $controller->generarToken();
}

/**
 * Utilidad: Tiempo relativo.
 * 
 * @deprecated Usar ChatService::tiempoRelativo()
 */
function tiempoRelativo($fecha)
{
    $service = ChatService::obtenerInstancia();
    return $service->tiempoRelativo($fecha);
}

/**
 * Utilidad: Nombre de usuario.
 * 
 * @deprecated Usar ChatService::obtenerInfoUsuario()
 */
function obtenerNombreUsuario($usuarioId)
{
    $service = ChatService::obtenerInstancia();
    $info = $service->obtenerInfoUsuario((int)$usuarioId);
    return $info ? $info['nombre'] : 'Usuario desconocido';
}

/**
 * Handler AJAX: Info usuario (Legacy Wrapper).
 * 
 * @deprecated Usar ChatAjaxController::infoUsuario
 */
function infoUsuario()
{
    $controller = new ChatAjaxController();
    $controller->infoUsuario();
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
