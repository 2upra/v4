<?php
// Refactor(Org): Funcion crearTarea() y hook AJAX movidos desde app/Services/TaskService.php
// La función crearTarea() y su hook AJAX ya se encuentran en este archivo.

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

// Refactor: Moved completarTarea() and hook from TaskService.php
function completarTarea()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : 'pendiente';

    $tarea = get_post($id);

    if (empty($tarea) || $tarea->post_type != 'tarea') {
        wp_send_json_error('Tarea no encontrada.');
    }

    $tipo = get_post_meta($id, 'tipo', true);
    $log = "Funcion completarTarea(). \n ID: $id, tipo: $tipo. \n";

    if ($tipo == 'una vez') {
        $log .= "Se actualizo el estado de la tarea a $estado \n";
        update_post_meta($id, 'estado', $estado);
    } else if ($tipo == 'habito' || $tipo == 'habito rigido') {
        $fecha = get_post_meta($id, 'fecha', true);
        $fechaProxima = get_post_meta($id, 'fechaProxima', true);
        $frecuencia = intval(get_post_meta($id, 'frecuencia', true));
        $hoy = date('Y-m-d');


        $vecesCompletado = get_post_meta($id, 'vecesCompletado', true);
        if (empty($vecesCompletado)) {
            add_post_meta($id, 'vecesCompletado', 0, true);
            $vecesCompletado = 0;
        }
        $vecesCompletado++;

        // Manejar el registro de fechas de completado
        $fechasCompletado = get_post_meta($id, 'fechasCompletado', true);
        if (empty($fechasCompletado)) {
            $fechasCompletado = array();
        }

        // Agregar la fecha actual al array de fechas de completado
        $fechasCompletado[] = $hoy;

        update_post_meta($id, 'vecesCompletado', $vecesCompletado);
        update_post_meta($id, 'fechasCompletado', $fechasCompletado);

        if ($tipo == 'habito') {
            $nuevaFechaProxima = date('Y-m-d', strtotime($hoy . " + $frecuencia days"));
        } elseif ($tipo == 'habito rigido') {
            $nuevaFechaProxima = date('Y-m-d', strtotime($fechaProxima . " + $frecuencia days"));
        }

        $log .= "Se actualizo fechaProxima de $fechaProxima a $nuevaFechaProxima, y se agrego +1 a vecesCompletado (actualmente en $vecesCompletado), ademas se registraron las fechas de completado \n";
        update_post_meta($id, 'fechaProxima', $nuevaFechaProxima);
    }
    guardarLog($log);
    wp_send_json_success();
}

add_action('wp_ajax_completarTarea', 'completarTarea');

// Refactor(Org): Funcion cambiarPrioridad() y hook AJAX movidos desde app/Services/TaskService.php
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

// Refactor(Org): Funcion borrarTarea() y hook AJAX movidos desde app/Services/TaskService.php
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

// Refactor(Org): Funcion modificarTarea() y hook AJAX movidos desde app/Services/TaskService.php
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
        // Refactor: Llamada a la función crearTarea que ahora está en TaskCrudService
        // Asegúrate de que TaskCrudService.php esté incluido donde sea necesario.
        // $tareaId = crearTarea(); // Esta llamada fallará si el archivo no está incluido o la función no es global.
        // Por ahora, asumimos que está disponible globalmente o se manejará la inclusión.
        // Si crearTarea() ya no está disponible globalmente, esta lógica necesita ajustarse.
        // Dado que crearTarea() ahora está en TaskCrudService.php y usa wp_send_json_*, no devolverá el ID directamente aquí.
        // La lógica original que dependía de crearTarea() devolviendo un ID necesita ser revisada.
        // Por ahora, comentamos la llamada directa y enviamos un error indicando que la creación debe manejarse por separado.
        wp_send_json_error('La creación de nuevas tareas debe usar la acción AJAX crearTarea.');
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
