<?php

/**
 * Wrappers deprecados para funciones de publicaciones/query.
 * 
 * Este archivo proporciona compatibilidad con código legacy.
 * Todas las funciones aquí están DEPRECADAS y serán eliminadas en futuras versiones.
 * 
 * @deprecated Usar Kamples\Services\PublicacionService en su lugar
 * @since 1.0.0
 */

use Kamples\Services\PublicacionService;
use Kamples\Controllers\PublicacionController;

/* Registrar el controlador AJAX */

PublicacionController::registrar();

/**
 * Obtiene publicaciones con los argumentos proporcionados.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->obtener()
 * @param array $args Argumentos de búsqueda
 * @param bool $isAjax Si es petición AJAX
 * @param int $paged Página actual
 * @return string|false HTML o false si falla
 */
function publicaciones($args = [], $isAjax = false, $paged = 1)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->obtener($args, $isAjax, (int)$paged);
}

/**
 * Handler AJAX para cargar más publicaciones.
 *
 * @deprecated Se registra automáticamente via PublicacionController::registrar()
 * @return void
 */
function publicacionAjax()
{
    $controller = new PublicacionController();
    $controller->cargarMasPublicaciones();
}

/**
 * Configura los argumentos de la query estándar.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->configuracionQueryArgs()
 * @param array $args Argumentos
 * @param int $paged Página
 * @param mixed $userId ID usuario perfil
 * @param int $usuarioActual ID usuario actual
 * @param string $tipoUsuario Tipo de usuario
 * @return array|false Query args
 */
function configuracionQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->configuracionQueryArgs($args, (int)$paged, $userId, (int)$usuarioActual, $tipoUsuario);
}

/**
 * Aplica pre-ordenamiento según el tipo de post.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->preOrdenamiento()
 * @param array $args Argumentos
 * @param int $paged Página
 * @param int $usu Usuario actual
 * @param string $identifier Identificador de búsqueda
 * @param bool $isAdmin Es administrador
 * @param int $posts Posts por página
 * @param int $filtroTiempo Filtro de tiempo
 * @param int|null $similarTo ID de post similar
 * @param string|null $tipoUsuario Tipo de usuario
 * @return array|false Query args
 */
function preOrdenamiento($args, $paged, $usu, $identifier, $isAdmin, $posts, $filtroTiempo, $similarTo, $tipoUsuario = null)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->preOrdenamiento(
        $args,
        (int)$paged,
        (int)$usu,
        $identifier,
        $isAdmin,
        (int)$posts,
        (int)$filtroTiempo,
        $similarTo,
        $tipoUsuario
    );
}

/**
 * Obtiene colecciones para el momento.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->obtenerColeccionesParaMomento()
 * @param array $args Argumentos
 * @param int $usuarioActual Usuario actual
 * @return string HTML de colecciones
 */
function obtenerColeccionesParaMomento($args, $usuarioActual)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->obtenerColeccionesParaMomento($args, (int)$usuarioActual);
}

/**
 * Ordenamiento especial para colecciones.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->ordenamientoColecciones()
 * @param array $queryArgs Query args
 * @param int $filtroTiempo Filtro tiempo
 * @param int $usuarioActual Usuario actual
 * @return array Query args modificados
 */
function ordenamientoColecciones($queryArgs, $filtroTiempo, $usuarioActual)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->ordenamientoColecciones($queryArgs, (int)$filtroTiempo, (int)$usuarioActual);
}

/**
 * Aplica ordenamiento según filtro de tiempo.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->ordenamiento()
 * @param array $queryArgs Query args actuales
 * @param int $filtroTiempo Filtro de tiempo seleccionado
 * @param int $usuarioActual ID usuario actual
 * @param string $identifier Identificador de búsqueda
 * @param int|null $similarTo ID post similar
 * @param int $paged Página
 * @param bool $isAdmin Es admin
 * @param int $posts Posts por página
 * @param string|null $tipoUsuario Tipo usuario
 * @return array Query args modificados
 */
function ordenamiento($queryArgs, $filtroTiempo, $usuarioActual, $identifier, $similarTo, $paged, $isAdmin, $posts, $tipoUsuario = null)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->ordenamiento(
        $queryArgs,
        (int)$filtroTiempo,
        (int)$usuarioActual,
        $identifier,
        $similarTo,
        (int)$paged,
        $isAdmin,
        (int)$posts,
        $tipoUsuario
    );
}

/**
 * Aplica filtros de usuario a la query.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->aplicarFiltrosUsuario()
 * @param array $queryArgs Query args
 * @param int $usuarioActual Usuario actual
 * @return array Query args modificados
 */
function aplicarFiltrosUsuario($queryArgs, $usuarioActual)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->aplicarFiltrosUsuario($queryArgs, (int)$usuarioActual);
}

/**
 * Pre-filtra por identifier (búsqueda).
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->prefiltrarIdentifier()
 * @param string $identifier Término de búsqueda
 * @param array $queryArgs Query args
 * @return array Query args modificados
 */
function prefiltrarIdentifier($identifier, $queryArgs)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->prefiltrarIdentifier($identifier, $queryArgs);
}

/**
 * Procesa publicaciones y genera HTML.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->procesarPublicaciones()
 * @param array $queryArgs Query args
 * @param array $args Argumentos originales
 * @param bool $is_ajax Si es AJAX
 * @return string HTML generado
 */
function procesarPublicaciones($queryArgs, $args, $is_ajax)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->procesarPublicaciones($queryArgs, $args, $is_ajax);
}

/**
 * Obtiene el userId según contexto.
 *
 * @deprecated Usar PublicacionService::obtenerInstancia()->obtenerUserId()
 * @param bool $is_ajax Si es AJAX
 * @return mixed User ID o null
 */
function obtenerUserId($is_ajax)
{
    $publicacionService = PublicacionService::obtenerInstancia();
    return $publicacionService->obtenerUserId($is_ajax);
}
