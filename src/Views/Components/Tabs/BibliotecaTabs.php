<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de tabs para la biblioteca del usuario.
 *
 * @since 2.0.0
 */
class BibliotecaTabs
{
    /**
     * Renderiza los tabs de la biblioteca.
     * 
     * @return string HTML de los tabs
     */
    public static function render(): string
    {
        ob_start();
?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Biblioteca"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <div class="tab INICIO S4K7I3" id="Biblioteca">
                    <div class="IIDJEND">
                        <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'rolaListLike', 'tab_id' => 'Biblioteca', 'posts' => 12, 'tipoUsuario' => 'Fan']) : ''; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
