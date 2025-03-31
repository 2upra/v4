<?php

/**
 * Helper para generar elementos HTML relacionados con el audio, como el reproductor.
 */

/**
 * Genera el HTML para el reproductor de audio global que se muestra en el footer.
 * Este reproductor se controla mediante JavaScript para reproducir las pistas seleccionadas.
 */
function reproductor()
{
?>

    <div class="TMLIWT" style="display: none;">

        <audio class="GSJJHK" style="display:none;"></audio>
        <div class="GPFFDR">

            <div class="CMJUXB">
                <div class="progress-container">
                    <div class="progress-bar"></div>
                </div>
            </div>

            <div class="CMJUXC">
                <div class="HOYBKW">
                    <img class="LWXUER">
                </div>
                <div class="XKPMGD">
                    <p class="tituloR"></p>
                    <p class="AutorR"></p>
                </div>
                <div class="SOMGMR">
            
                </div>
                <div class="PQWXDA">
                    <button class="prev-btn">
                        <? echo $GLOBALS['anterior']; ?>
                    </button>
                    <button class="play-btn">
                        <? echo $GLOBALS['play']; ?>
                    </button>
                    <button class="pause-btn" style="display: none;">
                        <? echo $GLOBALS['pause']; ?>
                    </button>
                    <button class="next-btn">
                        <? echo $GLOBALS['siguiente']; ?>
                    </button>
                    <div class="BSUXDA">
                        <button class="JMFCAI">
                            <? echo $GLOBALS['volumen']; ?>
                        </button>
                        <div class="TGXRDF">
                            <input type="range" class="volume-control" min="0" max="1" step="0.01" value="1">
                        </div>
                    </div>
                    <button class="PCNLEZ">
                        <? echo $GLOBALS['cancelicon']; ?>
                    </button>
                </div>

            </div>

        </div>
    </div>
<?

}
add_action('wp_footer', 'reproductor');
