<?php

// Refactor(Org): Funciones formTarea() y formTareaEstilo() movidas a app/View/Components/TaskForm.php

#Los selectores sImportancia y sTipo ya abren los submenu que son los A1806241, al ejecutarse el script tiene que remplazar el texto ejemplo y poner el primer valor de los botones, esta info se guarda en variables globales, y si el usuario da click a otro boton, se cambia, dame esa parte del script en una funcion, js vanilla 

// Refactor(Org): Funcion borrarTarea() y hook AJAX movidos a app/Services/TaskService.php

// Refactor(Org): Funcion modificarTarea() y hook AJAX movidos a app/Services/TaskService.php

// Refactor(Org): Funcion crearTarea() y hook AJAX movidos a app/Services/TaskService.php
// La funcion crearTarea() y su hook AJAX ya no estaban presentes en este archivo.

// Refactor(Org): Funcion completarTarea() y hook AJAX movidos a app/Services/TaskService.php


// Refactor(Org): Funcion archivarTarea() y hook AJAX movidos a app/Services/TaskService.php




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

//te muestro como se crean las subtareas 
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


function actualizarSesion()
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
        guardarLog("actualizarSesion:" . $log);
        wp_send_json_success('No se encontraron tareas para actualizar.');
    }

    foreach ($tareas as $tarea) {
        update_post_meta($tarea->ID, 'sesion', $valNue);
    }

    $log .= ", \n  Se actualizaron las sesiones de las tareas.";
    guardarLog("actualizarSesion:" . $log);
    wp_send_json_success();
}

add_action('wp_ajax_actualizarSesion', 'actualizarSesion');
