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
                        <button class="filtrosboton">
                            <?
                            $user_id = get_current_user_id();
                            $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true);
                            $filtro_tiempo = $filtro_tiempo === '' ? 0 : intval($filtro_tiempo);

                            $nombres_filtros = array(
                                0 => 'Feed',
                                1 => 'Reciente',
                                2 => 'Semanal',
                                3 => 'Mensual'
                            );

                            $nombre_filtro = isset($nombres_filtros[$filtro_tiempo]) ? $nombres_filtros[$filtro_tiempo] : 'Feed';
                            echo $nombre_filtro . ' ' . $GLOBALS['iconoflechaArriAba'];
                            ?>
                        </button>

                        <?
                        $filtroTiempo = get_user_meta(get_current_user_id(), 'filtroTiempo', true);
                        ?>


                        <div class="A1806241" id="filtrosMenu-default">
                            <div class="A1806242">
                                <button class="filtroFeed <? echo ($filtroTiempo == 0 || $filtroTiempo === '') ? 'filtroSelec' : ''; ?>">Para mí</button>
                                <button class="filtroReciente <? echo ($filtroTiempo == 1) ? 'filtroSelec' : ''; ?>">Recientes</button>
                                <button class="filtroSemanal <? echo ($filtroTiempo == 2) ? 'filtroSelec' : ''; ?>">Top Semanal</button>
                                <button class="filtroMensual <? echo ($filtroTiempo == 3) ? 'filtroSelec' : ''; ?>">Top Mensual</button>
                            </div>
                        </div>
                        <button class="ORDENPOSTSL" id="ORDENPOSTSL">Opciones<? echo $GLOBALS['flechaAbajo']; ?></button>
                        <div class="opcionCheckBox modal" id="filtrosPost" style="display: none;">

                            <div class="opcionCheck">
                                <div>
                                    <label>Ocultar ya descargados</label>
                                    <p class="description">No se mostraran los samples que ya hayas descargado</p>
                                </div>


                                <label class="switch">
                                    <input type="checkbox" name="ocultarDescargados" id="ocultarDescargados">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="opcionCheck">
                                <div>
                                    <label>Ocultar guardados en coleccion</label>
                                    <p class="description">No se mostraran los samples que esten guardadas en algunas de colecciones</p>
                                </div>

                                <label class="switch">
                                    <input type="checkbox" name="ocultarEnColeccion" id="ocultarEnColeccion">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="opcionCheck">
                                <div>
                                    <label>Mostrar solo con likes</label>
                                    <p class="description">Solo se mostraran los samples con tu like marcado</p>
                                </div>

                                <label class="switch">
                                    <input type="checkbox" name="mostrarMeGustan" id="mostrarMeGustan">
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
