<?

// Función de ayuda: Actualiza un metadato en una tarea y todas sus hijas directas
// Devuelve el estado de la actualización del padre y cuenta las hijas actualizadas.
function actTarHijosMet($idTar, $clave, $valor, &$cntHijAct = 0) {
    $res = update_post_meta($idTar, $clave, $valor);
    $hijos = get_children([
        'post_parent' => $idTar,
        'post_type'   => 'tarea',
        'numberposts' => -1,
        'fields'      => 'ids'
    ]);
    if ($hijos) {
        foreach ($hijos as $idHijo) {
            update_post_meta($idHijo, $clave, $valor);
            $cntHijAct++;
        }
    }
    return $res;
}

// Función de ayuda: Actualiza el estado de una tarea y todas sus hijas directas.
// Esta es más específica que actTarHijosMet para el metadato 'estado'.
function actEstTarHijos($idTar, $estNue, &$cntHijAct = 0) {
    update_post_meta($idTar, 'estado', $estNue);
    $hijos = get_children([
        'post_parent' => $idTar,
        'post_type'   => 'tarea',
        'fields'      => 'ids',
        'posts_per_page' => -1
    ]);
    if ($hijos) {
        foreach ($hijos as $idHijo) {
            update_post_meta($idHijo, 'estado', $estNue);
            $cntHijAct++;
        }
    }
}

function actualizarSeccion() {
    $f = 'actualizarSeccion';
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
        return;
    }

    $valAnt = sanitize_text_field($_POST['valorOriginal'] ?? '');
    $valNue = sanitize_text_field($_POST['valorNuevo'] ?? '');

    if (!$valAnt || !$valNue) {
        wp_send_json_error('Faltan datos.');
        return;
    }

    $idUsr = get_current_user_id();
    $log = "$f: usr $idUsr de '$valAnt' a '$valNue'";

    $args = [
        'post_type' => 'tarea',
        'posts_per_page' => -1,
        'author' => $idUsr,
        'meta_query' => [[
            'key' => 'sesion',
            'value' => $valAnt,
            'compare' => '='
        ]]
    ];

    $tareas = get_posts($args);
    $cantTar = count($tareas);
    $log .= ", $cantTar tareas encontradas";
    $hijasAct = 0;

    if (!$tareas) {
        if (function_exists('guardarLog')) {
            guardarLog($log . ", no se encontraron tareas.");
        }
        wp_send_json_success('No se encontraron tareas para actualizar.');
        return;
    }

    foreach ($tareas as $tar) {
        actTarHijosMet($tar->ID, 'sesion', $valNue, $hijasAct);
    }

    $log .= ", $cantTar tareas padres actualizadas";
    if ($hijasAct > 0) {
        $log .= ", $hijasAct tareas hijas actualizadas a '$valNue'";
    }

    if (function_exists('guardarLog')) {
        guardarLog($log);
    }
    wp_send_json_success("$cantTar tareas actualizadas. $hijasAct hijas también actualizadas.");
}

add_action('wp_ajax_actualizarSeccion', 'actualizarSeccion');

function asignarSeccionMeta() {
    $f = 'asignarSeccionMeta';

    if (!current_user_can('edit_posts')) {
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $f);
        return;
    }

    $idTar = (int)($_POST['idTarea'] ?? 0);
    $ses = sanitize_text_field(wp_unslash($_POST['sesion'] ?? ''));

    if (!$idTar) {
        jsonTask(false, 'ID de tarea inválido.', "ID Tarea: $idTar", $f);
        return;
    }

    $tar = get_post($idTar);
    if (!$tar || $tar->post_type !== 'tarea') {
        jsonTask(false, 'Tarea no encontrada.', "Tarea ID $idTar no encontrada o no tipo tarea.", $f);
        return;
    }

    $hijasAct = 0;
    $resUpd = actTarHijosMet($idTar, 'sesion', $ses, $hijasAct);

    $logMsg = "tarea $idTar sesion '$ses'";

    if ($resUpd === false) {
        $metaAct = get_post_meta($idTar, 'sesion', true);
        if ($metaAct !== $ses) {
            jsonTask(false, 'Error al actualizar la sesión.', "Fallo update_post_meta tarea $idTar a sesion $ses", $f);
            return;
        }
        $logMsg .= " (valor sin cambios)";
    }

    if ($hijasAct > 0) {
        $logMsg .= ", hijas $hijasAct a '$ses'";
    }

    $msjUsr = "Sesión '$ses' asignada a tarea $idTar.";
    if ($hijasAct > 0) {
        $msjUsr .= " Y a sus $hijasAct hija(s).";
    }

    jsonTask(true, ['mensaje' => $msjUsr], $logMsg, $f);
}

add_action('wp_ajax_asignarSeccionMeta', 'asignarSeccionMeta');

function actualizarSeccionEstado($tareaMov, $sesionArr) {
    $f = 'actualizarSeccionEstado';
    $log = "$f tarea:$tareaMov";

    $sesNue = (in_array($sesionArr, ['null', '', null], true)) ? "General" : $sesionArr;
    $log .= ", sesRecib:'$sesionArr', sesAUsar:'$sesNue'";

    $estAct = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesAct = get_post_meta($tareaMov, 'sesion', true);
    $sesAct = (in_array($sesAct, ['null', '', null], true)) ? "General" : $sesAct;
    $log .= ", estAct:'$estAct', sesAct:'$sesAct'";

    $tar = get_post($tareaMov);
    if (!$tar) {
        $log .= ", error:tareaNoEnc";
        if (function_exists('guardarLog')) guardarLog($log);
        return;
    }

    $esSub = !empty($tar->post_parent);
    $tienHij = false;
    if (!$esSub) {
        $hijosIds = get_children([
            'post_parent' => $tareaMov,
            'post_type' => 'tarea',
            'fields' => 'ids',
            'posts_per_page' => -1
        ]);
        $tienHij = !empty($hijosIds);
    }
    $log .= ", esSub:" . ($esSub ? 'si' : 'no') . ", tienHij:" . ($tienHij ? 'si' : 'no');

    $hijasEstAct = 0;
    if (strtolower($sesNue) !== 'general') {
        if (strtolower($sesNue) === 'archivado' && $estAct !== 'archivado') {
            actEstTarHijos($tareaMov, 'Archivado', $hijasEstAct);
            $log .= ", estUpd:Archivado";
            if ($hijasEstAct > 0) $log .= ", hijEstArchiv:" . $hijasEstAct;
        } elseif (strtolower($sesNue) !== 'archivado' && $estAct === 'archivado') {
            actEstTarHijos($tareaMov, 'Pendiente', $hijasEstAct);
            $log .= ", estUpd:Pendiente";
            if ($hijasEstAct > 0) $log .= ", hijEstPend:" . $hijasEstAct;
        } else {
            $log .= ", estNoCambio";
        }
    } else {
        $log .= ", sesEsGeneral, estNoCambioPorSes";
    }

    if ($esSub && strtolower($sesNue) === 'archivado') {
        wp_update_post(['ID' => $tareaMov, 'post_parent' => 0]);
        delete_post_meta($tareaMov, 'subtarea');
        $log .= ", subArchivDesvinc";
    }

    $hijasSesAct = 0;
    if ($sesNue !== $sesAct) {
        actTarHijosMet($tareaMov, 'sesion', $sesNue, $hijasSesAct);
        $log .= ", sesUpd:'$sesNue'";
        if ($hijasSesAct > 0) {
            $log .= ", hijSesUpd:" . $hijasSesAct . " a '$sesNue'";
        }
    } else {
        $log .= ", sesNoCambio";
    }

    $estFin = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesFin = get_post_meta($tareaMov, 'sesion', true);
    if (in_array($sesFin, ['null', '', null], true)) {
        $sesFin = "General";
        update_post_meta($tareaMov, 'sesion', $sesFin);
    }
    $log .= ", estFin:'$estFin', sesFin:'$sesFin'";
    if (function_exists('guardarLog')) guardarLog($log);
}