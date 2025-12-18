<?php

/**
 * Wrappers de compatibilidad para el módulo de notificaciones.
 * 
 * NOTA: Este archivo contiene funciones legacy que deben
 * ser reemplazadas gradualmente por las clases en src/.
 * 
 * @deprecated Use las clases en Kamples\Services y Kamples\Views\Components
 */

use Kamples\Services\Social\NotificacionService;
use Kamples\Views\Components\NotificacionComponents;

/**
 * Procesa las notificaciones pendientes en cola.
 * 
 * @deprecated Use NotificacionService::obtenerInstancia()->procesarNotificacionesPendientes()
 */
if (!function_exists('procesar_notificaciones')) {
    function procesar_notificaciones(): void
    {
        NotificacionService::obtenerInstancia()->procesarNotificacionesPendientes();
    }
}

/**
 * Crea una nueva notificación.
 * 
 * @deprecated Use NotificacionService::obtenerInstancia()->crearNotificacion()
 * 
 * @param int $usuarioReceptor ID del usuario receptor
 * @param string $contenido Contenido de la notificación
 * @param bool $metaSolicitud Si es una solicitud
 * @param int $postIdRelacionado ID del post relacionado
 * @param string $titulo Título de la notificación
 * @param string|null $url URL de la notificación
 * @param int|null $emisor ID del emisor
 * @return int|WP_Error ID del post creado o error
 */
if (!function_exists('crearNotificacion')) {
    function crearNotificacion(
        $usuarioReceptor,
        $contenido,
        $metaSolicitud = false,
        $postIdRelacionado = 0,
        $titulo = 'Nueva notificacion',
        $url = null,
        $emisor = null
    ) {
        return NotificacionService::obtenerInstancia()->crearNotificacion(
            intval($usuarioReceptor),
            strval($contenido),
            (bool) $metaSolicitud,
            intval($postIdRelacionado),
            strval($titulo),
            $url,
            $emisor ? intval($emisor) : null
        );
    }
}

/**
 * Envía una notificación push mediante Firebase.
 * 
 * @deprecated Use NotificacionService::obtenerInstancia()->enviarPushNotification()
 * 
 * @param int $user_id ID del usuario
 * @param string $title Título
 * @param string $message Mensaje
 * @param string $url URL
 * @return string|WP_Error Resultado
 */
if (!function_exists('send_push_notification')) {
    function send_push_notification($user_id, $title, $message, $url)
    {
        return NotificacionService::obtenerInstancia()->enviarPushNotification(
            intval($user_id),
            strval($title),
            strval($message),
            strval($url)
        );
    }
}

/**
 * Lista las notificaciones de un usuario.
 * 
 * @deprecated Use NotificacionComponents::listarNotificaciones()
 * 
 * @param int $pagina Número de página
 * @return string HTML
 */
if (!function_exists('listarNotificaciones')) {
    function listarNotificaciones($pagina = 1): string
    {
        $usuarioId = get_current_user_id();
        return NotificacionComponents::listarNotificaciones($usuarioId, intval($pagina));
    }
}

/**
 * Renderiza el icono de notificaciones.
 * 
 * @deprecated Use NotificacionComponents::iconoNotificaciones()
 * 
 * @return string HTML
 */
if (!function_exists('iconoNotificaciones')) {
    function iconoNotificaciones(): string
    {
        return NotificacionComponents::iconoNotificaciones();
    }
}
