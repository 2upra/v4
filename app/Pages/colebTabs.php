<?

function colecTabs()
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
                    <? echo tagsPosts() ?>
                </div>
                <div class="FDGEDF">
                    <p id="resultadosPost-sampleList"></p>
                    <div class="OPCDGED">

                        <button class="restablecerBusqueda" style="display: none;">Restablecer filtros</button>

                        <button class="ORDENPOSTSL" id="ORDENPOSTSL">Opciones<? echo $GLOBALS['flechaAbajo']; ?></button>
                        <div class="opcionCheckBox modal" id="filtrosPost" style="display: none;">

                            <div class="opcionCheck">
                                <div>
                                    <label>Mostrar solo mi contenido</label>
                                    <p class="description">Oculta todo el contenido que no hayas publicado</p>
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
                    <? echo publicaciones(['post_type' => 'colecciones', 'filtro' => 'colecciones', 'posts' => 12]); ?>
                </div>
            </div>

        </div>
    </div>

<?
    return ob_get_clean();
}
