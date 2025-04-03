<?php
// Refactor(Org): Movido desde app/Content/Logic/estado.php
// Contiene funciones y hooks AJAX para gestionar el estado de los posts.

function permitirDescarga($post_id)
{
    update_post_meta($post_id, 'paraDescarga', true);
    return json_encode(['success' => true, 'message' => 'Descarga permitida']);
}

function comprobarColabsUsuario($user_id)
{
    // Query para obtener las colaboraciones publicadas del usuario
    $args = [
        'author'         => $user_id,
        'post_status'    => 'publish',
        'post_type'      => 'colab',
        'posts_per_page' => -1,
    ];

    $query = new WP_Query($args);
    return $query->found_posts;
}

function cambiarEstado($post_id, $new_status)
{
    $post = get_post($post_id);
    $post->post_status = $new_status;
    wp_update_post($post);
    return json_encode(['success' => true, 'new_status' => $new_status]);
}

function cambioDeEstado()
{
    if (!isset($_POST['post_id'])) {
        echo json_encode(['success' => false, 'message' => 'Post ID is missing']);
        wp_die();
    }

    $post_id = $_POST['post_id'];
    $action = $_POST['action'];
    $current_user_id = get_current_user_id();

    // Si la acción es aceptar colaboración, comprobar el número de colabs publicadas
    if ($action === 'aceptarcolab') {
        $colabsPublicadas = comprobarColabsUsuario($current_user_id);

        if ($colabsPublicadas >= 3) {
            echo json_encode(['success' => false, 'message' => 'Ya tienes 3 colaboraciones en curso. Debes finalizar una para aceptar otra.']);
            wp_die();
        }
    }

    $estados = [
        'toggle_post_status'    => ($_POST['current_status'] == 'pending') ? 'publish' : 'pending',
        'reject_post'           => 'rejected',
        'request_post_deletion' => 'pending_deletion',
        'eliminarPostRs'        => 'pending_deletion',
        'rechazarcolab'         => 'pending_deletion',
        'aceptarcolab'          => 'publish',
    ];

    if ($action === 'permitirDescarga') {
        echo permitirDescarga($post_id);
    } elseif (isset($estados[$action])) {
        $new_status = $estados[$action];
        echo cambiarEstado($post_id, $new_status);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

    wp_die();
}

function verificarPost()
{
    if (!isset($_POST['post_id'])) {
        echo json_encode(['success' => false, 'message' => 'Post ID is missing']);
        wp_die();
    }

    $post_id = $_POST['post_id'];
    $current_user = wp_get_current_user();

    // Verificar si el usuario es administrador
    if (!user_can($current_user, 'administrator')) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para verificar este post']);
        wp_die();
    }

    // Actualizar el meta 'Verificado' a true
    update_post_meta($post_id, 'Verificado', true);

    echo json_encode(['success' => true, 'message' => 'Post verificado correctamente']);
    wp_die();
}



add_action('wp_ajax_verificarPost', 'verificarPost');
add_action('wp_ajax_permitirDescarga', 'cambioDeEstado');
add_action('wp_ajax_aceptarcolab', 'cambioDeEstado');
add_action('wp_ajax_rechazarcolab', 'cambioDeEstado');
add_action('wp_ajax_toggle_post_status', 'cambioDeEstado');
add_action('wp_ajax_reject_post', 'cambioDeEstado');
add_action('wp_ajax_request_post_deletion', 'cambioDeEstado');
add_action('wp_ajax_eliminarPostRs', 'cambioDeEstado');
