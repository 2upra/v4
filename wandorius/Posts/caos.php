<?php

// Función genérica para cambiar el estado de una publicación
function cambiarEstado($post_id, $new_status)
{
    $post = get_post($post_id);
    $post->post_status = $new_status;
    wp_update_post($post);
    return json_encode(['success' => true, 'new_status' => $new_status]);
}

// AJAX para alternar entre pendiente y publicado
add_action('wp_ajax_toggle_post_status', 'toggle_post_status_callback');
function toggle_post_status_callback()
{
    $post_id = $_POST['post_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 'pending') ? 'publish' : 'pending';
    echo cambiarEstado($post_id, $new_status);
    wp_die();
}

// AJAX para cambiar a rechazado
add_action('wp_ajax_reject_post', 'reject_post_callback');
function reject_post_callback()
{
    echo cambiarEstado($_POST['post_id'], 'rejected');
    wp_die();
}

// AJAX para cambiar a pendiente de eliminación
add_action('wp_ajax_request_post_deletion', 'request_post_deletion_callback');
function request_post_deletion_callback()
{
    echo cambiarEstado($_POST['post_id'], 'pending_deletion');
    wp_die();
}

// AJAX para elminar post RS
add_action('wp_ajax_eliminarPostRs', 'eliminarPostRs_callback');
function eliminarPostRs_callback()
{
    echo cambiarEstado($_POST['post_id'], 'pending_deletion');
    wp_die();
}

function caos()
{
    wp_enqueue_script('caos', get_template_directory_uri() . '/js/caos.js', array('jquery'), '2.1.12', true);
    wp_localize_script(
        'caos',
        'ajax_params',
        array(
            'ajax_url' => admin_url('admin-ajax.php')
        )
    );
}
add_action('wp_enqueue_scripts', 'caos');
