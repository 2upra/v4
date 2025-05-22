<?php

// Refactor(Org): Funcion actualizarSeccion() y hook AJAX movidos desde app/Content/Task/logicTareas.php

/**
 * Actualiza la sesión (campo meta 'sesion') de todas las tareas del usuario actual
 * que coincidan con un valor original.
 *
 * Se utiliza a través de AJAX.
 */
function actualizarSeccion()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $valAnt = isset($_POST['valorOriginal']) ? sanitize_text_field($_POST['valorOriginal']) : '';
    $valNue = isset($_POST['valorNuevo']) ? sanitize_text_field($_POST['valorNuevo']) : '';

    if (empty($valAnt) || empty($valNue)) {
        wp_send_json_error('Faltan datos.');
    }

    $log = "El usuario " . get_current_user_id() . " actualizo la sesion: $valAnt a: $valNue";

    $args = array(
        'post_type' => 'tarea',
        'posts_per_page' => -1,
        'author' => get_current_user_id(),
        'meta_query' => array(
            array(
                'key' => 'sesion',
                'value' => $valAnt,
                'compare' => '='
            )
        )
    );

    $tareas = get_posts($args);
    $cant = count($tareas);
    $log .= ", \n Se encontraron $cant tareas a modificar. ";

    if (empty($tareas)) {
        // Asumiendo que guardarLog() está disponible globalmente o será inyectado/requerido
        // Si no, esta llamada fallará. Considerar inyección de dependencias o un helper global.
        if (function_exists('guardarLog')) {
             guardarLog("actualizarSeccion:" . $log);
        }
        wp_send_json_success('No se encontraron tareas para actualizar.');
    }

    foreach ($tareas as $tarea) {
        update_post_meta($tarea->ID, 'sesion', $valNue);
    }

    $log .= ", \n  Se actualizaron las sesiones de las tareas.";
    // Asumiendo que guardarLog() está disponible globalmente o será inyectado/requerido
    if (function_exists('guardarLog')) {
        guardarLog("actualizarSeccion:" . $log);
    }
    wp_send_json_success();
}

add_action('wp_ajax_actualizarSeccion', 'actualizarSeccion');

function asignarSeccionMeta() {
    $func = 'asignarSeccionMeta';
    if (!current_user_can('edit_posts')) {
        jsonTask(false, 'Sin permisos.', 'Acceso denegado.', $func);
    }

    $idTarea = isset($_POST['idTarea']) ? (int)$_POST['idTarea'] : 0;
    $sesion = isset($_POST['sesion']) ? sanitize_text_field(wp_unslash($_POST['sesion'])) : ''; // wp_unslash por si acaso

    if ($idTarea <= 0) {
        jsonTask(false, 'ID de tarea inválido.', "ID Tarea: $idTarea", $func);
    }
    // El nombre de la sesión puede ser cualquier cadena, el frontend lo maneja.
    // Si es vacía, el frontend lo interpretará como 'General'.

    $tarea = get_post($idTarea);
    // Validar que la tarea exista y pertenezca al usuario o que el usuario tenga permisos para editarla.
    // Si solo el autor puede editar sus tareas:
    // if (!$tarea || $tarea->post_type !== 'tarea' || $tarea->post_author != get_current_user_id()) {
    // Si cualquier usuario con 'edit_posts' puede editar cualquier tarea de tipo 'tarea':
    if (!$tarea || $tarea->post_type !== 'tarea') {
        jsonTask(false, 'Tarea no encontrada.', "Tarea ID $idTarea no encontrada.", $func);
    }

    $resultadoUpdate = update_post_meta($idTarea, 'sesion', $sesion);

    if ($resultadoUpdate === false) {
        // Esto también puede ser false si el valor nuevo es igual al antiguo, lo cual no es un error.
        // Para un error real, podríamos comprobar si el meta realmente no se actualizó cuando debería.
        $metaActual = get_post_meta($idTarea, 'sesion', true);
        if ($metaActual !== $sesion) {
            jsonTask(false, 'Error al actualizar la sesión de la tarea en la base de datos.', "Fallo update_post_meta para tarea $idTarea, sesion $sesion", $func);
        }
    }

    jsonTask(true, ['mensaje' => "Sesión '$sesion' asignada a tarea $idTarea."], "Tarea $idTarea asignada a sesión '$sesion'.", $func);
}
add_action('wp_ajax_asignarSeccionMeta', 'asignarSeccionMeta');

function actualizarSeccionEstado($tareaMov, $sesionArr)
{
    $log = "actualizarSeccionEstado tarea:$tareaMov";

    $sesionParaActualizar = ($sesionArr === 'null' || is_null($sesionArr) || $sesionArr === '') ? "General" : $sesionArr;
    $log .= ", sesionRecibida:'$sesionArr', sesionAUsar:'$sesionParaActualizar'";

    $estadoAct = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesionTareaAct = get_post_meta($tareaMov, 'sesion', true);
    if (empty($sesionTareaAct) || $sesionTareaAct === 'null') { // Normalizar sesión actual para comparación
        $sesionTareaAct = "General";
    }
    $log .= ", estadoActual:'$estadoAct', sesionActual:'$sesionTareaAct'";

    $tarea = get_post($tareaMov);
    if(!$tarea) {
        $log .= ", error:tareaNoEncontrada";
        guardarLog($log);
        return;
    }
    $esSubtarea = !empty($tarea->post_parent);
    $hijas = [];
    if (!$esSubtarea) { // Solo las tareas principales pueden tener hijas según la nueva lógica
        $hijas = get_children(array(
            'post_parent' => $tareaMov, 'post_type' => 'tarea', 'fields' => 'ids', 'posts_per_page' => -1
        ));
    }
    $tieneSubtareas = !empty($hijas);
    $log .= ", esSubtarea:" . ($esSubtarea?'si':'no') . ", tieneSubtareas:" . ($tieneSubtareas?'si':'no');

    if (strtolower($sesionParaActualizar) !== 'general') {
        if (strtolower($sesionParaActualizar) === 'archivado' && $estadoAct !== 'archivado') {
            update_post_meta($tareaMov, 'estado', 'Archivado');
            $log .= ", estadoActualizadoA:Archivado";
            if ($tieneSubtareas) {
                foreach ($hijas as $hijaId) {
                    update_post_meta($hijaId, 'estado', 'Archivado');
                }
                $log .= ", subtareasTambiénArchivadas:" . count($hijas);
            }
        } elseif (strtolower($sesionParaActualizar) !== 'archivado' && $estadoAct === 'archivado') {
            update_post_meta($tareaMov, 'estado', 'Pendiente');
            $log .= ", estadoActualizadoA:Pendiente";
            if ($tieneSubtareas) {
                foreach ($hijas as $hijaId) {
                    update_post_meta($hijaId, 'estado', 'Pendiente');
                }
                $log .= ", subtareasTambiénPendientes:" . count($hijas);
            }
        } else {
            $log .= ", estadoNoRequirioCambio";
        }
    } else { // Sesión es 'General'
        // Si el estado era 'Archivado' y la sesión se mueve a 'General', ¿debería cambiar a 'Pendiente'?
        // Actualmente no lo hace si la $sesionParaActualizar es 'General'. Esto parece correcto.
        $log .= ", sesionEsGeneral, estadoNoCambiadoPorSesion";
    }

    // Si es una subtarea y se archiva, desvincularla (convertirla en tarea principal archivada).
    if ($esSubtarea && strtolower($sesionParaActualizar) === 'archivado') {
        wp_update_post(array('ID' => $tareaMov, 'post_parent' => 0));
        delete_post_meta($tareaMov, 'subtarea'); // Sincronizar con post_parent
        $log .= ", subtareaArchivadaYDesvinculada";
    }

    if ($sesionParaActualizar !== $sesionTareaAct) {
        update_post_meta($tareaMov, 'sesion', $sesionParaActualizar);
        $log .= ", sesionActualizadaA:'$sesionParaActualizar'";
    } else {
        $log .= ", sesionNoRequirioCambio";
    }

    $estadoFin = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesionFin = get_post_meta($tareaMov, 'sesion', true);
    if (empty($sesionFin) || $sesionFin === 'null') { // Re-normalizar por si acaso
        $sesionFin = "General";
        update_post_meta($tareaMov, 'sesion', $sesionFin); // Corregir si es necesario
    }
    $log .= ", estadoFinal:'$estadoFin', sesionFinal:'$sesionFin'";
    guardarLog($log);
}