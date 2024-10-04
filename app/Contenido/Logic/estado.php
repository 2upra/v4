<?php

function cambiarEstado($post_id, $new_status)
{
    $post = get_post($post_id);
    $post->post_status = $new_status;
    wp_update_post($post);
    return json_encode(['success' => true, 'new_status' => $new_status]);
}

// Función genérica para manejar las solicitudes AJAX
function cambioDeEstado()
{
    // Validar que se haya recibido el post_id
    if (!isset($_POST['post_id'])) {
        echo json_encode(['success' => false, 'message' => 'Post ID is missing']);
        wp_die();
    }

    $post_id = $_POST['post_id'];
    $action = $_POST['action'];
    $estados = [
        'toggle_post_status' => ($_POST['current_status'] == 'pending') ? 'publish' : 'pending',
        'reject_post' => 'rejected',
        'request_post_deletion' => 'pending_deletion',
        'eliminarPostRs' => 'pending_deletion',
        'rechazarcolab' => 'pending_deletiom',
        'aceptarcolab' => 'publish',
    ];
    if (isset($estados[$action])) {
        $new_status = $estados[$action];
        echo cambiarEstado($post_id, $new_status);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

    wp_die();
}

// Registrar las acciones AJAX
add_action('wp_ajax_aceptarcolab', 'cambioDeEstado');
add_action('wp_ajax_rechazarcolab', 'cambioDeEstado');
add_action('wp_ajax_toggle_post_status', 'cambioDeEstado');
add_action('wp_ajax_reject_post', 'cambioDeEstado');
add_action('wp_ajax_request_post_deletion', 'cambioDeEstado');
add_action('wp_ajax_eliminarPostRs', 'cambioDeEstado');

function cambiarDescripcion()
{
    // Verificar si el usuario está logeado
    if (!is_user_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'No estás autorizado']);
        wp_die();
    }

    // Obtener información del usuario actual
    $current_user = wp_get_current_user();

    // Sanitizar los datos recibidos de la solicitud
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

    // Verificar si se recibió un ID de post válido
    if ($post_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de post no válido']);
        wp_die();
    }

    // Obtener el post y verificar si existe
    $post = get_post($post_id);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'El post no existe']);
        wp_die();
    }

    // Verificar si el usuario es el autor del post o es administrador
    if ($post->post_author != $current_user->ID && !current_user_can('administrator')) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este post']);
        wp_die();
    }

    // Actualizar la descripción del post si todo es correcto
    $post->post_content = wp_kses_post($descripcion); // Sanitizar el contenido del post
    wp_update_post($post);

    echo json_encode(['success' => true]);
    wp_die();
}

add_action('wp_ajax_cambiar_descripcion', 'cambiarDescripcion');
