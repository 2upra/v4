<?php

// Refactor(Org): Funciones formTarea() y formTareaEstilo() movidas a app/View/Components/TaskForm.php

#Los selectores sImportancia y sTipo ya abren los submenu que son los A1806241, al ejecutarse el script tiene que remplazar el texto ejemplo y poner el primer valor de los botones, esta info se guarda en variables globales, y si el usuario da click a otro boton, se cambia, dame esa parte del script en una funcion, js vanilla 

// Refactor(Org): Funcion borrarTarea() y hook AJAX movidos a app/Services/TaskService.php

// Refactor(Org): Funcion modificarTarea() y hook AJAX movidos a app/Services/TaskService.php

// Refactor(Org): Funcion crearTarea() y hook AJAX movidos a app/Services/TaskService.php
// La funcion crearTarea() y su hook AJAX ya no estaban presentes en este archivo.

// Refactor(Org): Funcion completarTarea() y hook AJAX movidos a app/Services/TaskService.php


// Refactor(Org): Funcion archivarTarea() y hook AJAX movidos a app/Services/TaskService.php


// Refactor(Org): Funcion actualizarOrdenTareas() y hook AJAX movidos a app/Services/TaskService.php


// Refactor(Org): Funcion cambiarPrioridad() y hook AJAX movidos a app/Services/TaskService.php


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
