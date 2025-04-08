<?php
// Refactor(Org): Funcion crearTarea() y hook AJAX movidos desde app/Content/Task/logicTareas.php
// La funcion crearTarea() y su hook AJAX ya estaban presentes en este archivo.
// Este archivo se esta volviendo muy grande, hay que organizar mejor.

// Refactor(Org): Funcion crearTarea() y hook AJAX movidos a app/Services/Task/TaskCrudService.php

// Refactor(Org): Funcion borrarTarea() y hook AJAX movidos desde app/Content/Task/logicTareas.php
function borrarTarea()
{
    // Añadir verificacion de nonce
    if (!isset($_POST['nonce']) || empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'borrar_tarea_nonce')) {
        wp_send_json_error('Nonce invalido.');
        // wp_die(); // wp_send_json_error ya incluye wp_die()
    }

    $log = '';
    if (!current_user_can('edit_posts')) {
        $log .= 'No tienes permisos.';
        guardarLog("borrarTarea: \n $log");
        wp_send_json_error('No tienes permisos.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id === 0) {
        $log .= 'ID de tarea inválido.';
        guardarLog("borrarTarea: \n $log");
        wp_send_json_error('ID de tarea inválido.');
    }

    $tarea = get_post($id);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        $log .= 'Tarea no encontrada.';
        guardarLog("borrarTarea: \n $log");
        wp_send_json_error('Tarea no encontrada.');
    }

    $res = wp_delete_post($id, true);

    if (is_wp_error($res)) {
        $msg = $res->get_error_message();
        $log .= "Error al borrar tarea: $msg";
        guardarLog("borrarTarea: \n $log");
        wp_send_json_error($msg);
    }

    $log .= "Tarea con ID $id borrada exitosamente.";
    guardarLog("borrarTarea: \n $log");
    wp_send_json_success();
}

add_action('wp_ajax_borrarTarea', 'borrarTarea');

// Refactor(Org): Funcion modificarTarea() y hook AJAX movidos desde app/Content/Task/logicTareas.php
function modificarTarea()
{
    $log = '';
    if (!current_user_can('edit_posts')) {
        $log .= 'No tienes permisos.';
        guardarLog("modificarTarea: \n $log");
        wp_send_json_error('No tienes permisos.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tit = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';

    if (empty($tit)) {
        $log .= 'Título vacío.';
        guardarLog("modificarTarea: \n $log");
        wp_send_json_error('Título vacío.');
    }

    if ($id === 0) {
        $tareaId = crearTarea(); // Captura el ID devuelto por crearTarea()

        if (is_wp_error($tareaId)) {
            wp_send_json_error($tareaId->get_error_message());
        } else {
            wp_send_json_success(array('id' => $tareaId)); // Envía el ID en la respuesta
        }

        return;
    }

    $tarea = get_post($id);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        $log .= 'Tarea no encontrada.';
        guardarLog("modificarTarea: \n $log");
        wp_send_json_error('Tarea no encontrada.');
    }

    $args = array(
        'ID' => $id,
        'post_title' => $tit
    );

    $res = wp_update_post($args, true);

    if (is_wp_error($res)) {
        $msg = $res->get_error_message();
        $log .= "Error al modificar tarea: $msg \n";
        guardarLog("modificarTarea: \n $log");
        wp_send_json_error($msg);
    }

    $log .= "Tarea modificada con id $id";
    guardarLog("modificarTarea: \n $log");
    wp_send_json_success();
}

add_action('wp_ajax_modificarTarea', 'modificarTarea');

// Refactor(Org): Funcion archivarTarea() y hook AJAX movidos desde app/Content/Task/logicTareas.php
//necesito que cuando se archive una tarea, deje ser una subtarea en caso de que lo hubiera sido, y si tenia tareas hijos, sus tareas hijos tambien se archiven
function archivarTarea()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tarea = get_post($id);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    $log = "Funcion archivarTarea(). \n  ID: $id. \n";

    $usu = get_current_user_id();
    $orden = get_user_meta($usu, 'ordenTareas', true);
    $estadoActual = get_post_meta($id, 'estado', true);

    $log .= "Estado inicial de la tarea: $estadoActual. \n";

    if ($estadoActual == 'archivado') {
        update_post_meta($id, 'estado', 'pendiente');
        update_post_meta($id, 'sesion', 'General');
        $log .= "Se cambio el estado de la tarea $id a pendiente y la sesion a General.";
        // Eliminar la relación de subtarea si la tarea estaba archivada y se desarchiva
        wp_update_post(array(
            'ID' => $id,
            'post_parent' => 0
        ));
        delete_post_meta($id, 'subtarea');
        $log .= ", \n  Se eliminó la relación de subtarea para la tarea $id.";
    } else {
        if (is_array($orden) && in_array($id, $orden)) {
            $pos = array_search($id, $orden);
            unset($orden[$pos]);
            $orden[] = $id;
            update_user_meta($usu, 'ordenTareas', $orden);
            $log .= "Se actualizo el orden de la tarea $id, moviendola al final. \n";
        }

        // Archivar subtareas (tareas hijas)
        $args = array(
            'post_parent' => $id,
            'post_type'   => 'tarea',
            'numberposts' => -1,
            'post_status' => 'any'
        );
        $subtareas = get_children($args);

        foreach ($subtareas as $subtarea) {
            update_post_meta($subtarea->ID, 'estado', 'archivado');
            $log .= ", \n  Se archivó la subtarea {$subtarea->ID}.";
        }

        // Eliminar la relación de subtarea si la tarea se está archivando
        wp_update_post(array(
            'ID' => $id,
            'post_parent' => 0
        ));
        delete_post_meta($id, 'subtarea');
        $log .= ", \n  Se eliminó la relación de subtarea para la tarea $id.";

        update_post_meta($id, 'estado', 'archivado');
        $log .= ", \n  Se cambió el estado de la tarea $id a archivado.";
    }

    guardarLog($log);
    wp_send_json_success();
}

add_action('wp_ajax_archivarTarea', 'archivarTarea');

// Refactor(Org): Funcion completarTarea() y hook AJAX movidos a app/Services/Task/TaskCrudService.php [COMPLETED]

// Refactor(Org): Funcion actualizarOrdenTareas() y hook AJAX movidos desde app/Content/Task/logicTareas.php
function actualizarOrdenTareas()
{
    $usu = get_current_user_id();
    $tareaMov = isset($_POST['tareaMovida']) ? intval($_POST['tareaMovida']) : null;
    $ordenNue = isset($_POST['ordenNuevo']) ? explode(',', $_POST['ordenNuevo']) : [];
    $ordenNue = array_map('intval', $ordenNue);
    $sesionArr = isset($_POST['sesionArriba']) ? strtolower(sanitize_text_field($_POST['sesionArriba'])) : null;
    $ordenTar = get_user_meta($usu, 'ordenTareas', true) ?: [];
    $esSubtarea = isset($_POST['subtarea']) ? $_POST['subtarea'] === 'true' : false;
    $padre = isset($_POST['padre']) ? intval($_POST['padre']) : 0;

    $log = "actualizarOrdenTareas: \n  Usuario ID: $usu, \n  Tarea movida: $tareaMov, \n  Nuevo orden recibido: " . implode(',', $ordenNue) . ", \n  Orden antes de cambiar: " . implode(',', $ordenTar) . ", \n  Sesion arriba: $sesionArr";

    if ($tareaMov !== null && !empty($ordenNue)) {
        // Manejar creación o eliminación de subtareas
        if ($esSubtarea) {
            $log .= ", \n  " . manejarSubtarea($tareaMov, $padre);
        } else {
            // Si no es una subtarea, pero tiene el metadato 'subtarea', eliminarlo
            $subtareaExistente = get_post_meta($tareaMov, 'subtarea', true);
            if (!empty($subtareaExistente)) {
                $log .= ", \n  " . manejarSubtarea($tareaMov, 0);
            }
        }

        $ordenTar = actualizarOrden($ordenTar, $ordenNue);
        actualizarSesionEstado($tareaMov, $sesionArr);
        $log .= ", \n  Orden de tareas actualizado exitosamente para el usuario $usu";
        guardarLog($log);
        wp_send_json_success(['ordenTareas' => $ordenTar]);
    } else {
        $log .= ", \n  Error: ";
        if ($tareaMov === null) {
            $log .= "tareaMovida es null";
        }
        if (empty($ordenNue)) {
            $log .= ($tareaMov === null ? ", " : "") . "ordenNuevo está vacío";
        }
        guardarLog($log);
        wp_send_json_error(['error' => 'Falta información para actualizar el orden de tareas.'], 400);
    }
}

//aqui hay que ajustar algo, hay un error, a veces las tareas padres se vuelven subtareas de sus propios hijos, hay que evitar eso, supongo que  es facil de evitar
function manejarSubtarea($id, $idPadre)
{
    $log = '';
    if ($idPadre) {
        $tareaPadre = get_post($idPadre);
        if (empty($tareaPadre) || $tareaPadre->post_type != 'tarea') {
            return 'Error: Tarea padre no encontrada.';
        }

        // Verificar si la tarea padre es una subtarea de la tarea actual
        if (esPadreUnaSubtarea($idPadre, $id)) {
            return 'Error: No se puede convertir la tarea en subtarea de una de sus propias subtareas.';
        }

        $subtareaExistente = get_post_meta($id, 'subtarea', true);

        if (empty($subtareaExistente)) {
            $res = wp_update_post(array(
                'ID' => $id,
                'post_parent' => $idPadre
            ), true);

            if (is_wp_error($res)) {
                return 'Error al crear subtarea: ' . $res->get_error_message();
            }

            update_post_meta($id, 'subtarea', $idPadre);
            $log .= "Se creó la subtarea $id, tarea padre $idPadre. ";
        } else {
            $log .= "La subtarea $id ya existía como subtarea de $idPadre. No se realizaron cambios. ";
        }
    } else {
        // Eliminar subtarea
        $res = wp_update_post(array(
            'ID' => $id,
            'post_parent' => 0
        ), true);

        if (is_wp_error($res)) {
            return 'Error al eliminar subtarea: ' . $res->get_error_message();
        }

        delete_post_meta($id, 'subtarea');
        $log .= "Se eliminó la subtarea $id. ";
    }

    return $log;
}

function esPadreUnaSubtarea($idPadre, $id)
{
    $padreActual = $idPadre;
    while ($padreActual) {
        if ($padreActual == $id) {
            return true; // La tarea padre es una subtarea (directa o indirecta) de la tarea actual
        }
        $padreActual = get_post_meta($padreActual, 'subtarea', true);
    }
    return false; // La tarea padre no es una subtarea de la tarea actual
}

function actualizarOrden($ordenTar, $ordenNue)
{
    $log = "actualizarOrden: ";

    $usu = get_current_user_id();
    update_user_meta($usu, 'ordenTareas', $ordenNue);
    $log .= "\n  Se actualizó el orden de tareas para el usuario $usu a: " . implode(',', $ordenNue);

    guardarLog($log);
    return $ordenNue;
}

//esto funciona bien, solo necesito que si una tarea padre si archiva, sus hijas tambien, o si desarchiva, sus hijas tambien, y si una hijo es archivado, entonces, deja de ser una subtarea, asi de simple. Por rendimiento, esto obviamente debe suceder si se trata de una subtarea o un tarea padre con hijas
function actualizarSesionEstado($tareaMov, $sesionArr)
{
    $log = "actualizarSesionEstado: ";

    // Tratar 'null' como string
    $sesionArrString = is_null($sesionArr) ? "null" : $sesionArr;

    // Si $sesionArr es 'null' o null, usar "General"
    $sesionParaActualizar = ($sesionArrString === 'null') ? "General" : $sesionArr;

    $estadoAct = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesionTarea = get_post_meta($tareaMov, 'sesion', true);

    // Si $sesionTarea es null, 'null' o una cadena vacía, forzar a "General"
    if (empty($sesionTarea) || $sesionTarea === 'null') {
        $sesionTarea = "General";
    }

    $log .= "\n  Se recibió: '" . var_export($sesionArrString, true) . "' para la tarea '$tareaMov'.";
    $log .= "\n  Estado actual de la tarea '$tareaMov' es '$estadoAct'.";
    $log .= "\n  Sesión actual de la tarea '$tareaMov' es '" . var_export($sesionTarea, true) . "'.";

    // Obtener información sobre la tarea padre y las subtareas
    $tarea = get_post($tareaMov);
    $esSubtarea = !empty($tarea->post_parent);
    $tieneSubtareas = false;
    if (!$esSubtarea) {
        $hijas = get_children(array(
            'post_parent' => $tareaMov,
            'post_type'   => 'tarea',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        $tieneSubtareas = !empty($hijas);
    }

    // Si la sesión es "General", no se cambie el estado
    if (strtolower($sesionParaActualizar) !== 'general') {
        if (strtolower($sesionParaActualizar) === 'archivado' && $estadoAct !== 'archivado') {
            update_post_meta($tareaMov, 'estado', 'Archivado');
            $log .= "\n  Se actualizó el estado de la tarea '$tareaMov' a 'Archivado'.";

            // Si es una tarea padre, archivar también las subtareas
            if ($tieneSubtareas) {
                foreach ($hijas as $hija) {
                    update_post_meta($hija->ID, 'estado', 'Archivado');
                    $log .= "\n  Se actualizó el estado de la subtarea '{$hija->ID}' a 'Archivado'.";
                }
            }
        } elseif (strtolower($sesionParaActualizar) !== 'archivado' && $estadoAct === 'archivado') {
            update_post_meta($tareaMov, 'estado', 'Pendiente');
            $log .= "\n  Se actualizó el estado de la tarea '$tareaMov' a 'Pendiente'.";

            // Si es una tarea padre, desarchivar también las subtareas
            if ($tieneSubtareas) {
                foreach ($hijas as $hija) {
                    update_post_meta($hija->ID, 'estado', 'Pendiente');
                    $log .= "\n  Se actualizó el estado de la subtarea '{$hija->ID}' a 'Pendiente'.";
                }
            }
        } else {
            $log .= "\n  No se actualizó el estado de la tarea '$tareaMov' porque no era necesario.";
        }
    } else {
        $log .= "\n  La sesion es 'General', no se cambia el estado.";
    }

    // Si es una subtarea y se archiva, eliminar la relación de subtarea
    if ($esSubtarea && strtolower($sesionParaActualizar) === 'archivado') {
        wp_update_post(array(
            'ID' => $tareaMov,
            'post_parent' => 0
        ));
        delete_post_meta($tareaMov, 'subtarea');
        $log .= "\n  La tarea '$tareaMov' era una subtarea y se archivó, se eliminó la relación de subtarea.";
    }

    // Actualizar la sesión siempre que $sesionParaActualizar sea diferente a la actual
    if ($sesionParaActualizar !== $sesionTarea) {
        update_post_meta($tareaMov, 'sesion', $sesionParaActualizar);
        $log .= "\n  Se actualizó la sesión de la tarea '$tareaMov' a '$sesionParaActualizar'.";
    } else {
        $log .= "\n  No se actualizó la sesión de la tarea '$tareaMov' porque es la misma que la actual.";
    }

    $estadoFin = strtolower(get_post_meta($tareaMov, 'estado', true));
    $sesionFin = get_post_meta($tareaMov, 'sesion', true);

    // Si $sesionFin es null, 'null' o una cadena vacía, forzar a "General"
    if (empty($sesionFin) || $sesionFin === 'null') {
        $sesionFin = "General";
        update_post_meta($tareaMov, 'sesion', $sesionFin);
        $log .= "\n  Se corrigió la sesión final de la tarea '$tareaMov' a 'General'.";
    }

    $log .= "\n  Estado final de la tarea '$tareaMov' es '$estadoFin'.";
    $log .= "\n  Sesión final de la tarea '$tareaMov' es '$sesionFin'.";

    guardarLog($log);
}

add_action('wp_ajax_actualizarOrdenTareas', 'actualizarOrdenTareas');

// Refactor(Org): Funcion cambiarPrioridad() y hook AJAX movidos desde app/Content/Task/logicTareas.php
function cambiarPrioridad()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $tareaId = isset($_POST['tareaId']) ? intval($_POST['tareaId']) : 0;
    $prioridad = isset($_POST['prioridad']) ? sanitize_text_field($_POST['prioridad']) : '';

    $tarea = get_post($tareaId);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    if (!in_array($prioridad, ['baja', 'media', 'alta', 'importante'])) {
        wp_send_json_error('Prioridad inválida.');
    }

    $impnum = 0;
    if ($prioridad === 'importante') {
        $impnum = 4;
    } elseif ($prioridad === 'alta') {
        $impnum = 3;
    } elseif ($prioridad === 'media') {
        $impnum = 2;
    } elseif ($prioridad === 'baja') {
        $impnum = 1;
    }

    update_post_meta($tareaId, 'importancia', $prioridad);
    update_post_meta($tareaId, 'impnum', $impnum); // Guarda el valor numérico

    wp_send_json_success();
}

add_action('wp_ajax_cambiarPrioridad', 'cambiarPrioridad');

// Refactor(Org): Funcion cambiarFrecuencia() y hook AJAX movidos desde app/Content/Task/logicTareas.php
function cambiarFrecuencia()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $tareaId = isset($_POST['tareaId']) ? intval($_POST['tareaId']) : 0;
    $frec = isset($_POST['frecuencia']) ? intval($_POST['frecuencia']) : 0;

    $tarea = get_post($tareaId);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    if ($frec < 1 || $frec > 365) {
        wp_send_json_error('Frecuencia inválida.');
    }

    $fec = date('Y-m-d');
    $fecprox = date('Y-m-d', strtotime("+{$frec} days"));

    update_post_meta($tareaId, 'frecuencia', $frec);
    update_post_meta($tareaId, 'fechaProxima', $fecprox);

    $log = "Frecuencia de tarea actualizada correctamente. ID: $tareaId, Frecuencia: $frec \n Fecha proxima: $fecprox";
    guardarLog("cambiarFrecuencia:  \n $log");
    wp_send_json_success();
}

add_action('wp_ajax_cambiarFrecuencia', 'cambiarFrecuencia');

// Refactor(Org): Funcion crearSubtarea() y hook AJAX movidos desde app/Content/Task/logicTareas.php
function crearSubtarea()
{
    if (!current_user_can('edit_posts')) {
        $msg = 'No tienes permisos.';
        guardarLog("crearSubtarea: $msg");
        wp_send_json_error($msg);
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $esSubtarea = isset($_POST['subtarea']) ? $_POST['subtarea'] === 'true' : false;
    $idPadre = isset($_POST['padre']) ? intval($_POST['padre']) : 0;
    $log = '';

    if (!$esSubtarea) {
        $res = wp_update_post(array(
            'ID' => $id,
            'post_parent' => 0
        ), true);

        if (is_wp_error($res)) {
            $msg = $res->get_error_message();
            guardarLog("crearSubtarea: $msg");
            wp_send_json_error($msg);
        }

        delete_post_meta($id, 'subtarea');
        $log .= "Se eliminó la subtarea $id. ";
        guardarLog("crearSubtarea: $log");
        wp_send_json_success();
    }

    if ($idPadre) {
        $tareaPadre = get_post($idPadre);
        if (empty($tareaPadre) || $tareaPadre->post_type != 'tarea') {
            $msg = 'Tarea padre no encontrada.';
            guardarLog("crearSubtarea: $msg");
            wp_send_json_error($msg);
        }

        $subtareaExistente = get_post_meta($id, 'subtarea', true);

        if (empty($subtareaExistente)) {
            $res = wp_update_post(array(
                'ID' => $id,
                'post_parent' => $idPadre
            ), true);

            if (is_wp_error($res)) {
                $msg = $res->get_error_message();
                guardarLog("crearSubtarea: $msg");
                wp_send_json_error($msg);
            }

            update_post_meta($id, 'subtarea', $idPadre);
            $log .= "Se creó la subtarea $id, tarea padre $idPadre. ";
        } else {
            $log .= "La subtarea $id ya existía como subtarea de $idPadre. No se realizaron cambios. ";
        }
    }

    guardarLog("crearSubtarea: $log");
    wp_send_json_success();
}

add_action('wp_ajax_crearSubtarea', 'crearSubtarea');

// Refactor(Org): Funcion borrarTareasCompletadas() y hook AJAX movidos desde app/Content/Task/logicTareas.php
function borrarTareasCompletadas()
{
    if (isset($_POST['limpiar']) && $_POST['limpiar'] === 'true') {
        $usuarioActual = get_current_user_id();

        $args = array(
            'post_type'      => 'tarea',
            'author'         => $usuarioActual,
            'meta_query'     => array(
                array(
                    'key'   => 'estado',
                    'value' => 'completada',
                ),
            ),
            'posts_per_page' => -1,
        );

        $tareas = get_posts($args);

        if (empty($tareas)) {
            wp_send_json_error('No hay tareas completadas');
        } else {
            foreach ($tareas as $tarea) {
                wp_delete_post($tarea->ID, true);
            }
            wp_send_json_success('Tareas completadas borradas exitosamente');
        }
    } else {
        wp_send_json_error('No se solicitó limpiar');
    }
    wp_die();
}
add_action('wp_ajax_borrarTareasCompletadas', 'borrarTareasCompletadas');

// Refactor(Org): Funcion ordenamientoTareas() movida a app/Services/Task/TaskOrderingService.php
// Refactor(Org): Funciones ordenamientoTareas() y ordenamientoTareasPorPrioridad() movidas desde app/Content/Task/ordenamientoTareas.php

function ordenamientoTareasPorPrioridad($queryArgs, $usu)
{
    global $wpdb;
    $tareasPend = [];
    $tareasNoPend = [];
    $ordenActual = get_user_meta($usu, 'ordenTareas', true);
    $log = "ordenamientoTareasPorPrioridad, \n ";
    $todasTareasArgs = [
        'post_type'      => 'tarea',
        'author'         => $usu,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $todasTareas = get_posts($todasTareasArgs);

    if (empty($todasTareas) || !is_array($todasTareas)) {
        $log .= "No se encontraron tareas para el usuario $usu";
        guardarLog($log);
        return $queryArgs;
    }

    foreach ($todasTareas as $tareaId) {
        $estado = strtolower(get_post_meta($tareaId, 'estado', true));
        if ($estado === 'pendiente') {
            $tareasPend[] = $tareaId;
        } else {
            $tareasNoPend[] = $tareaId;
        }
    }

    if (!is_array($ordenActual)) {
        $ordenActual = [];
    }

    $tareasPendOrdenadas = [];
    foreach ($ordenActual as $tareaId) {
        if (in_array($tareaId, $tareasPend)) {
            $tareasPendOrdenadas[] = $tareaId;
        }
    }

    $tareasPendNoOrdenadas = array_diff($tareasPend, $tareasPendOrdenadas);

    usort($tareasPendNoOrdenadas, function ($a, $b) use ($wpdb) {
        $impnumA = get_post_meta($a, 'impnum', true);
        $impnumB = get_post_meta($b, 'impnum', true);
        return $impnumB - $impnumA;
    });

    $tareasPend = array_merge($tareasPendOrdenadas, $tareasPendNoOrdenadas);

    $tareasOrd = array_merge($tareasPend, $tareasNoPend);

    update_user_meta($usu, 'ordenTareas', $tareasOrd);
    $log .= "Se actualizaron las IDs de ordenTareas para el usuario $usu";
    guardarLog($log);

    $queryArgs['post__in'] = $tareasOrd;
    $queryArgs['orderby'] = 'post__in';
    unset($queryArgs['meta_key']);
    unset($queryArgs['meta_query']);
    unset($queryArgs['order']);
    unset($queryArgs['orderby']);


    return $queryArgs;
}
