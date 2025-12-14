<?php

/**
 * Wrappers deprecados para Ajustes de Posts (slugs)
 * 
 * Este archivo contiene funciones de compatibilidad que redirigen
 * a las nuevas clases en src/. NO USAR EN CÓDIGO NUEVO.
 *
 * @deprecated Usar Kamples\Services\PostSlugService
 * @package Theme\V4\Deprecated
 */

use Kamples\Services\PostSlugService;

if (!function_exists('registrarCambioSlug')) {
    /**
     * @deprecated Usar PostSlugService::registrarCambioSlug()
     */
    function registrarCambioSlug($old_slug, $post_id, $new_slug)
    {
        $service = new PostSlugService();
        $service->registrarCambioSlug($old_slug, $post_id, $new_slug);
    }
}

if (!function_exists('actualizar_titulos_y_slugs_social_posts')) {
    /**
     * @deprecated Usar PostSlugService::actualizarTitulosYSlugs()
     */
    function actualizar_titulos_y_slugs_social_posts($post_id, $post_after, $post_before)
    {
        PostSlugService::actualizarTitulosYSlugs($post_id, $post_after, $post_before);
    }
}
