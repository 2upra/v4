<?

function htmlTareas($filtro)
{
    $tareaId     = get_the_id();
    $titulo      = get_the_title($tareaId);
    $importancia = get_post_meta($tareaId, 'importancia', true);
    $tipo        = get_post_meta($tareaId, 'tipo', true);
    $estado      = get_post_meta($tareaId, 'estado', true);
    $estado      = $estado ? $estado : 'pendiente';
    $autorId    = get_post_field('post_author', $tareaId);

    $impIcono = '';
    switch ($importancia) {
        case 'poca':
            $impIcono = $GLOBALS['poca'];
            break;
        case 'media':
            $impIcono = $GLOBALS['media'];
            break;
        case 'alta':
            $impIcono = $GLOBALS['alta'];
            break;
        case 'urgente':
            $impIcono = $GLOBALS['urgente'];
            break;
        default:
            guardarLog("htmlTareas: Importancia no reconocida: $importancia");
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
        case 'meta':
            $tipoIcono = $GLOBALS['meta'];
            break;
        default:
            guardarLog("htmlTareas: Tipo no reconocido: $tipo");
            $tipoIcono = '';
    }

    $claseCompletada = ($estado === 'completada') ? 'completada' : '';
    $estiloTachado = ($estado === 'completada') ? 'style="text-decoration: line-through;"' : '';

    ob_start();
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo get_the_ID(); ?> <? echo $claseCompletada; ?> draggable-element"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($autorId); ?>"
        draggable="true" <? echo $estiloTachado; ?>>
        <button class="completaTarea" data-tarea="<? echo $tareaId; ?>">
            <? echo $GLOBALS['verificadoCirculo']; ?>
        </button>
        <p class="tituloTarea" data-tarea="<? echo $tareaId; ?>" style="border: none; outline: none; box-shadow: none;">
            <? echo $titulo; ?>
        </p>
        <div class="divImportancia" data-tarea="<? echo $tareaId; ?>">
            <p class="importanciaTarea svgtask">
                <? echo $impIcono; ?>
                <span class="tituloImportancia"><? echo $importancia; ?></span>
            </p>
        </div>
        <p class="tipoTarea svgtask"><? echo $tipoIcono; ?></p>
        <p class="estadoTarea" style="display: none;"><? echo $estado; ?></p>
        <? echo opcionesPost($tareaId, $autorId) ?>
    </li>
<?
    return ob_get_clean();
}
