<?

function taskTabs()
{
    ob_start();
?>

    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Tareas"></div>
    </div>

    <div class="tabs">
        <div class="tab-content">
            <div class="BPLBDE UP">
                <div class="FDGEDF">
                    <p id="resultadosPost-sampleList" typepost="colecciones"></p>
                    <div class="OPCDGED">

                        <button class="restablecerBusqueda coleccionRestablecer" style="display: none;">Restablecer filtros</button>

                        <button class="ORDENPOSTSL" id="ORDENPOSTSL">Opciones<? echo $GLOBALS['flechaAbajo']; ?></button>
                        <div class="opcionCheckBox modal" id="filtrosPost" style="display: none;">



                            <div class="opcionCheck">
                                <div>
                                    <label>Ocultar tareas completadas</label>
                                    <p class="description"></p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="ocultarCompletadas" id="ocultarCompletadas">
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
                <div class="tab INICIO S4K7I3" id="Tareas">
                    <div class="BPLBDE">
                        <div class="tareasDiv">
                            <? echo formTarea() ?>
                        </div>
                        <? echo publicaciones(['post_type' => 'tarea', 'filtro' => 'tarea', 'posts' => 12]); ?>
                    </div>
                </div>

            </div>
        </div>

    <?
    return ob_get_clean();
}
