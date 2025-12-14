<?php

/**
 * Funciones wrapper de compatibilidad para el sistema de filtros.
 * 
 * @deprecated Usar Kamples\Services\FiltroService y Kamples\Controllers\FiltroController
 * @see \Kamples\Services\FiltroService
 * @see \Kamples\Controllers\FiltroController
 */

use Kamples\Services\FiltroService;

/**
 * Aplica filtro global a query args.
 *
 * @deprecated Usar FiltroService::obtenerInstancia()->aplicarFiltroGlobal()
 */
function aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario = null)
{
    return FiltroService::obtenerInstancia()->aplicarFiltroGlobal(
        $queryArgs,
        $args,
        (int)$usuarioActual,
        $userId ? (int)$userId : null,
        $tipoUsuario
    );
}

/**
 * Aplica filtro por autor.
 *
 * @deprecated Usar FiltroService::obtenerInstancia()->aplicarFiltroPorAutor()
 */
function aplicarFiltroPorAutor($queryArgs, $userId, $filtro)
{
    return FiltroService::obtenerInstancia()->aplicarFiltroPorAutor(
        $queryArgs,
        (int)$userId,
        (string)$filtro
    );
}

/**
 * Aplica filtros de usuario.
 *
 * @deprecated Usar FiltroService::obtenerInstancia()->aplicarFiltrosDeUsuario()
 */
function aplicarFiltrosDeUsuario($queryArgs, $usu, $filtro)
{
    return FiltroService::obtenerInstancia()->aplicarFiltrosDeUsuario(
        $queryArgs,
        (int)$usu,
        (string)$filtro
    );
}

/**
 * Aplica condiciones de meta query.
 *
 * @deprecated Usar FiltroService::obtenerInstancia()->aplicarCondicionesDeMetaQuery()
 */
function aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario)
{
    return FiltroService::obtenerInstancia()->aplicarCondicionesDeMetaQuery(
        $queryArgs,
        (string)$filtro,
        (int)$usuarioActual,
        $tipoUsuario
    );
}

/**
 * Obtiene condiciones de meta query.
 *
 * @deprecated Usar FiltroService::obtenerInstancia()->obtenerCondicionesMetaQuery()
 */
function obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario)
{
    return FiltroService::obtenerInstancia()->obtenerCondicionesMetaQuery(
        (int)$usuarioActual,
        $tipoUsuario
    );
}
