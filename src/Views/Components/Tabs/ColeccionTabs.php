<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de tabs para colecciones.
 *
 * @since 2.0.0
 */
class ColeccionTabs
{
    /**
     * Renderiza los tabs de colecciones.
     * 
     * @return string HTML de los tabs
     */
    public static function render(): string
    {
        ob_start();
?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Colecciones"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <div class="BPLBDE UP">
                    <div class="DHRDTAG">
                        <?php echo function_exists('tagsPosts') ? tagsPosts() : ''; ?>
                    </div>
                    <div class="FDGEDF">
                        <p id="resultadosPost-sampleList" typepost="colecciones"></p>
                        <div class="OPCDGED">
                            <button class="restablecerBusqueda coleccionRestablecer" style="display: none;">Restablecer filtros</button>
                            <button class="ORDENPOSTSL" id="ORDENPOSTSL">Opciones<?php echo $GLOBALS['flechaAbajo'] ?? ''; ?></button>
                            <div class="opcionCheckBox modal" id="filtrosPost" style="display: none;">
                                <div class="opcionCheck">
                                    <div>
                                        <label>Mostrar solo mi contenido</label>
                                        <p class="description">Solo se muestra lo que publicaste.</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="misColecciones" id="misColecciones">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <div class="XJAAHB">
                                    <button class="botonsecundario borde">Restablecer</button>
                                    <button class="botonprincipal">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab INICIO S4K7I3" id="Colecciones">
                    <div class="BPLBDE">
                        <?php echo function_exists('publicaciones') ? publicaciones(['post_type' => 'colecciones', 'filtro' => 'colecciones', 'posts' => 12]) : ''; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
