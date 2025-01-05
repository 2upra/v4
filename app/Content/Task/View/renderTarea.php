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
        guardarLog($log);
        return $imp;
    }

    switch ($imp) {
        case 'baja':
            $icono = $GLOBALS['baja'];
            break;
        case 'media':
            $icono = $GLOBALS['media'];
            break;
        case 'alta':
            $icono = $GLOBALS['alta'];
            break;
        case 'importante':
            $icono = $GLOBALS['importante'];
            break;
        default:
            $icono = '';
            $log .= "Importancia no reconocida: $imp, ";
    }
    $log .= "Se retorna el icono de importancia: $icono";
    //guardarLog($log);
    return $icono;
}

function obtenerIconoTipo($tipo, $mostrarIcono)
{
    $log = "obtenerIconoTipo: ";
    if (!$mostrarIcono) {
        $log .= "Se retorna el texto de tipo: $tipo";
        //guardarLog($log);
        return $tipo;
    }

    switch ($tipo) {
        case 'una vez':
            $icono = $GLOBALS['unavez'];
            break;
        case 'habito':
        case 'habito rigido':
        case 'habito flexible':
            $icono = $GLOBALS['habito'];
            break;
        case 'meta':
            $icono = $GLOBALS['meta'];
            break;
        default:
            $icono = '';
            $log .= "Tipo no reconocido: $tipo, ";
    }

    $log .= "Se retorna el icono de tipo: $icono";
    //guardarLog($log);
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
    $mostrarIcono = ($mostrarIcono === '') ? false : (bool)$mostrarIcono;

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
        estado="<? echo esc_attr($estado) ?>"
        impnum="<? echo esc_attr($impnum) ?>"
        importancia="<? echo esc_attr($imp) ?>">

        <button class="completaTarea <? if ($esHabito) echo 'habito'; ?>" data-tarea="<? echo $tareaId; ?>">
            <? echo $GLOBALS['verificadoCirculo']; ?>
        </button>

        <p class="tituloTarea" data-tarea="<? echo $tareaId; ?>">
            <? echo $titulo; ?>
        </p>

        <p class="idtarea" style="display: none;">
            <? echo $tareaId ?>
        </p>

        <? if ($esHabito) {
            echo botonesHabitos($tareaId, $frecuencia, $proxima);
        } ?>

        <div class="divSesion" data-tarea="<? echo $tareaId; ?>" style="display: none; cursor: pointer;">
            <p class="sesionTarea">
                <? echo $GLOBALS['carpetaIcon']; ?>
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
