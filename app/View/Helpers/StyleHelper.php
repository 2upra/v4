<?php
// Helper para funciones que generan estilos CSS dinámicamente

// Refactor(Org): Función formTareaEstilo() movida desde app/View/Components/Task/TaskForm.php
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
