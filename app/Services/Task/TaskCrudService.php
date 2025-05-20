<?
// Asegúrate de que esta función exista y esté configurada correctamente.
// function guardarLog($mensaje) { /* ... tu implementación ... */ }

function jsonTask($exito, $datosOError, $logDetalles, $nombreFunc)
{
    $logFinal = "$nombreFunc: $logDetalles";
    if (!$exito && is_string($datosOError)) $logFinal .= " ErrorMsg: $datosOError";
    # guardarLog($logFinal);

    if ($exito) wp_send_json_success($datosOError);
    else wp_send_json_error($datosOError);
}

function crearTarea()
{
    $func = 'crearTarea';
    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $tit = sanitize_text_field($_POST['titulo'] ?? '');
    if (empty($tit)) jsonTask(false, 'Título vacío.', 'Título vacío.', $func);

    $imp = sanitize_text_field($_POST['importancia'] ?? 'media');
    $tip = sanitize_text_field($_POST['tipo'] ?? 'una vez');
    $frec = (int) ($_POST['frecuencia'] ?? 1);
    $ses = sanitize_text_field($_POST['sesion'] ?? '');
    $est = sanitize_text_field($_POST['estado'] ?? 'pendiente');
    $pad = (int) ($_POST['padre'] ?? 0);
    $fecLim = sanitize_text_field($_POST['fechaLimite'] ?? null);

    $mapaImp = ['importante' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
    $impnum = $mapaImp[$imp] ?? 2;
    $mapaTip = ['una vez' => 1, 'habito' => 2, 'meta' => 3, 'habito rigido' => 4];
    $tipnum = $mapaTip[$tip] ?? 1;

    $metaInput = [
        'importancia' => $imp,
        'impnum' => $impnum,
        'tipo' => $tip,
        'tipnum' => $tipnum,
        'estado' => $est,
        'frecuencia' => $frec,
        'fecha' => date('Y-m-d'),
        'fechaProxima' => date('Y-m-d', strtotime("+$frec days")),
        'sesion' => $ses
    ];
    if (!empty($fecLim)) $metaInput['fechaLimite'] = $fecLim;

    $args = [
        'post_title' => $tit,
        'post_type' => 'tarea',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'meta_input' => $metaInput
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
        jsonTask(false, $idTarea->get_error_message(), "Error WP: " . $idTarea->get_error_message(), $func);
    }

    $logMsg = $pad > 0 ? "Subtarea $idTarea (padre $pad)" : "Tarea $idTarea";
    if ($pad > 0) update_post_meta($idTarea, 'subtarea', $pad);

    jsonTask(true, ['tareaId' => $idTarea], "$logMsg creada. Sesion: $ses, Est: $est.", $func);
}
add_action('wp_ajax_crearTarea', 'crearTarea');

function completarTarea()
{
    $func = 'completarTarea';
    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);

    $tarea = get_post($id);
    if (!$tarea || $tarea->post_type !== 'tarea') jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    $tip = get_post_meta($id, 'tipo', true);
    $logDet = "ID: $id, Tipo: $tip";

    if ($tip === 'una vez') {
        $est = sanitize_text_field($_POST['estado'] ?? 'completada');
        update_post_meta($id, 'estado', $est);
        $logDet .= ". Estado -> $est.";
    } elseif ($tip === 'habito' || $tip === 'habito rigido') {
        $fecProAnt = get_post_meta($id, 'fechaProxima', true);
        $frec = (int) get_post_meta($id, 'frecuencia', true);
        $hoy = date('Y-m-d');

        $vecesComp = (int) get_post_meta($id, 'vecesCompletado', true) + 1;
        $fechasComp = get_post_meta($id, 'fechasCompletado', true);
        if (!is_array($fechasComp)) $fechasComp = [];
        $fechasComp[] = $hoy;

        update_post_meta($id, 'vecesCompletado', $vecesComp);
        update_post_meta($id, 'fechasCompletado', $fechasComp);

        $baseFec = ($tip === 'habito') ? $hoy : $fecProAnt;
        $nvaFecPro = date('Y-m-d', strtotime("$baseFec +$frec days"));
        update_post_meta($id, 'fechaProxima', $nvaFecPro);

        $logDet .= ". Comp: $vecesComp. FecProx: $fecProAnt -> $nvaFecPro.";
    } else {
        $logDet .= ". Sin acción especial de completado.";
    }

    jsonTask(true, ['mensaje' => 'Tarea procesada.'], $logDet, $func);
}
add_action('wp_ajax_completarTarea', 'completarTarea');

function cambiarPrioridad()
{
    $func = 'cambiarPrioridad';
    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['tareaId'] ?? 0);
    $prio = sanitize_text_field($_POST['prioridad'] ?? '');
    if ($id <= 0) jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);

    if (!get_post($id) || get_post_type($id) !== 'tarea') jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    $mapaPrio = ['importante' => 4, 'alta' => 3, 'media' => 2, 'baja' => 1];
    if (!isset($mapaPrio[$prio])) jsonTask(false, 'Prioridad inválida.', "Prio '$prio' no válida.", $func);

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
        wp_send_json_error('Nonce inválido.'); // No usar jsonTask para fallos de nonce
    }

    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);
    if (!get_post($id) || get_post_type($id) !== 'tarea') jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    if (!wp_delete_post($id, true)) jsonTask(false, 'Error al borrar.', "Error WP borrando ID $id.", $func);

    jsonTask(true, ['mensaje' => 'Tarea borrada.'], "ID $id borrada.", $func);
}
add_action('wp_ajax_borrarTarea', 'borrarTarea');

function modificarTarea()
{
    $func = 'modificarTarea';
    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['id'] ?? 0);
    $tit = sanitize_text_field($_POST['titulo'] ?? '');

    if ($id <= 0) jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);
    if (empty($tit)) jsonTask(false, 'Título vacío.', 'Título vacío.', $func);
    if (!get_post($id) || get_post_type($id) !== 'tarea') jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    $res = wp_update_post(['ID' => $id, 'post_title' => $tit], true);
    if (is_wp_error($res)) jsonTask(false, $res->get_error_message(), "Error WP: " . $res->get_error_message(), $func);

    jsonTask(true, ['mensaje' => 'Tarea modificada.'], "ID $id modificada. Título: '$tit'.", $func);
}
add_action('wp_ajax_modificarTarea', 'modificarTarea');

function archivarTarea()
{
    $func = 'archivarTarea';
    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);
    if (!get_post($id) || get_post_type($id) !== 'tarea') jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);

    $estAct = get_post_meta($id, 'estado', true);
    $logDet = "ID: $id. Est.Actual: $estAct.";
    $usu = get_current_user_id();

    if ($estAct === 'archivado') { // Desarchivar
        update_post_meta($id, 'estado', 'pendiente');
        update_post_meta($id, 'sesion', 'General');
        wp_update_post(['ID' => $id, 'post_parent' => 0]);
        delete_post_meta($id, 'subtarea');
        $logDet .= " Desarchivada -> pendiente, Sesion General, padre 0.";
    } else { // Archivar
        $orden = get_user_meta($usu, 'ordenTareas', true);
        if (is_array($orden)) {
            if (($pos = array_search($id, $orden)) !== false) unset($orden[$pos]);
            $orden[] = $id;
            update_user_meta($usu, 'ordenTareas', $orden);
            $logDet .= " Orden actualizado.";
        }

        $subtareas = get_children(['post_parent' => $id, 'post_type' => 'tarea', 'fields' => 'ids']);
        foreach ($subtareas as $subId) {
            update_post_meta($subId, 'estado', 'archivado');
            // Opcional: wp_update_post(['ID' => $subId, 'post_parent' => 0]); delete_post_meta($subId, 'subtarea');
            $logDet .= " Subtarea $subId archivada.";
        }

        wp_update_post(['ID' => $id, 'post_parent' => 0]);
        delete_post_meta($id, 'subtarea');
        update_post_meta($id, 'estado', 'archivado');
        $logDet .= " Archivada (padre 0).";
    }

    jsonTask(true, ['mensaje' => 'Estado de archivo actualizado.'], $logDet, $func);
}
add_action('wp_ajax_archivarTarea', 'archivarTarea');

function cambiarFrecuencia()
{
    $func = 'cambiarFrecuencia';
    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['tareaId'] ?? 0);
    $frec = (int) ($_POST['frecuencia'] ?? 0);

    if ($id <= 0) jsonTask(false, 'ID tarea inválido.', 'ID inválido.', $func);
    if (!get_post($id) || get_post_type($id) !== 'tarea') jsonTask(false, 'Tarea no encontrada.', "ID $id no encontrado.", $func);
    if ($frec < 1 || $frec > 365) jsonTask(false, 'Frecuencia inválida.', "Frec $frec fuera rango (1-365).", $func);

    $fecprox = date('Y-m-d', strtotime("+$frec days"));
    update_post_meta($id, 'frecuencia', $frec);
    update_post_meta($id, 'fechaProxima', $fecprox);

    jsonTask(true, ['mensaje' => 'Frecuencia actualizada.'], "ID: $id. Frec: $frec, Prox: $fecprox.", $func);
}
add_action('wp_ajax_cambiarFrecuencia', 'cambiarFrecuencia');

function crearSubtarea()
{
    $func = 'crearSubtarea'; // Renombrar a 'gestionarSubtarea' podría ser más claro.
    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    $id = (int) ($_POST['id'] ?? 0);
    $esSub = ($_POST['subtarea'] ?? 'false') === 'true';
    $idPad = (int) ($_POST['padre'] ?? 0);

    if ($id <= 0) jsonTask(false, 'ID tarea inválido.', "ID tarea $id inválido.", $func);
    $tareaMod = get_post($id);
    if (!$tareaMod || $tareaMod->post_type !== 'tarea') jsonTask(false, 'Tarea a modificar no encontrada.', "ID $id no encontrado.", $func);

    $logDet = "ID: $id.";

    if (!$esSub) {
        if (is_wp_error(wp_update_post(['ID' => $id, 'post_parent' => 0], true))) {
            jsonTask(false, 'Error al quitar padre.', "Error WP quitando padre de $id", $func);
        }
        delete_post_meta($id, 'subtarea');
        $logDet .= " Relación subtarea eliminada.";
        jsonTask(true, ['mensaje' => 'Relación subtarea eliminada.'], $logDet, $func);
        return;
    }

    if ($idPad <= 0) jsonTask(false, 'ID padre inválido.', "ID padre $idPad inválido.", $func);
    if ($id === $idPad) jsonTask(false, 'No puede ser subtarea de sí misma.', "ID $id y Padre $idPad iguales.", $func);

    $tareaPad = get_post($idPad);
    if (!$tareaPad || $tareaPad->post_type !== 'tarea') jsonTask(false, 'Tarea padre no encontrada.', "Padre $idPad no encontrado.", $func);

    if ((int)get_post_meta($id, 'subtarea', true) === $idPad && (int)$tareaMod->post_parent === $idPad) {
        $logDet .= " Ya es subtarea de $idPad.";
        jsonTask(true, ['mensaje' => 'Ya es subtarea del padre especificado.'], $logDet, $func);
        return;
    }

    if (is_wp_error(wp_update_post(['ID' => $id, 'post_parent' => $idPad], true))) {
        jsonTask(false, 'Error al asignar padre.', "Error WP asignando padre $idPad a $id", $func);
    }
    update_post_meta($id, 'subtarea', $idPad);
    $logDet .= " Asignada como subtarea de $idPad.";

    jsonTask(true, ['mensaje' => 'Relación subtarea actualizada.'], $logDet, $func);
}
add_action('wp_ajax_crearSubtarea', 'crearSubtarea');

function borrarTareasCompletadas()
{
    $func = 'borrarTareasCompletadas';
    if (!current_user_can('edit_posts')) jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);

    if (($_POST['limpiar'] ?? 'false') !== 'true') {
        jsonTask(false, 'No se solicitó limpiar.', 'limpiar no es true.', $func);
    }

    $idsTareas = get_posts([
        'post_type' => 'tarea',
        'author' => get_current_user_id(),
        'meta_query' => [['key' => 'estado', 'value' => 'completada']],
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);

    if (empty($idsTareas)) {
        jsonTask(true, 'No hay tareas completadas para borrar.', 'Sin tareas completadas.', $func);
    }

    $borradas = 0;
    $errores = 0;
    foreach ($idsTareas as $id) {
        if (wp_delete_post($id, true)) $borradas++;
        else $errores++;
    }

    $msg = "$borradas tareas borradas.";
    if ($errores > 0) $msg .= " $errores errores.";
    jsonTask(true, $msg, "Borradas: $borradas, Errores: $errores.", $func);
}
add_action('wp_ajax_borrarTareasCompletadas', 'borrarTareasCompletadas');
