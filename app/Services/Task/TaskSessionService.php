<?

function actualizarSeccion()
{
    $f = 'actualizarSeccion';
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
        return;
    }

    $valAnt = sanitize_text_field($_POST['valorOriginal'] ?? '');
    $valNueInput = sanitize_text_field($_POST['valorNuevo'] ?? ''); // Nombre cambiado para claridad

    if (!$valAnt || !$valNueInput) {
        wp_send_json_error('Faltan datos.');
        return;
    }

    // Normalizar la nueva sección
    $valNue = (in_array($valNueInput, ['null', '', null], true) || !$valNueInput) ? "General" : $valNueInput;

    $idUsr = get_current_user_id();
    $log = "$f: usr $idUsr de '$valAnt' a '$valNue' (input: '$valNueInput')";

    $args = [
        'post_type' => 'tarea',
        'posts_per_page' => -1,
        'author' => $idUsr,
        'meta_query' => [[
            'key' => 'sesion',
            // Comparar con el valor original sin normalizar, ya que así está en la BD
            'value' => $valAnt,
            'compare' => '='
        ]]
    ];

    $tareas = get_posts($args);
    $cantTarProc = count($tareas); // Cantidad de tareas que potencialmente se modificarán
    $log .= ", $cantTarProc tareas encontradas con sesion '$valAnt'";
    $hijasMetaAct = 0; // Contador para metadatos de hijas actualizados por actTarHijosMet
    $subtareasDesvinc = 0;

    if (!$tareas) {
        if (function_exists('guardarLog')) {
            guardarLog($log . ", no se encontraron tareas para actualizar.");
        }
        wp_send_json_success('No se encontraron tareas para actualizar.');
        return;
    }

    foreach ($tareas as $tar) {
        $idTarActual = $tar->ID;
        $esSubtarea = !empty($tar->post_parent);

        if ($esSubtarea) {
            $idPadre = $tar->post_parent;
            $sesPadreMeta = get_post_meta($idPadre, 'sesion', true);
            $sesPadreNorm = (in_array($sesPadreMeta, ['null', '', null], true) || !$sesPadreMeta) ? "General" : $sesPadreMeta;

            // $valNue ya está normalizada
            if ($valNue !== $sesPadreNorm) {
                wp_update_post(['ID' => $idTarActual, 'post_parent' => 0]);
                delete_post_meta($idTarActual, 'subtarea'); // Asumiendo que usas este meta
                $subtareasDesvinc++;
                $log .= "\n  - Subtarea $idTarActual desvinculada de padre $idPadre (sesPadre:'$sesPadreNorm', subNueva:'$valNue')";
            }
        }

        // Actualizar la sección de la tarea actual y propagar a sus hijas (si las tiene y no fue desvinculada de ellas)
        // $valNue es la sección normalizada que se guardará
        actTarHijosMet($idTarActual, 'sesion', $valNue, $hijasMetaAct);
    }

    $log .= ", $cantTarProc tareas procesadas. $subtareasDesvinc subtareas desvinc.";
    if ($hijasMetaAct > 0) {
        $log .= ", $hijasMetaAct meta de hijas actualizadas a '$valNue'";
    }

    if (function_exists('guardarLog')) {
        guardarLog($log);
    }

    $msjExito = "$cantTarProc tareas procesadas.";
    if ($subtareasDesvinc > 0) {
        $msjExito .= " $subtareasDesvinc subtarea(s) fueron desvinculada(s).";
    }
    if ($hijasMetaAct > 0) {
        $msjExito .= " $hijasMetaAct metadato(s) de tareas hijas también actualizados a '$valNue'.";
    }
    wp_send_json_success($msjExito);
}

add_action('wp_ajax_actualizarSeccion', 'actualizarSeccion');

function asignarSeccionMeta()
{
    $f = 'asignarSeccionMeta';

    if (!current_user_can('edit_posts')) {
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $f);
        return;
    }

    $idTar = (int)($_POST['idTarea'] ?? 0);
    $sesInput = sanitize_text_field(wp_unslash($_POST['sesion'] ?? '')); // Nombre cambiado

    if (!$idTar) {
        jsonTask(false, 'ID de tarea inválido.', "ID Tarea: $idTar", $f);
        return;
    }

    // Normalizar la nueva sección
    $ses = (in_array($sesInput, ['null', '', null], true) || !$sesInput) ? "General" : $sesInput;

    $tar = get_post($idTar);
    if (!$tar || $tar->post_type !== 'tarea') {
        jsonTask(false, 'Tarea no encontrada.', "Tarea ID $idTar no encontrada o no tipo tarea.", $f);
        return;
    }

    $logMsg = "$f: tarea $idTar nueva sesion '$ses' (input: '$sesInput')";
    $seDesvinculo = false;

    if (!empty($tar->post_parent)) {
        $idPadre = $tar->post_parent;
        $sesPadreMeta = get_post_meta($idPadre, 'sesion', true);
        $sesPadreNorm = (in_array($sesPadreMeta, ['null', '', null], true) || !$sesPadreMeta) ? "General" : $sesPadreMeta;

        // $ses ya está normalizada
        if ($ses !== $sesPadreNorm) {
            wp_update_post(['ID' => $idTar, 'post_parent' => 0]);
            delete_post_meta($idTar, 'subtarea'); // Asumiendo que usas este meta
            $seDesvinculo = true;
            $logMsg .= ", desvinculada de padre $idPadre (sesPadre:'$sesPadreNorm')";
        }
    }

    $hijasAct = 0;
    // $ses es la sección normalizada que se guardará
    $resUpd = actTarHijosMet($idTar, 'sesion', $ses, $hijasAct);

    if ($resUpd === false) {
        // Verificar si realmente falló o si el valor ya era el mismo.
        // get_post_meta devolverá el valor actual, que debería ser $ses si update_post_meta tuvo éxito (incluso si no cambió nada)
        $metaAct = get_post_meta($idTar, 'sesion', true);
        if ($metaAct !== $ses) {
            jsonTask(false, 'Error al actualizar la sesión.', "Fallo update_post_meta tarea $idTar a sesion $ses", $f, $logMsg . ", fallo update_post_meta");
            return;
        }
        $logMsg .= " (valor sin cambios aparentes o error no crítico en update)";
    }


    if ($hijasAct > 0) {
        $logMsg .= ", hijas $hijasAct a '$ses'";
    }

    $msjUsr = "Sesión '$ses' asignada a tarea $idTar.";
    if ($seDesvinculo) {
        $msjUsr .= " La tarea fue desvinculada de su padre.";
    }
    if ($hijasAct > 0) {
        $msjUsr .= " Y a sus $hijasAct hija(s).";
    }

    jsonTask(true, ['mensaje' => $msjUsr], $logMsg, $f);
}

add_action('wp_ajax_asignarSeccionMeta', 'asignarSeccionMeta');

function actualizarSeccionEstado($tareaMov, $sesionArr) {
    $f = 'actualizarSeccionEstado';
    $log = "$f tarea:$tareaMov";

    // Normalizar la nueva sección (si es null, vacía, etc., se considera "General")
    $sesNue = (in_array($sesionArr, ['null', '', null], true) || empty($sesionArr)) ? "General" : $sesionArr;
    $log .= ", sesRecib:'$sesionArr', sesAUsar:'$sesNue'";

    $estAct = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesAct = get_post_meta($tareaMov, 'sesion', true);
    // Normalizar la sección actual para comparación
    $sesActNorm = (in_array(strtolower($sesAct), ['null', '', null], true) || empty($sesAct)) ? "General" : $sesAct;
    $log .= ", estAct:'$estAct', sesAct:'$sesActNorm'";

    $tar = get_post($tareaMov);
    if (!$tar || $tar->post_type !== 'tarea') {
        $log .= ", error:tareaNoEnc";
        if (function_exists('guardarLog')) guardarLog($log);
        return;
    }

    $esSubOriginalmente = !empty($tar->post_parent);
    $idPadOriginal = $tar->post_parent;
    // (tienHij no es directamente relevante para la lógica de desvinculación por sección aquí)

    $log .= ", esSubOrig:" . ($esSubOriginalmente ? 'si(padreID:'.$idPadOriginal.')' : 'no');

    $hijasEstAct = 0;
    // Lógica para cambiar el estado de la tarea según la nueva sección
    if (strtolower($sesNue) !== 'general') {
        if (strtolower($sesNue) === 'archivado' && $estAct !== 'archivado') {
            actEstTarHijos($tareaMov, 'Archivado', $hijasEstAct);
            $log .= ", estUpd:Archivado";
            if ($hijasEstAct > 0) $log .= ", hijEstArchiv:" . $hijasEstAct;
        } elseif (strtolower($sesNue) !== 'archivado' && $estAct === 'archivado') {
            // Si se mueve DESDE 'archivado' a otra sección (que no sea 'General')
            actEstTarHijos($tareaMov, 'Pendiente', $hijasEstAct);
            $log .= ", estUpd:Pendiente";
            if ($hijasEstAct > 0) $log .= ", hijEstPend:" . $hijasEstAct;
        } else {
            $log .= ", estNoCambioPorReglaSeccion";
        }
    } else { // Si la nueva sección es 'General'
        if ($estAct === 'archivado') { // Si estaba archivada y se mueve a General
             actEstTarHijos($tareaMov, 'Pendiente', $hijasEstAct);
             $log .= ", estUpd:Pendiente (de Archivado a General)";
             if ($hijasEstAct > 0) $log .= ", hijEstPend:" . $hijasEstAct;
        } else {
            $log .= ", sesEsGeneral, estNoCambioPorSes";
        }
    }

    // Ya NO se necesita la desvinculación explícita aquí:
    // if ($esSub && strtolower($sesNue) === 'archivado') { ... }
    // La función actTarHijosMet se encargará de desvincular si $tareaMov es subtarea
    // y su nueva sección $sesNue difiere de la sección de su padre.

    $hijasSesAct = 0;
    if (strtolower($sesNue) !== strtolower($sesActNorm)) { // Solo si la sección realmente cambia
        // actTarHijosMet actualizará la sesión de $tareaMov y sus hijas.
        // También aplicará la lógica de desvinculación a $tareaMov si es subtarea
        // y su nueva sección $sesNue es diferente de la sección de su padre.
        actTarHijosMet($tareaMov, 'sesion', $sesNue, $hijasSesAct);
        $log .= ", sesUpd:'$sesNue'";
        if ($hijasSesAct > 0) {
            $log .= ", hijSesUpd:" . $hijasSesAct . " a '$sesNue'";
        }
    } else {
        $log .= ", sesNoCambio ($sesNue vs $sesActNorm)";
    }
    
    // Verificar si la tarea fue desvinculada para el log
    $tarDespues = get_post($tareaMov);
    $esSubDespues = !empty($tarDespues->post_parent);
    if ($esSubOriginalmente && !$esSubDespues) {
        $log .= ", tarea $tareaMov desvinculada de padre $idPadOriginal (ahora es principal)";
    } elseif ($esSubOriginalmente && $esSubDespues && $idPadOriginal != $tarDespues->post_parent) {
        // Esto no debería ocurrir con la lógica actual, pero por si acaso
        $log .= ", tarea $tareaMov cambió de padre $idPadOriginal a ".$tarDespues->post_parent;
    } elseif (!$esSubOriginalmente && $esSubDespues) {
        // Esto implicaría que se convirtió en subtarea, lo cual no hace esta función.
        $log .= ", tarea $tareaMov convertida en subtarea de ".$tarDespues->post_parent." (inesperado)";
    }


    $estFin = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesFin = get_post_meta($tareaMov, 'sesion', true);
    // Asegurar que la sesión final no quede vacía/nula en la BD para $tareaMov.
    if (in_array($sesFin, ['null', '', null], true) || empty($sesFin)) {
        $sesFin = "General"; // Coincide con $sesNue si $sesionArr era vacío/null
        update_post_meta($tareaMov, 'sesion', $sesFin);
    }
    $log .= ", estFin:'$estFin', sesFin:'$sesFin'";
    if (function_exists('guardarLog')) guardarLog($log);
}