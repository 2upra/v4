<?
// app/Services/Task/TaskCrudService.php

function jsonTask($exito, $datosOError, $logDetalles, $nombreFunc)
{
    $logFinal = "$nombreFunc: $logDetalles";
    if (!$exito && is_string($datosOError))
        $logFinal .= " ErrorMsg: $datosOError";
    // guardarLog($logFinal);

    if ($exito)
        wp_send_json_success($datosOError);
    else
        wp_send_json_error($datosOError);
}

function procesarCompletadoInterno($idTarea)
{
    $tip      = get_post_meta($idTarea, 'tipo', true);
    $logLocal = "ProcCompletado TareaID:$idTarea Tipo:$tip";

    if ($tip === 'una vez') {
        update_post_meta($idTarea, 'estado', 'completada');
        $logLocal .= ' Estado->completada.';
    } elseif ($tip === 'habito' || $tip === 'habito flexible' || $tip === 'habito rigido') {
        $frec = (int) get_post_meta($idTarea, 'frecuencia', true);
        if ($frec <= 0)
            $frec = 1;
        $hoy = date('Y-m-d');

        $vecesComp  = (int) get_post_meta($idTarea, 'vecesCompletado', true) + 1;
        $fechasComp = get_post_meta($idTarea, 'fechasCompletado', true);
        if (!is_array($fechasComp))
            $fechasComp = [];
        $fechasComp[] = $hoy;

        update_post_meta($idTarea, 'vecesCompletado', $vecesComp);
        update_post_meta($idTarea, 'fechasCompletado', $fechasComp);

        $baseFec   = $hoy;
        $nvaFecPro = date('Y-m-d', strtotime("$baseFec +$frec days"));
        update_post_meta($idTarea, 'fechaProxima', $nvaFecPro);

        $logLocal .= " Comp:$vecesComp FecProxAnt:$hoy FecProxNva:$nvaFecPro.";
    } else {
        update_post_meta($idTarea, 'estado', 'completada');
        $logLocal .= ' Estado->completada (tipo no específico para hábito).';
    }
    return $logLocal;
}

function crearTarea()
{
    $func = 'crearTarea';
    if (!current_user_can('edit_posts'))
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $tit = sanitize_text_field($_POST['titulo'] ?? '');
    if (empty($tit))
        jsonTask(false, 'Título vacío.', 'Título vacío.', $func);

    $imp = sanitize_text_field($_POST['importancia'] ?? 'media');
    $tip = sanitize_text_field($_POST['tipo'] ?? 'una vez');

    // Temporalmente desactiva habito rigido
    if ($tip === 'habito rigido') {
        $tip = 'habito';
    }

    $frec   = (int) ($_POST['frecuencia'] ?? 1);
    $ses    = sanitize_text_field($_POST['sesion'] ?? '');
    $est    = sanitize_text_field($_POST['estado'] ?? 'pendiente');
    $pad    = (int) ($_POST['padre'] ?? 0);
    $fecLim = sanitize_text_field($_POST['fechaLimite'] ?? null);

    $mapaImp = ['importante' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
    $impnum  = $mapaImp[$imp] ?? 2;
    $mapaTip = ['una vez' => 1, 'habito' => 2, 'meta' => 3, 'habito rigido' => 4];
    $tipnum  = $mapaTip[$tip] ?? 1;

    $metaInput = [
        'importancia'  => $imp,
        'impnum'       => $impnum,
        'tipo'         => $tip,
        'tipnum'       => $tipnum,
        'estado'       => $est,
        'frecuencia'   => $frec,
        'fecha'        => date('Y-m-d'),
        'fechaProxima' => date('Y-m-d', strtotime("+$frec days")),
        'sesion'       => $ses
    ];
    if (!empty($fecLim))
        $metaInput['fechaLimite'] = $fecLim;

    $args = [
        'post_title'  => $tit,
        'post_type'   => 'tarea',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'meta_input'  => $metaInput
    ];

    if ($pad > 0) {
        $pPost = get_post($pad);
        if (!$pPost || $pPost->post_type !== 'tarea') {
            jsonTask(false, 'Tarea padre no encontrada.', "Padre ID $pad no válido.", $func);
        }
        $args['post_parent'] = $pad;
    }

    $idTarea = wp_insert_post($args, true);

    if (is_wp_error($idTarea)) {
        jsonTask(false, $idTarea->get_error_message(), 'Error WP: ' . $idTarea->get_error_message(), $func);
    }

    $logMsg = $pad > 0 ? "Subtarea $idTarea (padre $pad)" : "Tarea $idTarea";
    if ($pad > 0)
        update_post_meta($idTarea, 'subtarea', $pad);

    jsonTask(true, ['tareaId' => $idTarea], "$logMsg creada. Sesion: $ses, Est: $est.", $func);
}

add_action('wp_ajax_crearTarea', 'crearTarea');

function completarTarea()
{
    $func = 'completarTarea';
    if (!current_user_can('edit_posts'))
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0)
        jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);

    $tarea = get_post($id);
    if (!$tarea || $tarea->post_type !== 'tarea')
        jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    $logDet  = "IDPrincipal:$id ";
    $logDet .= procesarCompletadoInterno($id);

    $subtareasIds = get_children(['post_parent' => $id, 'post_type' => 'tarea', 'numberposts' => -1, 'fields' => 'ids']);
    if (!empty($subtareasIds)) {
        $logDet .= ' Subtareas(' . count($subtareasIds) . '):';
        foreach ($subtareasIds as $subId) {
            $logDet .= ' [' . procesarCompletadoInterno($subId) . ']';
        }
    } else {
        $logDet .= ' SinSubtareas.';
    }

    jsonTask(true, ['mensaje' => 'Tarea(s) procesada(s).'], trim($logDet), $func);
}

add_action('wp_ajax_completarTarea', 'completarTarea');

function cambiarPrioridad()
{
    $func = 'cambiarPrioridad';
    if (!current_user_can('edit_posts'))
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id   = (int) ($_POST['tareaId'] ?? 0);
    $prio = sanitize_text_field($_POST['prioridad'] ?? '');
    if ($id <= 0)
        jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);

    if (!get_post($id) || get_post_type($id) !== 'tarea')
        jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    $mapaPrio = ['importante' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
    if (!isset($mapaPrio[$prio]))
        jsonTask(false, 'Prioridad inválida.', "Prio '$prio' no válida.", $func);

    update_post_meta($id, 'importancia', $prio);
    update_post_meta($id, 'impnum', $mapaPrio[$prio]);

    jsonTask(true, ['mensaje' => 'Prioridad actualizada.'], "ID: $id. Prio: $prio ({$mapaPrio[$prio]}).", $func);
}

add_action('wp_ajax_cambiarPrioridad', 'cambiarPrioridad');

function borrarTarea()
{
    $func = 'borrarTarea';
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'borrar_tarea_nonce')) {
        guardarLog("$func: Nonce inválido.");
        wp_send_json_error('Nonce inválido.');  // No usar jsonTask para fallos de nonce
    }

    if (!current_user_can('edit_posts'))
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0)
        jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);
    if (!get_post($id) || get_post_type($id) !== 'tarea')
        jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    if (!wp_delete_post($id, true))
        jsonTask(false, 'Error al borrar.', "Error WP borrando ID $id.", $func);

    jsonTask(true, ['mensaje' => 'Tarea borrada.'], "ID $id borrada.", $func);
}

add_action('wp_ajax_borrarTarea', 'borrarTarea');

function modificarTarea()
{
    $func = 'modificarTarea';
    if (!current_user_can('edit_posts'))
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id  = (int) ($_POST['id'] ?? 0);
    $tit = sanitize_text_field($_POST['titulo'] ?? '');

    if ($id <= 0)
        jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);
    if (empty($tit))
        jsonTask(false, 'Título vacío.', 'Título vacío.', $func);
    if (!get_post($id) || get_post_type($id) !== 'tarea')
        jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    $res = wp_update_post(['ID' => $id, 'post_title' => $tit], true);
    if (is_wp_error($res))
        jsonTask(false, $res->get_error_message(), 'Error WP: ' . $res->get_error_message(), $func);

    jsonTask(true, ['mensaje' => 'Tarea modificada.'], "ID $id modificada. Título: '$tit'.", $func);
}

add_action('wp_ajax_modificarTarea', 'modificarTarea');

function archivarTarea()
{
    $func = 'archivarTarea';
    if (!current_user_can('edit_posts'))
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0)
        jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);

    $tarea = get_post($id);
    if (!$tarea || $tarea->post_type !== 'tarea')
        jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    $estAct         = get_post_meta($id, 'estado', true);
    $logDet         = "ID:$id EstActual:$estAct.";
    $cntHijAct      = 0;  // Variable para contar hijos actualizados
    $mensajeUsuario = '';

    if ($estAct === 'archivado') {
        $nuevoEstado = 'pendiente';
        actEstTarHijos($id, $nuevoEstado, $cntHijAct);
        $logDet        .= " DesarchivadaA:$nuevoEstado. SubtareasAfectadas:$cntHijAct.";  // Corregido aquí
        $mensajeUsuario = 'Tarea y subtareas desarchivadas.';
    } else {
        $nuevoEstado = 'archivado';
        actEstTarHijos($id, $nuevoEstado, $cntHijAct);
        $logDet        .= " Archivada. SubtareasAfectadas:$cntHijAct.";  // Corregido aquí
        $mensajeUsuario = 'Tarea y subtareas archivadas.';

        $usu   = get_current_user_id();
        $orden = get_user_meta($usu, 'ordenTareas', true);
        if (is_array($orden)) {
            if (($pos = array_search($id, $orden)) !== false) {
                unset($orden[$pos]);
                update_user_meta($usu, 'ordenTareas', array_values($orden));  // Asegurar que los índices se reorganicen
                $logDet .= ' TareaQuitadaOrdenPersonalizado.';
            }
        }
    }

    jsonTask(true, ['mensaje' => $mensajeUsuario], $logDet, $func);
}

add_action('wp_ajax_archivarTarea', 'archivarTarea');

function cambiarFrecuencia()
{
    $func = 'cambiarFrecuencia';
    if (!current_user_can('edit_posts'))
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id   = (int) ($_POST['tareaId'] ?? 0);
    $frec = (int) ($_POST['frecuencia'] ?? 0);

    if ($id <= 0)
        jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);
    if (!get_post($id) || get_post_type($id) !== 'tarea')
        jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);
    if ($frec < 1 || $frec > 365)
        jsonTask(false, 'Frecuencia inválida.', "Frec $frec fuera rango (1-365).", $func);

    $fecprox = date('Y-m-d', strtotime("+$frec days"));
    update_post_meta($id, 'frecuencia', $frec);
    update_post_meta($id, 'fechaProxima', $fecprox);

    jsonTask(true, ['mensaje' => 'Frecuencia actualizada.'], "ID: $id. Frec: $frec, Prox: $fecprox.", $func);
}

add_action('wp_ajax_cambiarFrecuencia', 'cambiarFrecuencia');

function borrarTareasCompletadas()
{
    $func = 'borrarTareasCompletadas';
    if (!current_user_can('edit_posts'))
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    if (($_POST['limpiar'] ?? 'false') !== 'true') {
        jsonTask(false, 'No se solicitó limpiar.', 'limpiar no es true.', $func);
    }

    $idsTareas = get_posts([
        'post_type'      => 'tarea',
        'author'         => get_current_user_id(),
        'meta_query'     => [['key' => 'estado', 'value' => 'completada']],
        'posts_per_page' => -1,
        'fields'         => 'ids'
    ]);

    if (empty($idsTareas)) {
        jsonTask(true, 'No hay tareas completadas para borrar.', 'Sin tareas completadas.', $func);
    }

    $borradas = 0;
    $errores  = 0;
    foreach ($idsTareas as $id) {
        if (wp_delete_post($id, true))
            $borradas++;
        else
            $errores++;
    }

    $msg = "$borradas tareas borradas.";
    if ($errores > 0)
        $msg .= " $errores errores.";
    jsonTask(true, $msg, "Borradas: $borradas, Errores: $errores.", $func);
}

add_action('wp_ajax_borrarTareasCompletadas', 'borrarTareasCompletadas');

function modificarFechaLimiteTarea()
{
    $func = 'modificarFechaLimiteTarea';
    if (!current_user_can('edit_posts')) {
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);
    }

    $idTarea        = (int) ($_POST['tareaId'] ?? 0);
    // La fecha puede ser una cadena 'YYYY-MM-DD' o null si se está borrando.
    // Si se envía null desde JS, $_POST['fechaLimite'] podría no existir o ser una cadena vacía.
    // Si $_POST['fechaLimite'] es una cadena vacía, la trataremos como borrar la fecha.
    $fechaLimiteRaw = $_POST['fechaLimite'] ?? null;

    $logDet = "ID: $idTarea.";

    if ($idTarea <= 0) {
        jsonTask(false, 'ID de tarea inválido.', 'ID tarea inválido.', $func);
    }

    $tarea = get_post($idTarea);
    if (!$tarea || $tarea->post_type !== 'tarea') {
        jsonTask(false, 'Tarea no encontrada.', "ID $idTarea no encontrado o no es tarea.", $func);
    }

    if (empty($fechaLimiteRaw) || $fechaLimiteRaw === 'null') {  // 'null' como cadena si JS envía null así
        // Borrar la fecha límite
        delete_post_meta($idTarea, 'fechaLimite');
        $logDet .= ' Fecha límite eliminada.';
        jsonTask(true, ['mensaje' => 'Fecha límite eliminada.'], $logDet, $func);
    } else {
        // Validar formato de fecha YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaLimiteRaw)) {
            jsonTask(false, 'Formato de fecha inválido. Use YYYY-MM-DD.', "Fecha '$fechaLimiteRaw' formato inválido.", $func);
        }

        // Validar que la fecha sea una fecha real
        $d = DateTime::createFromFormat('Y-m-d', $fechaLimiteRaw);
        if (!$d || $d->format('Y-m-d') !== $fechaLimiteRaw) {
            jsonTask(false, 'Fecha inválida.', "Fecha '$fechaLimiteRaw' no es válida.", $func);
        }

        $fechaLimiteSanitizada = sanitize_text_field($fechaLimiteRaw);
        update_post_meta($idTarea, 'fechaLimite', $fechaLimiteSanitizada);
        $logDet .= " Fecha límite actualizada a $fechaLimiteSanitizada.";
        jsonTask(true, ['mensaje' => 'Fecha límite actualizada.'], $logDet, $func);
    }
}

add_action('wp_ajax_modificarFechaLimiteTarea', 'modificarFechaLimiteTarea');

function modificarFechaProximaHabito()
{
    $func = 'modificarFechaProximaHabito';
    if (!current_user_can('edit_posts')) {
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);
    }

    $idTarea         = (int) ($_POST['tareaId'] ?? 0);
    // La fecha puede ser una cadena 'YYYY-MM-DD' o null si se está borrando.
    // Si JS envía null, $_POST['fechaProxima'] podría no existir o ser una cadena vacía.
    // Trataremos cadena vacía como borrar la fecha.
    $fechaProximaRaw = $_POST['fechaProxima'] ?? null;

    $logDet = "ID: $idTarea.";

    if ($idTarea <= 0) {
        jsonTask(false, 'ID de tarea inválido.', 'ID tarea inválido.', $func);
    }

    $tarea = get_post($idTarea);
    if (!$tarea || $tarea->post_type !== 'tarea') {
        jsonTask(false, 'Tarea no encontrada.', "ID $idTarea no encontrado o no es tarea.", $func);
    }

    // Verificar si la tarea es un hábito (puedes ajustar los tipos si es necesario)
    $tipoTarea = get_post_meta($idTarea, 'tipo', true);
    if (!in_array($tipoTarea, ['habito', 'habito rigido', 'habito flexible'])) {
        jsonTask(false, 'Esta acción solo es para hábitos.', "ID $idTarea no es tipo hábito (es '$tipoTarea').", $func);
    }

    if (empty($fechaProximaRaw) || $fechaProximaRaw === 'null') {  // 'null' como cadena si JS envía null así
        // Si decides permitir borrar la fechaProxima para un hábito (lo cual podría ser raro, usualmente se recalcula)
        // delete_post_meta($idTarea, 'fechaProxima');
        // $logDet .= " Fecha próxima eliminada.";
        // jsonTask(true, ['mensaje' => 'Fecha próxima eliminada.'], $logDet, $func);
        // O, más probablemente, no permitir borrarla y enviar un error o no hacer nada:
        jsonTask(false, 'No se puede eliminar la fecha próxima de un hábito directamente. Se recalcula al completar.', "Intento de eliminar fechaProxima para hábito ID $idTarea.", $func);
    } else {
        // Validar formato de fecha YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaProximaRaw)) {
            jsonTask(false, 'Formato de fecha inválido. Use YYYY-MM-DD.', "Fecha '$fechaProximaRaw' formato inválido.", $func);
        }

        // Validar que la fecha sea una fecha real
        $d = DateTime::createFromFormat('Y-m-d', $fechaProximaRaw);
        if (!$d || $d->format('Y-m-d') !== $fechaProximaRaw) {
            jsonTask(false, 'Fecha inválida.', "Fecha '$fechaProximaRaw' no es válida.", $func);
        }

        $fechaProximaSanitizada = sanitize_text_field($fechaProximaRaw);
        update_post_meta($idTarea, 'fechaProxima', $fechaProximaSanitizada);
        $logDet .= " Fecha próxima actualizada a $fechaProximaSanitizada.";
        jsonTask(true, ['mensaje' => 'Fecha próxima actualizada.'], $logDet, $func);
    }
}

add_action('wp_ajax_modificarFechaProximaHabito', 'modificarFechaProximaHabito');

function marcarDiaHabito()
{
    $func = 'marcarDiaHabito';
    if (!current_user_can('edit_posts')) {
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);
    }

    $idTarea     = (int) ($_POST['tareaId'] ?? 0);
    $fecha       = sanitize_text_field($_POST['fecha'] ?? '');
    $estadoNuevo = sanitize_text_field($_POST['estado'] ?? 'pendiente');  // 'completado', 'saltado', 'pendiente'

    $logDet = "ID: $idTarea, Fecha: $fecha, EstadoNuevo: $estadoNuevo.";

    if ($idTarea <= 0) {
        jsonTask(false, 'ID de tarea inválido.', 'ID tarea inválido.', $func);
    }
    if (empty($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        jsonTask(false, 'Formato de fecha inválido. Use YYYY-MM-DD.', "Fecha '$fecha' formato inválido.", $func);
    }
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        jsonTask(false, 'Fecha inválida.', "Fecha '$fecha' no es válida.", $func);
    }
    if (!in_array($estadoNuevo, ['completado', 'saltado', 'pendiente'])) {
        jsonTask(false, 'Estado inválido.', "Estado '$estadoNuevo' no válido.", $func);
    }

    $tarea = get_post($idTarea);
    if (!$tarea || $tarea->post_type !== 'tarea') {
        jsonTask(false, 'Tarea no encontrada.', "ID $idTarea no encontrado o no es tarea.", $func);
    }

    $tipoTarea = get_post_meta($idTarea, 'tipo', true);
    if (!in_array($tipoTarea, ['habito', 'habito rigido'])) {  // Ajustar si 'habito flexible' también usa esto
        jsonTask(false, 'Esta acción solo es para hábitos (rígidos o normales).', "ID $idTarea no es tipo hábito compatible (es '$tipoTarea').", $func);
    }

    // Manejar fechasCompletado
    $fechasCompletado = get_post_meta($idTarea, 'fechasCompletado', true);
    if (!is_array($fechasCompletado))
        $fechasCompletado = [];

    // Manejar fechasSaltado
    $fechasSaltado = get_post_meta($idTarea, 'fechasSaltado', true);
    if (!is_array($fechasSaltado))
        $fechasSaltado = [];

    // Lógica para actualizar los arrays de fechas
    // Primero, quitar la fecha de ambos arrays para evitar duplicados o estados conflictivos
    if (($key = array_search($fecha, $fechasCompletado)) !== false) {
        unset($fechasCompletado[$key]);
    }
    if (($key = array_search($fecha, $fechasSaltado)) !== false) {
        unset($fechasSaltado[$key]);
    }

    // Luego, añadirla al array correspondiente si el nuevo estado no es 'pendiente'
    if ($estadoNuevo === 'completado') {
        $fechasCompletado[] = $fecha;
        $logDet            .= ' Añadido a fechasCompletado.';
    } elseif ($estadoNuevo === 'saltado') {
        $fechasSaltado[] = $fecha;
        $logDet         .= ' Añadido a fechasSaltado.';
    } else {  // pendiente
        $logDet .= ' Eliminado de ambos (marcado como pendiente).';
    }

    // Asegurar que no haya duplicados y reindexar
    $fechasCompletado = array_values(array_unique($fechasCompletado));
    $fechasSaltado    = array_values(array_unique($fechasSaltado));

    update_post_meta($idTarea, 'fechasCompletado', $fechasCompletado);
    update_post_meta($idTarea, 'fechasSaltado', $fechasSaltado);

    // Recalcular vecesCompletado
    $vecesCompletado = count($fechasCompletado);
    update_post_meta($idTarea, 'vecesCompletado', $vecesCompletado);
    $logDet .= " VecesCompletado actualizado a $vecesCompletado.";

    // Recalcular fechaProxima
    $frecuencia = (int) get_post_meta($idTarea, 'frecuencia', true);
    if ($frecuencia <= 0) {
        $frecuencia = 1;  // Frecuencia por defecto si no está definida o es inválida
    }

    $nuevaFechaProxima = '';

    // Independientemente del estado que se marque, la fechaProxima se calcula
    // a partir de la última fecha completada existente.
    // $fechasCompletado ya está actualizado con el estado actual del día ($fecha).
    if (!empty($fechasCompletado)) {
        $fechasCompletadoOrdenadas = $fechasCompletado;  // Copiar para no modificar el original que se guarda
        usort($fechasCompletadoOrdenadas, function ($a, $b) {
            return strtotime($b) - strtotime($a);  // Orden descendente para obtener la más reciente primero
        });
        $ultimaFechaCompletadaReal = $fechasCompletadoOrdenadas[0];
        $nuevaFechaProxima         = date('Y-m-d', strtotime("$ultimaFechaCompletadaReal +$frecuencia days"));
        $logDet                   .= " Nueva fechaProxima basada en última completada real ($ultimaFechaCompletadaReal): $nuevaFechaProxima.";
    } else {
        // No hay ninguna fecha completada registrada.
        // La próxima fecha es hoy + frecuencia.
        $nuevaFechaProxima = date('Y-m-d', strtotime("today +$frecuencia days"));
        $logDet           .= " Sin fechas completadas: fechaProxima calculada desde hoy: $nuevaFechaProxima.";
    }

    update_post_meta($idTarea, 'fechaProxima', $nuevaFechaProxima);

    jsonTask(true, ['mensaje' => 'Estado del día actualizado y fecha próxima recalculada.'], $logDet, $func);
}

add_action('wp_ajax_marcarDiaHabito', 'marcarDiaHabito');
