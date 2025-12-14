<?php

/**
 * Funciones wrapper de compatibilidad para el sistema de búsqueda.
 * 
 * @deprecated Usar Kamples\Services\BusquedaService directamente
 * @see \Kamples\Services\BusquedaService
 * @see \Kamples\Controllers\BusquedaController
 */

use Kamples\Services\BusquedaService;
use Kamples\Views\Components\BusquedaComponents;

/**
 * Realiza una búsqueda.
 *
 * @deprecated Usar BusquedaService::obtenerInstancia()->realizarBusqueda()
 */
function realizar_busqueda($texto)
{
    return BusquedaService::obtenerInstancia()->realizarBusqueda($texto);
}

/**
 * Busca posts de un tipo.
 *
 * @deprecated Usar BusquedaService::obtenerInstancia()->buscarPosts()
 */
function buscar_posts($post_type, $texto)
{
    return BusquedaService::obtenerInstancia()->buscarPosts($post_type, $texto);
}

/**
 * Busca usuarios.
 *
 * @deprecated Usar BusquedaService::obtenerInstancia()->buscarUsuarios()
 */
function buscar_usuarios($texto)
{
    return BusquedaService::obtenerInstancia()->buscarUsuarios($texto);
}

/**
 * Balancea resultados.
 *
 * @deprecated Usar BusquedaService::obtenerInstancia()->balancearResultados()
 */
function balancear_resultados($resultados)
{
    return BusquedaService::obtenerInstancia()->balancearResultados($resultados);
}

/**
 * Obtiene imagen de un post.
 *
 * @deprecated Usar BusquedaService::obtenerInstancia()->obtenerImagenPost()
 */
function obtenerImagenPost($post_id)
{
    return BusquedaService::obtenerInstancia()->obtenerImagenPost((int)$post_id);
}

/**
 * Genera HTML de resultados.
 *
 * @deprecated Usar BusquedaService::obtenerInstancia()->generarHtmlResultados()
 */
function generar_html_resultados($resultados)
{
    return BusquedaService::obtenerInstancia()->generarHtmlResultados($resultados);
}

/**
 * Renderiza el buscador.
 *
 * @deprecated Usar BusquedaComponents::buscador()
 */
function busqueda()
{
    return BusquedaComponents::buscador();
}
