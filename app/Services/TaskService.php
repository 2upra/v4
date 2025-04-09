<?php
// Refactor(Org): Funcion crearTarea() y hook AJAX movidos desde app/Content/Task/logicTareas.php
// La funcion crearTarea() y su hook AJAX ya estaban presentes en este archivo.
// Este archivo se esta volviendo muy grande, hay que organizar mejor.

// Refactor(Org): Funcion crearTarea() y hook AJAX movidos a app/Services/Task/TaskCrudService.php
// La funcion crearTarea() ya no se encuentra en este archivo, fue movida a TaskCrudService.php.

// Refactor(Org): Funcion borrarTarea() y hook AJAX movidos a app/Services/Task/TaskCrudService.php
// La funcion borrarTarea() ya no se encuentra en este archivo, fue movida a TaskCrudService.php.

// Refactor(Org): Funcion modificarTarea() y hook AJAX movidos a app/Services/Task/TaskCrudService.php
// La funcion modificarTarea() ya no se encuentra en este archivo, fue movida a TaskCrudService.php.

// Refactor(Org): Funcion archivarTarea() y hook AJAX movidos a app/Services/Task/TaskCrudService.php
// La funcion archivarTarea() ya no se encuentra en este archivo, fue movida a TaskCrudService.php.

// Refactor(Org): Funcion completarTarea() y hook AJAX movidos a app/Services/Task/TaskCrudService.php
// La funcion completarTarea() y su hook ya no se encuentran en este archivo, fueron movidos a TaskCrudService.php.

// Refactor(Org): Funcion actualizarOrdenTareas() y hook AJAX movidos a app/Services/Task/TaskOrderingService.php
// Refactor(Org): Funciones helper (manejarSubtarea, esPadreUnaSubtarea, actualizarOrden, actualizarSesionEstado) movidas a app/Services/Task/TaskOrderingService.php

// Refactor(Org): Funcion cambiarPrioridad() y hook AJAX movidos a app/Services/Task/TaskCrudService.php

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

// Refactor(Org): Funcion ordenamientoTareasPorPrioridad() movida a app/Services/Task/TaskOrderingService.php

// Refactor(Org): Funcion actualizarOrdenTareasGrupo() y hook movidos a app/Services/Task/TaskOrderingService.php
// // La funcion actualizarOrdenTareasGrupo() y su hook ya no estan aqui.
