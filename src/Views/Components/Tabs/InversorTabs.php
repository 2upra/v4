<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de tabs para inversor/proyecto.
 *
 * @since 2.0.0
 */
class InversorTabs
{
    /**
     * Renderiza los tabs del inversor.
     * 
     * @return string HTML de los tabs
     */
    public static function render(): string
    {
        ob_start();
?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Proyecto"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <div class="tab S4K7I3" id="Proyecto">
                    <?php echo function_exists('inversorSector') ? inversorSector() : ''; ?>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
