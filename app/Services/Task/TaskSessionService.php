<?php

// Refactor(Org): Funcion actualizarSesion() y hook AJAX movidos desde app/Content/Task/logicTareas.php

/**
 * Actualiza la sesión (campo meta 'sesion') de todas las tareas del usuario actual
 * que coincidan con un valor original.
 *
 * Se utiliza a través de AJAX.
 */
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
        // Asumiendo que guardarLog() está disponible globalmente o será inyectado/requerido
        // Si no, esta llamada fallará. Considerar inyección de dependencias o un helper global.
        if (function_exists('guardarLog')) {
             guardarLog("actualizarSesion:" . $log);
        }
        wp_send_json_success('No se encontraron tareas para actualizar.');
    }

    foreach ($tareas as $tarea) {
        update_post_meta($tarea->ID, 'sesion', $valNue);
    }

    $log .= ", \n  Se actualizaron las sesiones de las tareas.";
    // Asumiendo que guardarLog() está disponible globalmente o será inyectado/requerido
    if (function_exists('guardarLog')) {
        guardarLog("actualizarSesion:" . $log);
    }
    wp_send_json_success();
}

add_action('wp_ajax_actualizarSesion', 'actualizarSesion');

