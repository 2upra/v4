<?php

/**
 * Wrappers deprecados para tipos de posts
 * 
 * Este archivo contiene funciones de compatibilidad que redirigen
 * a las nuevas clases en src/. NO USAR EN CÓDIGO NUEVO.
 *
 * @deprecated Usar Kamples\Core\PostTypes
 * @package Theme\V4\Deprecated
 */

use Kamples\Core\PostTypes;

/*
 * La inicialización de CPTs ahora se hace a través de PostTypes::inicializar()
 * que se llama desde functions.php
 * 
 * Las siguientes funciones se mantienen como wrappers por si algún código
 * externo las llama directamente.
 */

if (!function_exists('register_custom_post_statuses')) {
    /**
     * @deprecated Usar PostTypes::registrarEstadosPersonalizados()
     */
    function register_custom_post_statuses()
    {
        PostTypes::registrarEstadosPersonalizados();
    }
}

if (!function_exists('register_custom_post_types')) {
    /**
     * @deprecated Usar PostTypes::registrarTiposDePost()
     */
    function register_custom_post_types()
    {
        PostTypes::registrarTiposDePost();
    }
}

if (!function_exists('register_colab_meta')) {
    /**
     * @deprecated Usar PostTypes::registrarMetaColab()
     */
    function register_colab_meta()
    {
        PostTypes::registrarMetaColab();
    }
}

if (!function_exists('regenerate_colecciones_sitemap')) {
    /**
     * @deprecated Usar PostTypes::regenerarSitemapColecciones()
     */
    function regenerate_colecciones_sitemap($post_ID, $post, $update)
    {
        PostTypes::regenerarSitemapColecciones($post_ID, $post, $update);
    }
}
