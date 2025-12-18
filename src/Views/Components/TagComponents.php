<?php

namespace Kamples\Views\Components;

use Kamples\Services\Core\TagService;

/**
 * Componente de visualización de tags.
 *
 * @since 1.0.0
 */
class TagComponents
{
    /**
     * Renderiza los tags frecuentes.
     *
     * @param int $cantidad Cantidad de tags a mostrar
     * @return string HTML
     */
    public static function tagsPosts(int $cantidad = 32): string
    {
        $tagService = TagService::obtenerInstancia();
        $tagsFrecuentes = $tagService->obtenerTagsFrecuentes($cantidad);

        if (empty($tagsFrecuentes)) {
            return '<div class="tags-frecuentes">No tags available.</div>';
        }

        $html = '<div class="tags-frecuentes">';
        foreach ($tagsFrecuentes as $tag) {
            $html .= '<span class="postTag">' . esc_html(ucwords($tag)) . '</span> ';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Imprime los tags frecuentes (función helper).
     *
     * @return void
     */
    public static function mostrarTagsPosts(): void
    {
        echo self::tagsPosts();
    }
}
