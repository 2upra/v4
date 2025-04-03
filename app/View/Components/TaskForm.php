<?php
// Refactor(Org): Funciones formTarea() y formTareaEstilo() movidas desde app/Content/Task/logicTareas.php

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

function formTareaEstilo()
{
    ob_start();
?>
    <style>
        span.icono p {
            font-size: 12px;
        }

        span.icono {
            display: flex;
            flex-direction: row;
            font-size: 11px;
            gap: 6px;
            padding: 0px 5px;
            border-radius: 100px;
            align-items: center;
            justify-content: center;
            width: max-content;
            opacity: 0.9;
            cursor: pointer;
        }

        .selectorIcono {
            padding: 10px 0px;
        }

        .bloque.tareasbloque svg {
            cursor: pointer;
        }

        .bloque.tareasbloque {
            display: flex;
            flex-direction: row;
            height: 40px;
            padding: 5px;
            align-items: center;
            padding-right: 20px;
            background: unset;
        }

        .tareasbloque input {
            background: none;
        }

        .LNVHED.no-tareas {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 100px;
        }
    </style>
<?
    return ob_get_clean();
}
