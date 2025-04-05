<?php
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
                <button value="habito rigido">Hábito rígido</button>
                <button value="meta" style="display: none;">Meta</button>
            </div>
        </div>
    </div>

    <? echo formTareaEstilo(); ?>

<?
    return ob_get_clean();
}

