<?

function crearNota()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
    }
    $cont = isset($_POST['contenido']) ? sanitize_textarea_field($_POST['contenido']) : '';

    if (empty($cont)) {
        wp_send_json_error('Contenido vacío.');
    }

    $titulo = substr($cont, 0, 80);

    $args = array(
        'post_title' => $titulo,
        'post_content' => $cont,
        'post_type' => 'notas',
        'post_status' => 'publish',
        'post_author' => get_current_user_id()
    );
    $notaId = wp_insert_post($args);
    if (is_wp_error($notaId)) {
        $msg = $notaId->get_error_message();
        guardarLog("crearNota: Error $msg");
        wp_send_json_error($msg);
    }
    guardarLog("crearNota: Nota creada $notaId");
    wp_send_json_success(array('notaId' => $notaId));
}

add_action('wp_ajax_crearNota', 'crearNota');

function modificarNota()
{
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos.');
        return;
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $cont = isset($_POST['contenido']) ? sanitize_textarea_field($_POST['contenido']) : '';

    if (empty($cont)) {
        wp_send_json_error('Contenido vacío.');
        return;
    }
    if ($id <= 0) {
        wp_send_json_error('ID no válido.');
        return;
    }

    $nota = get_post($id);

    if (!$nota || $nota->post_type !== 'notas') {
        wp_send_json_error('La nota no existe.');
        return;
    }
    if (!current_user_can('edit_post', $id)) {
        wp_send_json_error('No tienes permisos para editar esta nota.');
        return;
    }

    $args = array(
        'ID' => $id,
        'post_content' => $cont,
    );

    $notaId = wp_update_post($args);

    if (is_wp_error($notaId)) {
        $msg = $notaId->get_error_message();
        guardarLog("modificarNota: Error $msg");
        wp_send_json_error($msg);
    }

    guardarLog("modificarNota: Nota $notaId modificada");
    wp_send_json_success();
}

add_action('wp_ajax_modificarNota', 'modificarNota');


function borrarLasNotas()
{
    if (isset($_POST['limpiar']) && $_POST['limpiar'] === 'true') {
        $usuarioActual = get_current_user_id();

        $args = array(
            'post_type'      => 'notas',
            'author'         => $usuarioActual,
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
add_action('wp_ajax_borrarLasNotas', 'borrarLasNotas');
