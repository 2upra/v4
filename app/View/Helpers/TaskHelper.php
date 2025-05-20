<?
function htmlTareas($filtro)
{
    $id = get_the_id();
    $meta = get_post_meta($id);

    $titulo = get_the_title($id);
    $imp = $meta['importancia'][0] ?? 'media';
    $tipo = $meta['tipo'][0] ?? 'una vez';
    $estado = $meta['estado'][0] ?? 'pendiente';
    $frec = (int)($meta['frecuencia'][0] ?? 1);
    $autorId = get_post_field('post_author', $id);
    $proxima = $meta['fechaProxima'][0] ?? date('Y-m-d');
    $sesion = $meta['sesion'][0] ?? '';
    $impnum = (int)($meta['impnum'][0] ?? 0);

    $filtroHtml = ($filtro === 'tareaPrioridad') ? 'tarea' : $filtro;

    $mostrarIcono = filter_var(get_user_meta($autorId, 'mostrarIconoTareas', true) ?: true, FILTER_VALIDATE_BOOLEAN);

    $impIcono = obtenerIconoImportancia($imp, $mostrarIcono);
    $tipoIcono = obtenerIconoTipo($tipo, $mostrarIcono);

    return generarHtmlTarea($id, $filtroHtml, $titulo, $impIcono, $imp, $tipoIcono, $frec, $estado, $autorId, $tipo, $proxima, $sesion, $impnum);
}

function obtenerIconoImportancia($imp, $mostrarIcono)
{
    if (!$mostrarIcono) return $imp;
    $iconos = [
        'baja' => $GLOBALS['baja'] ?? 'B',
        'media' => $GLOBALS['media'] ?? 'M',
        'alta' => $GLOBALS['alta'] ?? 'A',
        'importante' => $GLOBALS['importante'] ?? 'I'
    ];
    return $iconos[$imp] ?? '';
}

function obtenerIconoTipo($tipo, $mostrarIcono)
{
    if (!$mostrarIcono) return $tipo;
    $iconos = [
        'una vez' => $GLOBALS['unavez'] ?? '1',
        'habito' => $GLOBALS['habito'] ?? 'H',
        'habito rigido' => $GLOBALS['habito'] ?? 'H',
        'habito flexible' => $GLOBALS['habito'] ?? 'H',
        'meta' => $GLOBALS['meta'] ?? 'G'
    ];
    return $iconos[$tipo] ?? '';
}

function obtenerFrecuenciaTexto($frec)
{
    return match (true) {
        $frec == 1 => 'diaria',
        $frec == 7 => 'semanal',
        $frec >= 27 && $frec <= 32 => 'mensual',
        $frec == 365 => 'anual',
        default => "{$frec}d",
    };
}

function botonesHabitos($id, $frec, $proxima)
{
    $frecTxt = obtenerFrecuenciaTexto($frec);
    $hoy = date('Y-m-d');
    $dif = (strtotime($proxima) - strtotime($hoy)) / (60 * 60 * 24);
    $txt = '';
    $simbolo = '';
    $claseNeg = '';

    if ($dif == 0) $txt = 'Hoy';
    elseif ($dif == 1) $txt = 'Mañana';
    elseif ($dif == -1) {
        $txt = 'Ayer';
        $claseNeg = 'diaNegativo';
    } elseif ($dif > 1) $txt = $dif . 'd';
    elseif ($dif < -1) {
        $txt = abs($dif) . 'd';
        $simbolo = '-';
        $claseNeg = 'diaNegativo';
    }

    ob_start();
?>
    <div class="divProxima" data-tarea="<? echo $id; ?>" style="cursor: pointer;">
        <p class="proximaTarea svgtask">
            <span class="textoProxima <? echo $claseNeg; ?>"><? echo $simbolo . $txt; ?></span>
        </p>
    </div>
    <div class="divFrecuencia" data-tarea="<? echo $id; ?>" style="cursor: pointer;">
        <p class="frecuenciaTarea svgtask">
            <span class="tituloFrecuencia"><? echo $frecTxt; ?></span>
        </p>
    </div>
<?
    return ob_get_clean();
}

function generarHtmlTarea($id, $filtro, $titulo, $impIcono, $imp, $tipoIcono, $frec, $est, $autorId, $tipo, $proxima, $sesion, $impnum)
{
    $esCompletada = ($est === 'completada');
    $esHabito = ($tipo === 'habito' || $tipo === 'habito rigido');
    $esSubtarea = get_post_meta($id, 'subtarea', true);
    $mostrarIcono = filter_var(get_user_meta($autorId, 'mostrarIconoTareas', true) ?: false, FILTER_VALIDATE_BOOLEAN);
    $difDias = floor((strtotime($proxima) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
    $sesionHtml = esc_attr(empty($sesion) ? ($est === 'archivado' ? 'archivado' : 'general') : $sesion);

    ob_start();
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo $id; ?> <? echo $esCompletada ? 'completada' : ''; ?> draggable-element <? echo esc_attr($est); ?> <? echo $esSubtarea ? 'subtarea' : ''; ?>"
        filtro="<? echo esc_attr($filtro); ?>"
        tipo-tarea="<? echo esc_attr($tipo); ?>"
        id-post="<? echo $id; ?>"
        autor="<? echo esc_attr($autorId); ?>"
        draggable="true" <? echo $esCompletada ? 'style="text-decoration: line-through;"' : ''; ?>
        sesion="<? echo $sesionHtml; ?>"
        estado="<? echo esc_attr($est) ?>"
        impnum="<? echo esc_attr($impnum) ?>"
        importancia="<? echo esc_attr($imp) ?>"
        subtarea="<? echo $esSubtarea ? 'true' : 'false'; ?>"
        padre="<? echo esc_attr($esSubtarea ?: '0'); ?>"
        dif="<? echo esc_attr($difDias); ?>">

        <button class="completaTarea <? echo $esHabito ? 'habito' : ''; ?>" data-tarea="<? echo $id; ?>">
            <? echo $GLOBALS['verificadoCirculo'] ?? '[ ]'; ?>
        </button>

        <p class="tituloTarea" data-tarea="<? echo $id; ?>">
            <? echo esc_html($titulo); ?>
        </p>

        <p class="idtarea" style="display: none; font-size: 11px;">
            <? echo $id; ?>
        </p>

        <? if ($esHabito) echo botonesHabitos($id, $frec, $proxima); ?>

        <div class="divSesion" data-tarea="<? echo $id; ?>" style="display: none; cursor: pointer;">
            <p class="sesionTarea">
                <? echo $GLOBALS['carpetaIcon'] ?? ''; // Icono de carpeta por defecto 
                ?>
            </p>
        </div>

        <div class="divImportancia" data-tarea="<? echo $id; ?>">
            <p class="importanciaTarea <? echo $mostrarIcono ? 'svgtask' : ''; ?>">
                <? echo $mostrarIcono ? $impIcono : "<span class=\"tituloImportancia\">" . esc_html($imp) . "</span>"; ?>
            </p>
        </div>

        <p class="tipoTarea svgtask" style="display: none;"><? echo $tipoIcono; ?></p>
        <p class="estadoTarea" style="display: none;"><? echo esc_html($est); ?></p>

        <? if (function_exists('opcionesPost')) echo opcionesPost($id, $autorId);
        else echo '<!-- Fn:opcionesPost Indisponible -->';
        ?>

        <div class="divArchivado ocultadoAutomatico" data-tarea="<? echo $id; ?>" style="display: none;">
            <p class="archivadoTarea" style="cursor: pointer;">
                <? echo $GLOBALS['archivadoIcon'] ?? '[A]'; ?>
            </p>
        </div>
    </li>
<?
    return ob_get_clean();
}
?>