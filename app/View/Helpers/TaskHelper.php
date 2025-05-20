<?
// app/View/Helpers/TaskHelper.php

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
    $proxima = $meta['fechaProxima'][0] ?? null; // Mantener null si no existe
    $fechaLimite = $meta['fechaLimite'][0] ?? null;
    $sesion = $meta['sesion'][0] ?? '';
    $impnum = (int)($meta['impnum'][0] ?? 0);

    $filtroHtml = ($filtro === 'tareaPrioridad') ? 'tarea' : $filtro;

    $mostrarIcono = filter_var(get_user_meta($autorId, 'mostrarIconoTareas', true) ?: true, FILTER_VALIDATE_BOOLEAN);

    $impIcono = obtenerIconoImportancia($imp, $mostrarIcono);
    $tipoIcono = obtenerIconoTipo($tipo, $mostrarIcono);

    return generarHtmlTarea($id, $filtroHtml, $titulo, $impIcono, $imp, $tipoIcono, $frec, $estado, $autorId, $tipo, $proxima, $fechaLimite, $sesion, $impnum);
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

function calcularTextoTiempo($fechaReferencia)
{
    $valRetDefecto = ['txt' => '', 'simbolo' => '', 'claseNeg' => '', 'diasDif' => 0];

    if (empty($fechaReferencia)) {
        return $valRetDefecto;
    }
    
    $tsReferencia = strtotime($fechaReferencia);

    // Si strtotime falla o la fecha es anterior a epoch (ej. '0000-00-00' puede dar ts negativo)
    if ($tsReferencia === false || $tsReferencia < 0) { 
        return $valRetDefecto;
    }

    $tsHoy = strtotime(date('Y-m-d')); // Medianoche de hoy
    $difDias = floor(($tsReferencia - $tsHoy) / (60 * 60 * 24));

    $txt = '';
    $simbolo = '';
    $claseNeg = '';

    if ($difDias == 0) $txt = 'Hoy';
    elseif ($difDias == 1) $txt = 'Mañana';
    elseif ($difDias == -1) {
        $txt = 'Ayer';
        $claseNeg = 'diaNegativo';
    } elseif ($difDias > 1) $txt = $difDias . 'd';
    elseif ($difDias < -1) {
        $txt = abs($difDias) . 'd';
        $simbolo = '-';
        $claseNeg = 'diaNegativo';
    }
    return ['txt' => $txt, 'simbolo' => $simbolo, 'claseNeg' => $claseNeg, 'diasDif' => $difDias];
}

function botonesHabitos($id, $frec, $proxima)
{
    $frecTxt = obtenerFrecuenciaTexto($frec);
    $tiempo = calcularTextoTiempo($proxima); // $proxima puede ser null
    
    // Si $proxima era null o inválida, $tiempo['txt'] estará vacío.
    // No se mostrará texto de tiempo, lo cual es correcto.
    ob_start();
?>
    <div class="divProxima" data-tarea="<? echo $id; ?>" style="cursor: pointer;">
        <p class="proximaTarea svgtask">
            <span class="textoProxima <? echo $tiempo['claseNeg']; ?>"><? echo $tiempo['simbolo'] . $tiempo['txt']; ?></span>
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

function botonesMeta($id, $fechaLimite)
{
    $tiempo = calcularTextoTiempo($fechaLimite);

    if (empty($tiempo['txt'])) { // Si no hay texto, no mostrar nada.
        return '';
    }

    ob_start();
?>
    <div class="divFechaLimite" data-tarea="<? echo $id; ?>" style="cursor: pointer;">
        <p class="fechaLimiteMeta svgtask">
            <span class="textoFechaLimite <? echo $tiempo['claseNeg']; ?>"><? echo $tiempo['simbolo'] . $tiempo['txt']; ?></span>
        </p>
    </div>
<?
    return ob_get_clean();
}

function generarHtmlTarea($id, $filtro, $titulo, $impIcono, $imp, $tipoIcono, $frec, $est, $autorId, $tipo, $proxima, $fechaLimite, $sesion, $impnum)
{
    $esCompletada = ($est === 'completada');
    $esHabito = ($tipo === 'habito' || $tipo === 'habito rigido' || $tipo === 'habito flexible');
    $esMeta = ($tipo === 'meta');
    $esSubtarea = get_post_meta($id, 'subtarea', true);
    $mostrarIcono = filter_var(get_user_meta($autorId, 'mostrarIconoTareas', true) ?: false, FILTER_VALIDATE_BOOLEAN);

    $tiempoProxima = calcularTextoTiempo($proxima);
    $difDiasHabito = ($esHabito && !empty($tiempoProxima['txt'])) ? $tiempoProxima['diasDif'] : 0;

    $tiempoLimite = calcularTextoTiempo($fechaLimite);
    $difDiasMeta = ($esMeta && !empty($tiempoLimite['txt'])) ? $tiempoLimite['diasDif'] : 0;
    
    $limiteTieneTextoValido = !empty($tiempoLimite['txt']);

    $difDiasActivo = $esHabito ? $difDiasHabito : ($esMeta ? $difDiasMeta : 0);

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
        dif="<? echo esc_attr($difDiasActivo); ?>"
        data-fechalimite="<? echo esc_attr($fechaLimite ?? ''); ?>"
        data-proxima="<? echo esc_attr($proxima ?? ''); ?>">

        <button class="completaTarea <? echo ($esHabito && $tipo !== 'habito rigido') ? 'habito' : ''; ?> <? echo ($tipo === 'habito flexible') ? 'habitoFlexible' : ''; ?>" data-tarea="<? echo $id; ?>">
            <? echo $GLOBALS['verificadoCirculo'] ?? '[ ]'; ?>
        </button>

        <p class="tituloTarea" data-tarea="<? echo $id; ?>">
            <? echo esc_html($titulo); ?>
        </p>

        <p class="idtarea" style="display: none; font-size: 11px;">
            <? echo $id; ?>
        </p>

        <?
        if ($esHabito) {
            echo botonesHabitos($id, $frec, $proxima);
        } elseif ($esMeta && $limiteTieneTextoValido) { // Solo mostrar si es Meta y fechaLimite es válida y parseable
            echo botonesMeta($id, $fechaLimite);
        }
        ?>

        <div class="divSesion" data-tarea="<? echo $id; ?>" style="display: none; cursor: pointer;">
            <p class="sesionTarea">
                <? echo $GLOBALS['carpetaIcon'] ?? ''; ?>
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
        
        <? // El divFechaLimite con el icono de calendario (para añadir/editar fecha)
           // Solo se muestra si NO es un hábito Y NO tiene ya una fecha límite válida.
        if (!$esHabito && !$limiteTieneTextoValido) : ?>
            <div class="divFechaLimite ocultadoAutomatico" data-tarea="<? echo $id; ?>" style="display: none; cursor: pointer;">
                <p>
                    <span class="textoFechaLimite">
                        <? echo $GLOBALS['calendario'] ?? '[F]'; ?>
                    </span>
                </p>
            </div>
        <? endif; ?>

    </li>
<?
    return ob_get_clean();
}
?>