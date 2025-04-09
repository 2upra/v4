<?php
// Archivo creado para contener funciones de ayuda para renderizar elementos de Tareas.
// TODO: Mover funciones relevantes de app/Content/Task/View/renderTarea.php aquí.

// Ejemplo de estructura de función (se añadirán las reales más tarde):
/*
function renderTaskElement($task) {
    // Lógica para renderizar un elemento de tarea
}
*/

// Refactor(Org): Funciones movidas desde app/Content/Task/View/renderTarea.php
function htmlTareas($filtro)
{
    $tareaId = get_the_id();
    $titulo = get_the_title($tareaId);
    $imp = get_post_meta($tareaId, 'importancia', true);
    $tipo = get_post_meta($tareaId, 'tipo', true);
    $estado = get_post_meta($tareaId, 'estado', true);
    $frecuencia = get_post_meta($tareaId, 'frecuencia', true);
    $estado = $estado ? $estado : 'pendiente';
    $autorId = get_post_field('post_author', $tareaId);
    $proxima = get_post_meta($tareaId, 'fechaProxima', true);
    $sesion = get_post_meta($tareaId, 'sesion', true);
    $impnum = get_post_meta($tareaId, 'impnum', true);

    if ($filtro === 'tareaPrioridad') {
        $filtro = 'tarea';
    }

    $mostrarIcono = get_user_meta($autorId, 'mostrarIconoTareas', true);
    $mostrarIcono = ($mostrarIcono === '') ? true : (bool)$mostrarIcono;

    $impIcono = obtenerIconoImportancia($imp, $mostrarIcono);
    $tipoIcono = obtenerIconoTipo($tipo, $mostrarIcono);

    return generarHtmlTarea($tareaId, $filtro, $titulo, $impIcono, $imp, $tipoIcono, $frecuencia, $estado, $autorId, $tipo, $proxima, $sesion, $impnum);
}


function obtenerIconoImportancia($imp, $mostrarIcono)
{
    $log = "obtenerIconoImportancia: ";
    if (!$mostrarIcono) {
        $log .= "Se retorna el texto de importancia: $imp";
        // guardarLog($log); // Comentado para evitar side-effects no deseados en Helper
        return $imp;
    }

    switch ($imp) {
        case 'baja':
            $icono = $GLOBALS['baja'] ?? 'B'; // Usar Null coalescing operator por si GLOBALS no está definido
            break;
        case 'media':
            $icono = $GLOBALS['media'] ?? 'M';
            break;
        case 'alta':
            $icono = $GLOBALS['alta'] ?? 'A';
            break;
        case 'importante':
            $icono = $GLOBALS['importante'] ?? 'I';
            break;
        default:
            $icono = '';
            $log .= "Importancia no reconocida: $imp, ";
    }
    $log .= "Se retorna el icono de importancia: $icono";
    // guardarLog($log); // Comentado para evitar side-effects no deseados en Helper
    return $icono;
}

function obtenerIconoTipo($tipo, $mostrarIcono)
{
    $log = "obtenerIconoTipo: ";
    if (!$mostrarIcono) {
        $log .= "Se retorna el texto de tipo: $tipo";
        // guardarLog($log); // Comentado para evitar side-effects no deseados en Helper
        return $tipo;
    }

    switch ($tipo) {
        case 'una vez':
            $icono = $GLOBALS['unavez'] ?? '1'; // Usar Null coalescing operator
            break;
        case 'habito':
        case 'habito rigido':
        case 'habito flexible':
            $icono = $GLOBALS['habito'] ?? 'H';
            break;
        case 'meta':
            $icono = $GLOBALS['meta'] ?? 'G';
            break;
        default:
            $icono = '';
            $log .= "Tipo no reconocido: $tipo, ";
    }

    $log .= "Se retorna el icono de tipo: $icono";
    // guardarLog($log); // Comentado para evitar side-effects no deseados en Helper
    return $icono;
}

function obtenerFrecuenciaTexto($frecuencia)
{
    if ($frecuencia == 1) {
        return 'diaria';
    } elseif ($frecuencia == 7) {
        return 'semanal';
    } elseif ($frecuencia >= 27 && $frecuencia <= 32) {
        return 'mensual';
    } elseif ($frecuencia == 365) {
        return 'anual';
    } else {
        return "{$frecuencia}d";
    }
}


function botonesHabitos($tareaId, $frecuencia, $proxima)
{
    $frecuenciaTexto = obtenerFrecuenciaTexto($frecuencia);
    $hoy = date('Y-m-d');
    $dif = floor((strtotime($proxima) - strtotime($hoy)) / (60 * 60 * 24));
    $txt = '';
    $simbolo = '';
    $claseNegativo = '';

    if ($dif == 0) {
        $txt = 'Hoy';
    } elseif ($dif == 1) {
        $txt = 'Mañana';
    } elseif ($dif == -1) {
        $txt = 'Ayer';
        $claseNegativo = 'diaNegativo';
    } elseif ($dif > 1) {
        $txt = $dif . 'd';
    } elseif ($dif < -1) {
        $txt = abs($dif) . 'd';
        $simbolo = '-';
        $claseNegativo = 'diaNegativo';
    }

    ob_start();
?>
    <div class="divProxima" data-tarea="<? echo $tareaId; ?>" style="cursor: pointer;">
        <p class="proximaTarea svgtask">
            <span class="textoProxima <? echo $claseNegativo; ?>"><? echo $simbolo . $txt; ?></span>
        </p>
    </div>
    <div class="divFrecuencia" data-tarea="<? echo $tareaId; ?>" style="cursor: pointer;">
        <p class="frecuenciaTarea svgtask">
            <span class="tituloFrecuencia"><? echo $frecuenciaTexto; ?></span>
        </p>
    </div>
<?
    return ob_get_clean();
}

function generarHtmlTarea($tareaId, $filtro, $titulo, $impIcono, $imp, $tipoIcono, $frecuencia, $estado, $autorId, $tipo, $proxima, $sesion, $impnum)
{
    $clase = ($estado === 'completada') ? 'completada' : '';
    $estilo = ($estado === 'completada') ? 'style="text-decoration: line-through;"' : '';
    $esHabito = ($tipo === 'habito' || $tipo === 'habito rigido');

    $mostrarIcono = get_user_meta($autorId, 'mostrarIconoTareas', true);
    $mostrarIcono = ($mostrarIcono === '') ? false : (bool)$mostrarIcono; // Nota: Originalmente era true por defecto, aquí se cambió a false si está vacío.
    $esSubtarea = get_post_meta($tareaId, 'subtarea', true);
    $hoy = date('Y-m-d');
    $dif = floor((strtotime($proxima) - strtotime($hoy)) / (60 * 60 * 24));
    ob_start();
?>

    <li
        class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo $tareaId; ?> <? echo $clase; ?> draggable-element <? echo $estado; ?> <? if ($esSubtarea) echo 'subtarea';?>"
        filtro="<? echo esc_attr($filtro); ?>"
        tipo-tarea="<? echo esc_attr($tipo); ?>"
        id-post="<? echo $tareaId; ?>"
        autor="<? echo esc_attr($autorId); ?>"
        draggable="true" <? echo $estilo; ?>
        sesion="<? echo esc_attr(empty($sesion) ? ($estado === 'archivado' ? 'archivado' : 'general') : $sesion); ?>"
        estado="<? echo esc_attr($estado) ?>"
        impnum="<? echo esc_attr($impnum) ?>"
        importancia="<? echo esc_attr($imp) ?>"
        subtarea="<? if ($esSubtarea) echo 'true'; else echo 'false'; ?>"
        padre="<? echo esc_attr($esSubtarea) ?>"
        dif="<? echo esc_attr($dif) ?>"
        >

        <button class="completaTarea <? if ($esHabito) echo 'habito'; ?>" data-tarea="<? echo $tareaId; ?>">
            <? echo $GLOBALS['verificadoCirculo'] ?? '[ ]'; // Usar Null coalescing operator ?>
        </button>

        <p class="tituloTarea" data-tarea="<? echo $tareaId; ?>">
            <? echo $titulo; ?>
        </p>

        <p class="idtarea" style="display: none; font-size: 11px;">
            <? echo $tareaId ?>
        </p>

        <? if ($esHabito) {
            echo botonesHabitos($tareaId, $frecuencia, $proxima);
        } ?>

        <div class="divSesion" data-tarea="<? echo $tareaId; ?>" style="display: none; cursor: pointer;">
            <p class="sesionTarea">
                <? echo $GLOBALS['carpetaIcon'] ?? '[S]'; // Usar Null coalescing operator ?>
            </p>
        </div>

        <div class="divImportancia" data-tarea="<? echo $tareaId; ?>">
            <p class="importanciaTarea <? if ($mostrarIcono) echo 'svgtask'; ?>">
                <? if ($mostrarIcono) : ?>
                    <? echo $impIcono; ?>
                <? else : ?>
                    <span class="tituloImportancia"><? echo $imp; ?></span>
                <? endif; ?>
            </p>
        </div>

        <p class="tipoTarea svgtask" style="display: none;"><? echo $tipoIcono; ?></p>
        <p class="estadoTarea" style="display: none;"><? echo $estado; ?></p>
        <? 
        // Asegúrate de que la función opcionesPost esté disponible globalmente o inclúyela donde sea necesario.
        if (function_exists('opcionesPost')) {
            echo opcionesPost($tareaId, $autorId);
        } else {
            // Opcional: Muestra un marcador de posición o registra un error si la función no existe.
            echo '<!-- opcionesPost no disponible -->';
        }
        ?>

        <div class="divArchivado ocultadoAutomatico" data-tarea="<? echo $tareaId; ?>" style="display: none;">
            <p class="archivadoTarea" style="cursor: pointer;">
                <? echo $GLOBALS['archivadoIcon'] ?? '[A]'; // Usar Null coalescing operator ?>
            </p>
        </div>
    </li>
<?
    return ob_get_clean();
}

?>