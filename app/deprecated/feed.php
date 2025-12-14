<?php

/**
 * Funciones wrapper de compatibilidad para el sistema de feed.
 * 
 * @deprecated Usar Kamples\Services\FeedService directamente
 * @see \Kamples\Services\FeedService
 */

use Kamples\Services\FeedService;

/**
 * Obtiene el feed personalizado de un usuario.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerFeedPersonalizado()
 */
function obtenerFeedPersonalizado(
    $idUsuario,
    $identificador,
    $similar,
    $pagina,
    $esAdmin,
    $postsPagina,
    $tipoUsuario = null,
    $filtrosUsuario = null
) {
    /* 
     * El código legacy puede pasar $filtrosUsuario como string serializado.
     * Convertimos a array si es necesario.
     */
    if (is_string($filtrosUsuario) && !empty($filtrosUsuario)) {
        $deserializado = @unserialize($filtrosUsuario);
        $filtrosUsuario = is_array($deserializado) ? $deserializado : null;
    } elseif (!is_array($filtrosUsuario)) {
        $filtrosUsuario = null;
    }

    return FeedService::obtenerInstancia()->obtenerFeedPersonalizado(
        (int)$idUsuario,
        (string)$identificador,
        $similar ? (int)$similar : null,
        (int)$pagina,
        (bool)$esAdmin,
        (int)$postsPagina,
        $tipoUsuario,
        $filtrosUsuario
    );
}

/**
 * Obtiene posts similares a un post específico.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerPostsSimilares()
 */
function obtenerPostsSimilares($current_user_id, $similar_to)
{
    $resultado = FeedService::obtenerInstancia()->obtenerPostsSimilares(
        (int)$current_user_id,
        (int)$similar_to
    );

    return [
        'posts_personalizados' => array_flip($resultado['post_ids'] ?? []),
        'post_not_in' => $resultado['post_not_in'] ?? [],
    ];
}

/**
 * Reinicia el feed de un usuario.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->reiniciarFeed()
 */
function reiniciarFeed($current_user_id)
{
    return FeedService::obtenerInstancia()->reiniciarFeed((int)$current_user_id);
}

/**
 * Obtiene datos del feed para un usuario.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerDatosFeed()
 */
function obtenerDatosFeed($userId)
{
    return FeedService::obtenerInstancia()->obtenerDatosFeed((int)$userId);
}

/**
 * Obtiene datos del feed con cache.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerDatosFeedConCache()
 */
function obtenerDatosFeedConCache($userId)
{
    return FeedService::obtenerInstancia()->obtenerDatosFeedConCache((int)$userId);
}

/**
 * Obtiene usuarios seguidos por un usuario.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerUsuariosSeguidos()
 */
function obtenerUsuariosSeguidos($userId)
{
    return FeedService::obtenerInstancia()->obtenerUsuariosSeguidos((int)$userId);
}

/**
 * Comprueba la conexión a la base de datos.
 *
 * @deprecated Esta función es interna del FeedService
 */
function comprobarConexionBD()
{
    global $wpdb;
    return $wpdb !== null;
}

/**
 * Valida un usuario.
 *
 * @deprecated Esta función es interna del FeedService
 */
function validarUsuario($userId)
{
    return (bool)$userId;
}

/**
 * Obtiene intereses del usuario.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerInteresesUsuario()
 */
function obtenerInteresesUsuario($userId)
{
    return FeedService::obtenerInstancia()->obtenerInteresesUsuario((int)$userId);
}

/**
 * Obtiene datos de vistas.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->vistasDatos()
 */
function vistasDatos($userId)
{
    return FeedService::obtenerInstancia()->vistasDatos((int)$userId);
}

/**
 * Obtiene IDs de posts recientes.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerIdsPostsRecientes()
 */
function obtenerIdsPostsRecientes()
{
    return FeedService::obtenerInstancia()->obtenerIdsPostsRecientes();
}

/**
 * Obtiene metadatos de posts.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerMetadatosPosts()
 */
function obtenerMetadatosPosts($postsIds)
{
    return FeedService::obtenerInstancia()->obtenerMetadatosPosts($postsIds);
}

/**
 * Procesa metadatos de roles.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->procesarMetadatosRoles()
 */
function procesarMetadatosRoles($metaData)
{
    return FeedService::obtenerInstancia()->procesarMetadatosRoles($metaData);
}

/**
 * Obtiene likes por post.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerLikesPorPost()
 */
function obtenerLikesPorPost($postsIds)
{
    return FeedService::obtenerInstancia()->obtenerLikesPorPost($postsIds);
}

/**
 * Obtiene datos básicos de posts.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->obtenerDatosBasicosPosts()
 */
function obtenerDatosBasicosPosts($postsIds)
{
    return FeedService::obtenerInstancia()->obtenerDatosBasicosPosts($postsIds);
}

/**
 * Procesa contenido de posts.
 *
 * @deprecated Usar FeedService::obtenerInstancia()->procesarContenidoPosts()
 */
function procesarContenidoPosts($postsResultados)
{
    return FeedService::obtenerInstancia()->procesarContenidoPosts($postsResultados);
}
