<?php
// Refactor(Org): Funcion taskTabs() movida desde app/Pages/taskTabs.php

function taskTabs()
{
    ob_start();
?>

    <div id="menuData" style="display:none;" pestanaActual="">
        <div data-tab="Tareas"></div>
    </div>

    <div class="tabs">
        <div class="tab-content">
            <div class="TABTAREAS">

                <div class="taskConfig">

                    <button class="borrarTareasCompletadas">
                        <? echo $GLOBALS['borradorIcon']; ?>
                    </button>

                    <button class="prioridadTareas">
                        <? echo $GLOBALS['estrellaCuatro']; ?>
                    </button>

                    <button class="tiempoTareas" style="display: none;">
                        <? echo $GLOBALS['tiempoIcon']; ?>
                    </button>

                    <button class="restablecerTareas" style="display: none;">
                        <? echo $GLOBALS['iconViento']; ?>
                    </button>

                    <button class="ORDENPOSTSL" id="ORDENPOSTSL">
                        <? echo $GLOBALS['iconFiltro']; ?>
                    </button>

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

                <div class="tab INICIO" id="Tareas">
                    <div class="contentTareas">
                        <div class="tareasDiv">
                            <? echo formTarea() ?>
                        </div>
                        <? echo publicaciones(['post_type' => 'tarea', 'filtro' => 'tarea', 'posts' => 50, 'tab_id' => 'tareas']); ?>
                        <div class="notasMentales" style="display: none;">
                            <? echo publicaciones(['post_type' => 'notas', 'filtro' => 'notas', 'posts' => 12, 'tab_id' => 'tareas']); ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    <?php // Asegurarse de usar <?php para cerrar el ob_start
    return ob_get_clean();
}
