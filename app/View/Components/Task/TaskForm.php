<?
// Refactor(Org): Funciones formTarea() y formTareaEstilo() movidas desde app/Content/Task/logicTareas.php
// Refactor(Org): Función formTareaEstilo() movida a app/View/Helpers/StyleHelper.php

function formTarea()
{
    ob_start();
    ?>
    <div class="bloque tareasbloque">
        <input type="text" name="titulo" placeholder="Agregar nueva tarea" id="tituloTarea">

        <div class="selectorIcono sImportancia" id="sImportancia">
            <span class="icono">
                <? echo $GLOBALS['importancia']; ?>baja
            </span>
        </div>

        <div class="A1806241" id="sImportancia-sImportancia">
            <div class="A1806242">
                <button value="baja">baja</button>
                <button value="media">media</button>
                <button value="alta">alta</button>
                <button value="importante">importante</button>
            </div>
        </div>

        <div class="selectorIcono sTipo" id="sTipo">
            <span class="icono"><? echo $GLOBALS['tipoTarea']; ?>Una vez</span>
        </div>

        <div class="A1806241" id="sTipo-sTipo">
            <div class="A1806242">
                <button value="una vez">Una vez</button>
                <button value="habito">Hábito flexible</button>
                <button value="habito rigido" style="display: none;">Hábito rígido</button>
                <button value="meta" style="display: none;">Meta</button>
            </div>
        </div>

        <div class="selectorIcono sFechaLimite" id="sFechaLimite">
            <span class="icono"><? echo $GLOBALS['calendario']; ?></span>
            <? // El texto se manejará por JS ?>
        </div>

        <div class="selectorIcono sSeccion" id="sSeccion">
            <span class="icono"><? echo $GLOBALS['meterCarpeta'] ?? '[C]'; ?></span>
            <span class="nombreSeccionSeleccionada" data-placeholder="Seleccionar sección"></span>
            <? // El texto o la selección de la sección se manejará por JS ?>
        </div>

        <div id="modalAsignarSeccionForm" class="modal-asignar-seccion modal bloque" style="display: none; position: absolute; z-index: 10001;"> 
            <div class="div-asignar-seccion-input" style="gap: 5px;"> 
                <input type="text" id="inputNuevaSeccionModalForm" placeholder="Crear sección" maxlength="30"> 
                <button id="btnCrearAsignarSeccionModalForm" style="display: none;">Crear</button> 
            </div> 
            <div id="listaSeccionesExistentesModalForm"></div> 
            <button id="btnCerrarModalSeccionForm" style="display: none;">Cerrar</button> 
        </div>

        <!-- Calendario personalizado -->
        <div id="calCont" class="cal-contenedor" style="display:none; position:absolute; z-index:1001;">
            <div class="cal-nav">
                <button type="button" id="calPrev" class="cal-nav-btn">
                    <
                </button>
                <span id="calMesAnio" class="cal-mes-anio"></span>
                <button type="button" id="calNext" class="cal-nav-btn">
                    >
                </button>
            </div>
            <table class="cal-tabla">
                <thead>
                    <tr id="calDiasSemana">
                        <!-- Los días de la semana se generarán aquí por JS -->
                    </tr>
                </thead>
                <tbody id="calBody" class="cal-body">
                    <!-- Los días del mes se generarán aquí por JS -->
                </tbody>
            </table>
            <div class="cal-acciones">
                <button type="button" id="calHoyBtn" class="cal-btn-accion">Hoy</button>
                <button type="button" id="calBorrarBtn" class="cal-btn-accion">Borrar</button>
            </div>
        </div>

        <input type="date" id="inputFechaLimite" style="display: none;">
    </div>

    <? echo formTareaEstilo(); // Aquí podrías agregar los estilos del calendario o en un archivo CSS global ?>
    <? // Para este ejemplo, los estilos CSS irán separados más abajo. ?>
    <?
    return ob_get_clean();
}
