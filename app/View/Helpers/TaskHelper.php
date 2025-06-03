<?
// app/View/Helpers/TaskHelper.php
$depurarTitulo = false;

function htmlTareas($filtro)
{
    $id = get_the_id();

    $titulo      = get_the_title($id);
    $imp         = get_post_meta($id, 'importancia', true) ?: 'media';
    $tipo        = get_post_meta($id, 'tipo', true) ?: 'una vez';
    $estado      = get_post_meta($id, 'estado', true) ?: 'pendiente';
    $frec        = (int) (get_post_meta($id, 'frecuencia', true) ?: 1);
    $autorId     = get_post_field('post_author', $id);
    $proxima     = get_post_meta($id, 'fechaProxima', true) ?: null;
    $fechaLimite = get_post_meta($id, 'fechaLimite', true) ?: null;
    $sesion      = get_post_meta($id, 'sesion', true) ?: '';
    $impnum      = (int) (get_post_meta($id, 'impnum', true) ?: 0);

    $filtroHtml = ($filtro === 'tareaPrioridad') ? 'tarea' : $filtro;

    $mostrarIconoMeta = get_user_meta($autorId, 'mostrarIconoTareas', true);
    // Si el meta es string vacío (no seteado), default a true. Sino, valida el valor.
    $mostrarIcono     = ($mostrarIconoMeta === '') ? false : filter_var($mostrarIconoMeta, FILTER_VALIDATE_BOOLEAN);

    $impIcono  = obtenerIconoImportancia($imp, $mostrarIcono);
    $tipoIcono = obtenerIconoTipo($tipo, $mostrarIcono);

    // Pasamos $mostrarIcono a generarHtmlTarea
    return generarHtmlTarea($id, $filtroHtml, $titulo, $impIcono, $imp, $tipoIcono, $frec, $estado, $autorId, $tipo, $proxima, $fechaLimite, $sesion, $impnum, $mostrarIcono);
}

function obtenerIconoImportancia($imp, $mostrarIcono)
{
    if (!$mostrarIcono)
        return esc_html($imp);  // Escapar por si se muestra como texto
    $iconos = [
        'baja'       => $GLOBALS['baja'] ?? 'B',
        'media'      => $GLOBALS['media'] ?? 'M',
        'alta'       => $GLOBALS['alta'] ?? 'A',
        'importante' => $GLOBALS['importante'] ?? 'I'
    ];
    return $iconos[$imp] ?? '';
}

function obtenerIconoTipo($tipo, $mostrarIcono)
{
    if (!$mostrarIcono)
        return esc_html($tipo);  // Escapar por si se muestra como texto
    $iconos = [
        'una vez'         => $GLOBALS['unavez'] ?? '1',
        'habito'          => $GLOBALS['habito'] ?? 'H',
        'habito rigido'   => $GLOBALS['habito'] ?? 'H',
        'habito flexible' => $GLOBALS['habito'] ?? 'H',
        'meta'            => $GLOBALS['meta'] ?? 'G'
    ];
    return $iconos[$tipo] ?? '';
}

function obtenerFrecuenciaTexto($frec)
{
    return match (true) {
        $frec == 1                 => 'diaria',
        $frec == 7                 => 'semanal',
        $frec >= 27 && $frec <= 32 => 'mensual',  // Cubre meses de 28 a 31 dias aprox.
        $frec == 365               => 'anual',
        default                    => "{$frec}d",
    };
}

function calcularTextoTiempo($fechaReferencia)
{
    $valRetDefecto = ['txt' => '', 'simbolo' => '', 'claseNeg' => '', 'diasDif' => 0];
    if (empty($fechaReferencia) || $fechaReferencia === '0000-00-00') {  // Considerar '0000-00-00' como inválida
        return $valRetDefecto;
    }

    $tsReferencia = strtotime($fechaReferencia);
    if ($tsReferencia === false || $tsReferencia < 0) {
        return $valRetDefecto;
    }

    $tsHoy   = strtotime(date('Y-m-d'));
    $difDias = floor(($tsReferencia - $tsHoy) / (60 * 60 * 24));

    $txt      = '';
    $simbolo  = '';
    $claseNeg = '';

    if ($difDias == 0)
        $txt = 'Hoy';
    elseif ($difDias == 1)
        $txt = 'Mañana';
    elseif ($difDias == -1) {
        $txt      = 'Ayer';
        $claseNeg = 'diaNegativo';
    } elseif ($difDias > 1)
        $txt = $difDias . 'd';
    elseif ($difDias < -1) {
        $txt      = abs($difDias) . 'd';
        $simbolo  = '-';
        $claseNeg = 'diaNegativo';
    }

    return ['txt' => $txt, 'simbolo' => $simbolo, 'claseNeg' => $claseNeg, 'diasDif' => $difDias];
}

function botonesHabitos($id, $frec, $proxima)
{
    $frecTxt = obtenerFrecuenciaTexto($frec);
    $tiempo  = calcularTextoTiempo($proxima);
    ob_start();
    ?>
    <div class="divProxima" data-tarea="<? echo $id; ?>" style="cursor: pointer;">
        <p class="proximaTarea svgtask">
            <span class="textoProxima <? echo esc_attr($tiempo['claseNeg']); ?>"><? echo esc_html($tiempo['simbolo'] . $tiempo['txt']); ?></span>
        </p>
    </div>
    <div class="divFrecuencia" data-tarea="<? echo $id; ?>" style="cursor: pointer;">
        <p class="frecuenciaTarea svgtask">
            <span class="tituloFrecuencia"><? echo esc_html($frecTxt); ?></span>
        </p>
    </div>
<?
    return ob_get_clean();
}

function fechaLimite($id, $fechaLimite)
{
    $tiempo = calcularTextoTiempo($fechaLimite);
    if (empty($tiempo['txt']))
        return '';
    ob_start();
    ?>
    <div class="divFechaLimite" data-tarea="<? echo $id; ?>" style="cursor: pointer;">
        <p class="fechaLimiteMeta svgtask">
            <span class="textoFechaLimite <? echo esc_attr($tiempo['claseNeg']); ?>"><? echo esc_html($tiempo['simbolo'] . $tiempo['txt']); ?></span>
        </p>
    </div>
<?
    return ob_get_clean();
}

function generarHtmlTarea($id, $filtro, $titulo, $impIcono, $imp, $tipoIcono, $frec, $est, $autorId, $tipo, $proxima, $fechaLimite, $sesion, $impnum, $mostrarIcono)
{
    global $depurarTitulo;

    $esCompletada         = ($est === 'completada');
    $esHabito             = in_array($tipo, ['habito', 'habito rigido', 'habito flexible']);
    $esMeta               = ($tipo === 'meta');
    $idPadre              = get_post_meta($id, 'subtarea', true);
    $tituloOriginal       = $titulo;
    $infoDepuracionTitulo = '';

    $tieneSubtareasIncompletas = false;
    $idsSubtareas              = [];

    if ($idPadre) {
        $tieneSubtareasIncompletas = false;
    } else {
        $postTypeActual = get_post_type($id) ?: 'post';
        $argsSubtareas  = [
            'post_type'      => $postTypeActual,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'     => 'subtarea',
                    'value'   => $id,
                    'compare' => '=',
                ],
                [
                    'key'     => 'estado',
                    'value'   => ['completada', 'eliminada'],
                    'compare' => 'NOT IN',
                ],
            ],
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ];
        $idsSubtareas   = get_posts($argsSubtareas);
        if (!empty($idsSubtareas)) {
            $tieneSubtareasIncompletas = true;
        } else {
            $tieneSubtareasIncompletas = false;
        }
    }

    if ($depurarTitulo) {
        $partesDepuracionPrincipal   = [];
        $partesDepuracionPrincipal[] = 'ID: ' . $id;

        if (!empty($sesion)) {
            $partesDepuracionPrincipal[] = 'Sesión: ' . esc_html($sesion);
        }
        $infoDepuracionTitulo = '(' . implode(' | ', $partesDepuracionPrincipal) . ')';

        if ($idPadre) {
            $infoDepuracionTitulo .= ' (Padre: ' . esc_html($idPadre) . ')';
        } elseif (!empty($idsSubtareas)) {
            $infoDepuracionTitulo .= ' (SubT: ' . implode(', ', $idsSubtareas) . ')';
        }
    }

    $tiempoProxima          = calcularTextoTiempo($proxima);
    $difDiasHabito          = ($esHabito && !empty($tiempoProxima['txt'])) ? $tiempoProxima['diasDif'] : 0;
    $tiempoLimite           = calcularTextoTiempo($fechaLimite);
    $difDiasMeta            = ($esMeta && !empty($tiempoLimite['txt'])) ? $tiempoLimite['diasDif'] : 0;
    $limiteTieneTextoValido = !empty($tiempoLimite['txt']);
    $difDiasActivo          = $esHabito ? $difDiasHabito : ($esMeta ? $difDiasMeta : 0);
    $sesionPredeterminada   = ($est === 'archivado' ? 'Archivado' : 'General');
    $sesionValor            = empty($sesion) ? $sesionPredeterminada : $sesion;
    $frecTxt                = obtenerFrecuenciaTexto($frec);

    if (in_array(strtolower($sesionValor), ['general', 'archivado', 'archivada'])) {
        $sesionValor = ucfirst(str_replace('archivada', 'archivado', strtolower($sesionValor)));
    }

    $sesionHtml = esc_attr($sesionValor);

    ob_start();
    ?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo $id; ?> <? echo $esCompletada ? 'completada' : ''; ?> <? echo (!$idPadre && $tieneSubtareasIncompletas) ? 'tarea-padre' : ''; ?> draggable-element <? echo esc_attr($est); ?> <? echo $idPadre ? 'subtarea' : ''; ?>"
        filtro="<? echo esc_attr($filtro); ?>"
        tipo-tarea="<? echo esc_attr($tipo); ?>"
        id-post="<? echo $id; ?>"
        autor="<? echo esc_attr($autorId); ?>"
        draggable="true" <? echo $esCompletada ? 'style="text-decoration: line-through;"' : ''; ?>
        data-sesion="<? echo $sesionHtml; ?>"
        estado="<? echo esc_attr($est); ?>"
        impnum="<? echo esc_attr($impnum); ?>"
        importancia="<? echo esc_attr($imp); ?>"
        subtarea="<? echo $idPadre ? 'true' : 'false'; ?>"
        padre="<? echo esc_attr($idPadre ?: ''); ?>"
        dif="<? echo esc_attr($difDiasActivo); ?>"
        data-fechalimite="<? echo esc_attr($fechaLimite ?? ''); ?>"
        data-proxima="<? echo esc_attr($proxima ?? ''); ?>">

        <button class="completaTarea <? echo ($esHabito && $tipo !== 'habito rigido') ? 'habito' : ''; ?> <? echo ($tipo === 'habito flexible') ? 'habitoFlexible' : ''; ?>" data-tarea="<? echo $id; ?>">
            <? echo $GLOBALS['circulo'] ?? '[ ]'; ?>
        </button>

        <p class="tituloTarea" data-tarea="<? echo $id; ?>">
            <? echo esc_html($tituloOriginal); ?>
            <? if (!empty($infoDepuracionTitulo)): ?>
                <span class="info-ids-depuracion" style="font-size:0.75em; color: #666; margin-left: 8px; font-weight:normal;"><? echo esc_html($infoDepuracionTitulo); ?></span>
            <? endif; ?>
        </p>

        <p class="idtarea" style="display: none; font-size: 11px;">
            <? echo $id; ?>
        </p>

        <?
        if ($esHabito) {
            // echo botonesHabitos($id, $frec, $proxima);
        } elseif ($limiteTieneTextoValido) {
            echo fechaLimite($id, $fechaLimite);
        }
        ?>

        <div class="divSesion" data-tarea="<? echo $id; ?>" style="display: none; cursor: pointer;">
            <p class="sesionTarea">
                <? echo $GLOBALS['carpetaIcon'] ?? ''; ?>
            </p>
        </div>

        <div class="divImportancia" data-tarea="<? echo $id; ?>">
            <p class="importanciaTarea <? echo $mostrarIcono ? 'svgtask' : ''; ?>">
                <? echo $mostrarIcono ? $impIcono : '<span class="tituloImportancia">' . esc_html($imp) . '</span>'; ?>
            </p>
        </div>

        <p class="tipoTarea svgtask" style="display: none;"><? echo $tipoIcono; ?></p>
        <p class="estadoTarea" style="display: none;"><? echo esc_html($est); ?></p>

        <?
        if (function_exists('opcionesPost'))
            echo opcionesPost($id, $autorId);
        else
            echo '<!-- Fn:opcionesPost Indisponible -->';
        ?>

        <div class="divArchivado ocultadoAutomatico" data-tarea="<? echo $id; ?>" style="display: none;">
            <p class="archivadoTarea" style="cursor: pointer;">
                <? echo $GLOBALS['archivadoIcon'] ?? '[A]'; ?>
            </p>
        </div>

        <? if (!$esHabito && !$limiteTieneTextoValido): ?>
            <div class="divFechaLimite ocultadoAutomatico" data-tarea="<? echo $id; ?>" style="display: none; cursor: pointer;">
                <p>
                    <span class="textoFechaLimite">
                        <? echo $GLOBALS['calendario'] ?? '[F]'; ?>
                    </span>
                </p>
            </div>
        <? endif; ?>

        <div class="divCarpeta ocultadoAutomatico" data-tarea="<? echo $id; ?>" style="display: none; cursor: pointer;">
            <p>
                <span class="carpetaSpan">
                    <? echo $GLOBALS['meterCarpeta'] ?? '[C]'; ?>
                </span>
            </p>
        </div>

        <? if ($esHabito || $tipo === 'habito flexible'): ?>
            <div class="divOpcionesHabito ocultadoAutomatico divFrecuencia" data-tarea="<? echo $id; ?>" data-tarea="<? echo $id ?>" style="display: none; cursor: pointer;"> 

                    <span class="tituloFrecuencia"><? echo esc_html($frec); ?></span>
                    <? echo $GLOBALS['iconoHabitoRe']; ?>

            </div>
        <? endif; ?>
        


        <?

        if ($esHabito && in_array($tipo, ['habito', 'habito rigido'])) {  // Solo para 'habito' y 'habito rigido'
            // Configuración para cambiar modo de visualización (1 o 2)
            $modoVisualizacionDias = 2;  // 1 para frecuencia, 2 para consecutivos
            $maxDiasMostrar        = 5;
            $hoyObjeto             = new DateTime();
            $hoy                   = $hoyObjeto->format('Y-m-d');

            $fechasCompletado = get_post_meta($id, 'fechasCompletado', true);
            if (!is_array($fechasCompletado))
                $fechasCompletado = [];

            // Asumimos que 'fechasSaltado' se guardará de forma similar
            $fechasSaltado = get_post_meta($id, 'fechasSaltado', true);
            if (!is_array($fechasSaltado))
                $fechasSaltado = [];

            $diasParaMostrar = [];

            if ($modoVisualizacionDias == 1) {
                // Modo 1: Basado en frecuencia, inverso, incluyendo completados/saltados no frecuentes
                $fechasRelevantes       = [];
                $fechaActualConsiderada = new DateTime($hoy);
                // Añadir días de frecuencia
                for ($i = 0; $i < $maxDiasMostrar * 2; $i++) {  // Iterar más para asegurar suficientes días base
                    $fechasRelevantes[$fechaActualConsiderada->format('Y-m-d')] = ['tipo' => 'frecuencia'];
                    if (count($fechasRelevantes) >= $maxDiasMostrar && $i >= $maxDiasMostrar - 1)
                        break;  // Asegurar al menos maxDiasMostrar si es posible
                    $fechaActualConsiderada->modify("-$frec days");
                }

                // Añadir días completados y saltados que no estén ya
                foreach ($fechasCompletado as $fc) {
                    if (!isset($fechasRelevantes[$fc]))
                        $fechasRelevantes[$fc] = ['tipo' => 'completado_extra'];
                }
                foreach ($fechasSaltado as $fs) {
                    if (!isset($fechasRelevantes[$fs]))
                        $fechasRelevantes[$fs] = ['tipo' => 'saltado_extra'];
                }

                uksort($fechasRelevantes, function ($a, $b) {
                    return strtotime($a) - strtotime($b);
                });  // Ordenar por fecha ascendente

                $contadorDias = 0;
                foreach ($fechasRelevantes as $fechaStr => $data) {
                    if ($contadorDias >= $maxDiasMostrar)
                        break;
                    // Solo tomar fechas hasta hoy inclusive
                    if (strtotime($fechaStr) > strtotime($hoy))
                        continue;
                    $diasParaMostrar[$fechaStr] = $fechaStr;
                    $contadorDias++;
                }
                // Asegurarse de que el día de hoy esté si no se ha alcanzado el máximo
                if (!isset($diasParaMostrar[$hoy]) && count($diasParaMostrar) < $maxDiasMostrar) {
                    $diasParaMostrar[$hoy] = $hoy;
                }
                // Ordenar ascendente
                ksort($diasParaMostrar);
            } else {  // Modo 2: Consecutivos
                $fechaActualConsiderada = new DateTime($hoy);
                for ($i = 0; $i < $maxDiasMostrar; $i++) {
                    $diasParaMostrar[$fechaActualConsiderada->format('Y-m-d')] = $fechaActualConsiderada->format('Y-m-d');
                    $fechaActualConsiderada->modify('-1 day');
                }
                ksort($diasParaMostrar);  // Ordenar por fecha ascendente
            }

            if (!empty($diasParaMostrar)) {
                echo '<div class="habito-dias-visualizacion">';
                foreach ($diasParaMostrar as $fechaDia) {
                    $estadoDia   = 'pendiente';  // Por defecto
                    $iconoDia    = $GLOBALS['equis'] ?? 'X';
                    $claseEstado = 'estado-pendiente';

                    if (in_array($fechaDia, $fechasCompletado)) {
                        $estadoDia   = 'completado';
                        $iconoDia    = $GLOBALS['iconoCheck1'] ?? 'V';
                        $claseEstado = 'estado-completado';
                    } elseif (in_array($fechaDia, $fechasSaltado)) {
                        $estadoDia   = 'saltado';
                        $iconoDia    = $GLOBALS['minus'] ?? '-';
                        $claseEstado = 'estado-saltado';
                    }
                    // Si es hoy y no está completado ni saltado, ya es 'pendiente' con 'equis'

                    echo '<span class="dia-habito-item ' . esc_attr($claseEstado) . '" data-fecha="' . esc_attr($fechaDia) . '" data-tarea-id="' . esc_attr($id) . '" data-estado="' . esc_attr($estadoDia) . '">' . $iconoDia . '</span>';
                }
                echo '</div>';
            }
        }
        ?>
    </li>
<?
    return ob_get_clean();
}
?>