<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de tabs para la seccion de musica.
 *
 * @since 2.0.0
 */
class MusicTabs
{
    /**
     * Renderiza los tabs de musica (ultimas rolas).
     * 
     * @return string HTML de los tabs
     */
    public static function render(): string
    {
        $userId = get_current_user_id();

        if (function_exists('saberSi')) {
            saberSi($userId);
        }

        ob_start();
?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Music"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <div class="tab active ZYBVGE" id="Music" ajax="no">
                    <div class="SAOEXP">
                        <div class="XZCZLA">
                            <p class="titulorolasenviadas">Ultimas rolas</p>
                            <button class="TDMZDD"></button>
                        </div>
                        <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'rola', 'tab_id' => 'Music', 'posts' => 12]) : ''; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
