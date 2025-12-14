<?php

/**
 * Componentes del reproductor de audio
 * 
 * Renderiza el reproductor flotante de audio
 *
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

class ReproductorComponents
{
    /**
     * Renderiza el reproductor flotante de audio
     *
     * @return string HTML del reproductor
     */
    public static function renderReproductor(): string
    {
        $iconoAnterior = $GLOBALS['anterior'] ?? '';
        $iconoPlay = $GLOBALS['play'] ?? '';
        $iconoPause = $GLOBALS['pause'] ?? '';
        $iconoSiguiente = $GLOBALS['siguiente'] ?? '';
        $iconoVolumen = $GLOBALS['volumen'] ?? '';
        $iconoCancelar = $GLOBALS['cancelicon'] ?? '';

        ob_start();
?>
        <div class="TMLIWT" id="reproductor-flotante" style="display: none;">
            <audio class="GSJJHK" style="display:none;"></audio>
            <div class="GPFFDR">
                <div class="CMJUXB">
                    <div class="progress-container">
                        <div class="progress-bar"></div>
                    </div>
                </div>

                <div class="CMJUXC">
                    <div class="HOYBKW">
                        <img class="LWXUER" alt="Portada del audio">
                    </div>
                    <div class="XKPMGD">
                        <p class="tituloR"></p>
                        <p class="AutorR"></p>
                    </div>
                    <div class="SOMGMR"></div>
                    <div class="PQWXDA">
                        <button class="prev-btn">
                            <?= $iconoAnterior; ?>
                        </button>
                        <button class="play-btn">
                            <?= $iconoPlay; ?>
                        </button>
                        <button class="pause-btn" style="display: none;">
                            <?= $iconoPause; ?>
                        </button>
                        <button class="next-btn">
                            <?= $iconoSiguiente; ?>
                        </button>
                        <div class="BSUXDA">
                            <button class="JMFCAI">
                                <?= $iconoVolumen; ?>
                            </button>
                            <div class="TGXRDF">
                                <input type="range" class="volume-control" min="0" max="1" step="0.01" value="1">
                            </div>
                        </div>
                        <button class="PCNLEZ">
                            <?= $iconoCancelar; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Añade el reproductor al footer
     */
    public static function agregarAlFooter(): void
    {
        add_action('wp_footer', function () {
            echo self::renderReproductor();
        });
    }
}
