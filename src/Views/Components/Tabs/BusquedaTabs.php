<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de tabs para la seccion de busqueda.
 *
 * @since 2.0.0
 */
class BusquedaTabs
{
    /**
     * Renderiza los tabs de busqueda.
     * 
     * @return string HTML de los tabs
     */
    public static function render(): string
    {
        ob_start();
?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Busquedatab"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <div class="tab INICIO S4K7I3" id="Busquedatab">
                    <div class="GSDKRA DSOE4LS">
                        <?php echo function_exists('busqueda') ? busqueda() : ''; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
