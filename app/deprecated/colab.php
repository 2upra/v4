<?php

/**
 * Wrappers de compatibilidad para el sistema de colaboraciones.
 * 
 * Este archivo contiene funciones wrapper deprecadas que mantienen
 * la compatibilidad con código legacy mientras se completa la migración.
 * 
 * @deprecated Usar las clases en Kamples\Services, Kamples\Controllers y Kamples\Views
 * @package Kamples
 * @since 1.0.0
 */

/* Evitar acceso directo */
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Services\Social\ColabService;
use Kamples\Controllers\Social\ColabController;
use Kamples\Views\Components\ColabComponents;

/* 
 * Instancia del servicio para reutilización 
 */

$GLOBALS['_colabService'] = null;

/**
 * Obtener instancia del servicio de colaboraciones.
 * 
 * @return ColabService
 */
function obtenerColabService(): ColabService
{
    if ($GLOBALS['_colabService'] === null) {
        $GLOBALS['_colabService'] = new ColabService();
    }
    return $GLOBALS['_colabService'];
}

/**
 * Renderizar botón de colaboración para un post.
 * 
 * @deprecated Usar ColabService::obtenerBotonColab()
 * @param int  $postId ID del post.
 * @param bool $colab  Si permite colaboraciones.
 * @return string HTML del botón.
 */
function botonColab($postId, $colab): string
{
    return obtenerColabService()->obtenerBotonColab((int) $postId, (bool) $colab);
}

/**
 * Obtener variables de una colaboración.
 * 
 * @deprecated Usar ColabService::obtenerVariablesColab()
 * @param int|null $postId ID del post (usa global $post si null).
 * @return array Variables de la colaboración.
 */
function variablesColab($postId = null): array
{
    return obtenerColabService()->obtenerVariablesColab($postId ? (int) $postId : null);
}

/**
 * Renderizar HTML de una colaboración.
 * 
 * @deprecated Usar ColabComponents::renderHtmlColab()
 * @param string $filtro Filtro de la colaboración.
 * @return string HTML de la colaboración.
 */
function htmlColab($filtro): string
{
    return ColabComponents::renderHtmlColab($filtro);
}

/**
 * Renderizar mensaje de funcionalidad no disponible.
 * 
 * @deprecated Usar ColabComponents::renderMensajeNoDisponible()
 * @return string HTML del mensaje.
 */
function colab(): string
{
    return ColabComponents::renderMensajeNoDisponible();
}

/**
 * Renderizar vista de test de colaboraciones.
 * 
 * @deprecated Usar ColabComponents::renderColabTest()
 * @return string HTML de la vista de test.
 */
function colabTest(): string
{
    return ColabComponents::renderColabTest();
}

/**
 * Renderizar opciones de colaboración pendiente.
 * 
 * @deprecated Usar ColabComponents::renderOpcionesColab()
 * @param array $var Variables de la colaboración.
 * @return string HTML de las opciones.
 */
function opcionesColab($var): string
{
    return ColabComponents::renderOpcionesColab($var);
}

/**
 * Renderizar contenido de colaboración.
 * 
 * @deprecated Usar ColabComponents::renderContenidoColab()
 * @param array $var Variables de la colaboración.
 * @return string HTML del contenido.
 */
function contenidoColab($var): string
{
    return ColabComponents::renderContenidoColab($var);
}

/**
 * Renderizar reproductor de audio para colaboración.
 * 
 * @deprecated Usar ColabComponents::renderAudioColab()
 * @param int    $postId      ID del post.
 * @param string $audioIdLite ID del audio.
 * @return string HTML del audio.
 */
function audioColab($postId, $audioIdLite): string
{
    return ColabComponents::renderAudioColab((int) $postId, (string) $audioIdLite);
}

/**
 * Renderizar título de colaboración.
 * 
 * @deprecated Usar ColabComponents::renderTituloColab()
 * @param array $var Variables de la colaboración.
 * @return string HTML del título.
 */
function tituloColab($var): string
{
    return ColabComponents::renderTituloColab($var);
}

/**
 * Renderizar participantes de colaboración.
 * 
 * @deprecated Usar ColabComponents::renderParticipantesColab()
 * @param array $var Variables de la colaboración.
 * @return string HTML de los participantes.
 */
function participantesColab($var): string
{
    return ColabComponents::renderParticipantesColab($var);
}

/**
 * Renderizar opciones para colaboración activa.
 * 
 * @deprecated Usar ColabComponents::renderOpcionesColabActivo()
 * @param array $var Variables de la colaboración.
 * @return string HTML de las opciones.
 */
function opcionesColabActivo($var): string
{
    return ColabComponents::renderOpcionesColabActivo($var);
}

/**
 * Renderizar chat de colaboración.
 * 
 * @deprecated Usar ColabComponents::renderChatColab()
 * @param array $var Variables de la colaboración.
 * @return string HTML del chat.
 */
function chatColab($var): string
{
    return ColabComponents::renderChatColab($var);
}

/**
 * Renderizar resumen de colaboraciones del usuario.
 * 
 * @deprecated Usar ColabComponents::renderColabsResumen()
 * @return string HTML del resumen.
 */
function colabsResumen(): string
{
    return ColabComponents::renderColabsResumen();
}

/**
 * Manejar cambio de estado de colaboración.
 * 
 * Hook para wp action 'post_updated'.
 * 
 * @param int      $postId     ID del post.
 * @param \WP_Post $postAfter  Post después del cambio.
 * @param \WP_Post $postBefore Post antes del cambio.
 */
function actualizarEstadoColab($postId, $postAfter, $postBefore): void
{
    obtenerColabService()->manejarCambioEstado((int) $postId, $postAfter, $postBefore);
}

/* Registrar hook de actualización de estado */
add_action('post_updated', 'actualizarEstadoColab', 10, 3);

/**
 * Inicializar el controlador de colaboraciones.
 * Registra las acciones AJAX.
 */
function inicializarColabController(): void
{
    $controller = new ColabController();
    $controller->registrar();
}

add_action('init', 'inicializarColabController');
