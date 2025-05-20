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

        /* Estilos para el calendario personalizado */
        .cal-contenedor {
            background-color: var(--fondo);
            border: var(--borde);
            border-radius: 0.5rem;
            padding: 0.75rem;
            width: 250px;
        }

        .cal-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .cal-mes-anio {
            font-size: 0.875rem;
            font-weight: 500;
            color: #e2e8f0;
        }

        .cal-nav-btn {
            height: 1.75rem;
            width: 1.75rem;
            background-color: transparent;
            padding: 0;
            border: var(--borde);
            border-radius: 0.375rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e0;
        }

        .cal-nav-btn:hover {
            opacity: 1;
            background-color: #2d2d2d;
        }

        .cal-tabla {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.5rem;
        }

        .cal-tabla th {
            color: #a0aec0;
            border-radius: 0.375rem;
            width: calc(100% / 7);
            font-weight: 400;
            font-size: 0.8rem;
            padding-bottom: 0.5rem;
        }

        .cal-tabla td {
            padding: 0;
            text-align: center;
            font-size: 12px;
            position: relative;
        }

        .cal-dia {
            height: 2rem;
            width: 2rem;
            line-height: 2rem;
            padding: 0;
            font-weight: 400;
            border-radius: 0.375rem;
            cursor: pointer;
            display: inline-block;
            color: #e2e8f0;
            transition: background-color 0.1s ease-in-out, color 0.1s ease-in-out;
        }

        .cal-dia:hover {
            background-color: #2d2d2d;
            color: #fff;
        }

        .cal-dia-fuera {
            color: #4a5568;
            opacity: 0.8;
        }

        .cal-dia-fuera:hover {
            background-color: transparent;
        }

        .cal-dia-hoy .cal-dia-num {
            background-color: #2d2d2d;
            color: #e2e8f0;
            border-radius: 0.375rem;
            display: inline-block;
            width: 100%;
            height: 100%;
        }

        .cal-dia-hoy.cal-dia-sel .cal-dia-num {
            background-color: #3182ce;
            color: #fff;
        }

        .cal-dia-sel .cal-dia-num {
            background-color: #3182ce;
            color: #fff;
            border-radius: 0.375rem;
            display: inline-block;
            width: 100%;
            height: 100%;
        }

        .cal-dia-sel .cal-dia-num:hover {
            background-color: #2c5282;
        }

        .cal-dia-deshab {
            color: #4a5568;
            opacity: 0.5;
            cursor: default;
        }

        .cal-dia-deshab:hover {
            background-color: transparent;
        }

        .cal-acciones {
            display: flex;
            justify-content: space-between;
            margin-top: 0.75rem;
        }

        .cal-btn-accion {
            padding: 5px 12px;
            border: var(--borde);
            cursor: pointer;
        }

        .cal-btn-accion:hover {
            background-color: #2d2d2d;
        }

        .cal-contenedor tr {
            padding: 1px 0px;
            display: flex;
            justify-content: space-around;
        }

        .textoFechaLimite svg {
            height: 13px !important;
        }
    </style>
<?
    return ob_get_clean();
}
