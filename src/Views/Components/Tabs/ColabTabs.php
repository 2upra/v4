<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de tabs para colaboraciones.
 *
 * @since 2.0.0
 */
class ColabTabs
{
    /**
     * Renderiza los tabs de colaboraciones.
     * 
     * @return string HTML de los tabs
     */
    public static function render(): string
    {
        ob_start();
?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Colabs"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <div class="tab INICIO S4K7I3" id="Colabs">
                    <div class="GSDKRA">
                        <?php echo function_exists('colab') ? colab() : ''; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
