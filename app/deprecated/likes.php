<?php

/**
 * Funciones de likes DEPRECADAS.
 * 
 * Este archivo contiene wrappers temporales para compatibilidad.
 * Todas las funciones aquí están marcadas como @deprecated y serán eliminadas.
 * 
 * USO CORRECTO:
 * - Lógica: Kamples\Services\LikeService
 * - AJAX: Kamples\Controllers\LikeController  
 * - Vista: Kamples\Views\Components\LikeButtons
 *
 * @package Kamples
 * @since 1.0.0
 * @deprecated Este archivo será eliminado una vez se actualicen todas las referencias.
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Services\LikeService;
use Kamples\Controllers\LikeController;

/* 
 *
 * Inicialización del controlador AJAX
 * Registra las acciones de WordPress para manejar solicitudes AJAX
 *
 */

$likeController = new LikeController();
$likeController->registrar();

/* 
 *
 * Funciones wrapper DEPRECADAS
 *
 */

/**
 * Manejar solicitud AJAX de like.
 * 
 * @deprecated Usar LikeController::manejarLike() directamente.
 */
function manejarLike(): void
{
    $controller = new LikeController();
    $controller->manejarLike();
}

/**
 * Ejecutar acción de like/unlike en base de datos.
 *
 * @param int    $postId   ID del post.
 * @param int    $userId   ID del usuario.
 * @param string $accion   Acción a realizar.
 * @param string $likeType Tipo de like.
 * @deprecated Usar LikeService::ejecutarAccion() directamente.
 */
function likeAccion($postId, $userId, $accion, $likeType = 'like'): void
{
    $servicio = new LikeService();
    $servicio->ejecutarAccion((int) $postId, (int) $userId, $accion, $likeType);
}

/**
 * Contar likes de un post.
 *
 * @param int         $postId   ID del post.
 * @param string|null $likeType Tipo de like (null para likes + favoritos).
 * @return int
 * @deprecated Usar LikeService::contarReacciones() directamente.
 */
function contarLike($postId, $likeType = null): int
{
    $servicio = new LikeService();
    return $servicio->contarReacciones((int) $postId, $likeType);
}

/**
 * Verificar si usuario tiene like en un post.
 *
 * @param int    $postId   ID del post.
 * @param int    $userId   ID del usuario.
 * @param string $likeType Tipo de like.
 * @return bool
 * @deprecated Usar LikeService::usuarioTieneReaccion() directamente.
 */
function chequearLike($postId, $userId, $likeType = 'like'): bool
{
    $servicio = new LikeService();
    return $servicio->usuarioTieneReaccion((int) $postId, (int) $userId, $likeType);
}

/**
 * Renderizar botones de like en un post.
 *
 * @param int $postId ID del post.
 * @return string HTML de los botones de like.
 * @deprecated Usar LikeButtons::mostrar() directamente.
 */
function like($postId): string
{
    return \Kamples\Views\Components\LikeButtons::mostrar((int) $postId);
}

/**
 * Obtener los IDs de posts que le gustan a un usuario.
 *
 * @param int $userId ID del usuario.
 * @return array IDs de posts.
 * @deprecated Usar LikeService::obtenerLikesDelUsuario() directamente.
 */
if (!function_exists('obtenerLikesDelUsuario')) {
    function obtenerLikesDelUsuario($userId): array
    {
        $servicio = new LikeService();
        return $servicio->obtenerLikesDelUsuario((int) $userId);
    }
}
