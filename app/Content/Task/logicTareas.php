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


// Refactor(Org): Funcion cambiarFrecuencia() y hook AJAX movidos a app/Services/TaskService.php


// Refactor(Org): Funcion crearSubtarea() y hook AJAX movidos a app/Services/TaskService.php


// Refactor(Org): Funcion borrarTareasCompletadas() y hook AJAX movidos a app/Services/TaskService.php


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
