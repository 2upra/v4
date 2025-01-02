<?

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

    if ($filtro === 'tareaPrioridad') {
        $filtro = 'tarea';
    }

    $impIcono = '';
    switch ($imp) {
        case 'baja':
            $impIcono = $GLOBALS['baja'];
            break;
        case 'media':
            $impIcono = $GLOBALS['media'];
            break;
        case 'alta':
            $impIcono = $GLOBALS['alta'];
            break;
        case 'importante':
            $impIcono = $GLOBALS['importante'];
            break;
        default:
            guardarLog("htmlTareas: Importancia no reconocida: $imp");
            $impIcono = '';
    }

    $tipoIcono = '';
    switch ($tipo) {
        case 'una vez':
            $tipoIcono = $GLOBALS['unavez'];
            break;
        case 'habito':
            $tipoIcono = $GLOBALS['habito'];
            break;
        case 'habito rigido':
            $tipoIcono = $GLOBALS['habito'];
            break;
        case 'habito flexible':
            $tipoIcono = $GLOBALS['habito'];
            break;
        case 'meta':
            $tipoIcono = $GLOBALS['meta'];
            break;
        default:
            guardarLog("htmlTareas: Tipo no reconocido: $tipo");
            $tipoIcono = '';
    }

    return generarHtmlTarea($tareaId, $filtro, $titulo, $impIcono, $imp, $tipoIcono, $frecuencia, $estado, $autorId, $tipo, $proxima, $sesion);
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

function generarHtmlTarea($tareaId, $filtro, $titulo, $impIcono, $imp, $tipoIcono, $frecuencia, $estado, $autorId, $tipo, $proxima, $sesion)
{
    $clase = ($estado === 'completada') ? 'completada' : '';
    $estilo = ($estado === 'completada') ? 'style="text-decoration: line-through;"' : '';
    $esHabito = ($tipo === 'habito' || $tipo === 'habito rigido');

    ob_start();
?>

    <li
        class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo $tareaId; ?> <? echo $clase; ?> draggable-element <? echo $estado; ?>"
        filtro="<? echo esc_attr($filtro); ?>"
        tipo-tarea="<? echo esc_attr($tipo); ?>"
        id-post="<? echo $tareaId; ?>"
        autor="<? echo esc_attr($autorId); ?>"
        draggable="true" <? echo $estilo; ?>
        sesion="<? echo esc_attr($sesion) ?>"
        estado="<? echo esc_attr($estado) ?>">

        <button class="completaTarea <? if ($esHabito) echo 'habito'; ?>" data-tarea="<? echo $tareaId; ?>">
            <? echo $GLOBALS['verificadoCirculo']; ?>
        </button>

        <p class="tituloTarea" data-tarea="<? echo $tareaId; ?>">
            <? echo $titulo; ?>
        </p>

        <? if ($esHabito) {
            echo botonesHabitos($tareaId, $frecuencia, $proxima);
        } ?>
   
        <div class="divSesion" data-tarea="<? echo $tareaId; ?>"  style="display: none; cursor: pointer;">
            <p class="sesionTarea">
                <? echo $GLOBALS['carpetaIcon']; ?>
            </p>
        </div>

        <div class="divImportancia" data-tarea="<? echo $tareaId; ?>">
            <p class="importanciaTarea svgtask">
                <? echo $impIcono; ?>
                <span class="tituloImportancia"><? echo $imp; ?></span>
            </p>
        </div>
        <p class="tipoTarea svgtask" style="display: none;"><? echo $tipoIcono; ?></p>
        <p class="estadoTarea" style="display: none;"><? echo $estado; ?></p>
        <? echo opcionesPost($tareaId, $autorId) ?>

        <div class="divArchivado ocultadoAutomatico" data-tarea="<? echo $tareaId; ?>" style="display: none;">
            <p class="archivadoTarea" style="cursor: pointer;">
                <? echo $GLOBALS['archivadoIcon']; ?>
            </p>
        </div>
    </li>
<?
    return ob_get_clean();
}
