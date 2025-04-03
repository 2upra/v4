<?php
// Refactor(Org): Funcion crearTarea() y hook AJAX movidos desde app/Content/Task/logicTareas.php

//aqui necesito que cuando llega un padre, haga lo que hace crearSubtarea
function crearTarea()
{
    $log = '';
    if (!current_user_can('edit_posts')) {
        $log = 'No tienes permisos.';
        guardarLog("crearTarea: $log");
        wp_send_json_error($log);
    }

    $tit = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
    $imp = isset($_POST['importancia']) ? sanitize_text_field($_POST['importancia']) : 'media';
    $tip = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'una vez';
    $frec = isset($_POST['frecuencia']) ? (int) sanitize_text_field($_POST['frecuencia']) : 1;
    $ses = isset($_POST['sesion']) ? sanitize_text_field($_POST['sesion']) : '';
    $est = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : 'pendiente';
    $pad = isset($_POST['padre']) ? (int) sanitize_text_field($_POST['padre']) : 0;

    if (empty($tit)) {
        $log = 'Título vacío.';
        guardarLog("crearTarea: $log");
        wp_send_json_error($log);
    }

    $impnum = 0;
    if ($imp === 'importante') {
        $impnum = 4;
    } elseif ($imp === 'alta') {
        $impnum = 3;
    } elseif ($imp === 'media') {
        $impnum = 2;
    } elseif ($imp === 'baja') {
        $impnum = 1;
    }

    $tipnum = 0;
    if ($tip === 'una vez') {
        $tipnum = 1;
    } elseif ($tip === 'habito') {
        $tipnum = 2;
    } elseif ($tip === 'meta') {
        $tipnum = 3;
    } elseif ($tip === 'habito rigido') {
        $tipnum = 4;
    }

    $fec = date('Y-m-d');
    $fecprox = date('Y-m-d', strtotime("+{$frec} days"));

    $args = array(
        'post_title' => $tit,
        'post_type' => 'tarea',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'meta_input' => array(
            'importancia' => $imp,
            'impnum' => $impnum,
            'tipo' => $tip,
            'tipnum' => $tipnum,
            'estado' => $est,
            'frecuencia' => $frec,
            'fecha' => $fec,
            'fechaProxima' => $fecprox,
            'sesion' => $ses
        ),
    );

    // Si se recibe un padre, se crea como subtarea
    if ($pad) {
        $tareaPadre = get_post($pad);
        if (empty($tareaPadre) || $tareaPadre->post_type != 'tarea') {
            $msg = 'Tarea padre no encontrada.';
            guardarLog("crearTarea: $msg");
            wp_send_json_error($msg);
        }
        $args['post_parent'] = $pad;
    }

    $tareaId = wp_insert_post($args);

    if (is_wp_error($tareaId)) {
        $msg = $tareaId->get_error_message();
        $log .= "Error al crear tarea: $msg";
        guardarLog("crearTarea: $log");
        wp_send_json_error($msg);
    }

    // Si es una subtarea, se actualiza el meta 'subtarea'
    if ($pad) {
        update_post_meta($tareaId, 'subtarea', $pad);
        $log .= "Subtarea creada con id $tareaId, tarea padre $pad, sesion $ses, estado $est";
    } else {
        $log .= "Tarea creada con id $tareaId, sesion $ses, estado $est";
    }

    guardarLog("crearTarea: $log");
    wp_send_json_success(array('tareaId' => $tareaId));
}

add_action('wp_ajax_crearTarea', 'crearTarea');
