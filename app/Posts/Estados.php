<?php

function cambiarEstado($post_id, $new_status)
{
    $post = get_post($post_id);
    $post->post_status = $new_status;
    wp_update_post($post);
    return json_encode(['success' => true, 'new_status' => $new_status]);
}

// Función genérica para manejar las solicitudes AJAX
function manejarCambioEstadoPublicacion()
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
add_action('wp_ajax_toggle_post_status', 'manejarCambioEstadoPublicacion');
add_action('wp_ajax_reject_post', 'manejarCambioEstadoPublicacion');
add_action('wp_ajax_request_post_deletion', 'manejarCambioEstadoPublicacion');
add_action('wp_ajax_eliminarPostRs', 'manejarCambioEstadoPublicacion');


