<?php

/**
 * Funciones wrapper de compatibilidad para gestión de estados de posts.
 * 
 * @deprecated Usar Kamples\Services\PostEstadoService directamente
 * @see \Kamples\Services\PostEstadoService
 * @see \Kamples\Controllers\PostEstadoController
 */

use Kamples\Services\PostEstadoService;

/**
 * Permite la descarga de un post.
 *
 * @deprecated Usar PostEstadoService::obtenerInstancia()->permitirDescarga()
 */
function permitirDescarga($post_id)
{
    $resultado = PostEstadoService::obtenerInstancia()->permitirDescarga((int)$post_id);
    return json_encode($resultado);
}

/**
 * Comprueba las colaboraciones del usuario.
 *
 * @deprecated Usar PostEstadoService::obtenerInstancia()->comprobarColabsUsuario()
 */
function comprobarColabsUsuario($user_id)
{
    return PostEstadoService::obtenerInstancia()->comprobarColabsUsuario((int)$user_id);
}

/**
 * Cambia el estado de un post.
 *
 * @deprecated Usar PostEstadoService::obtenerInstancia()->cambiarEstado()
 */
function cambiarEstado($post_id, $new_status)
{
    $resultado = PostEstadoService::obtenerInstancia()->cambiarEstado((int)$post_id, $new_status);
    return json_encode($resultado);
}
