<?php

/**
 * Permite la descarga de un post.
 */
function permitirDescarga($idDelPost)
{
    update_post_meta($idDelPost, 'paraDescarga', true);
    wp_send_json_success(['message' => 'Descarga permitida']);
}

/**
 * Comprueba el número de colaboraciones publicadas de un usuario.
 */
function comprobarColabsUsuario($idDelUsuario)
{
    $args = [
        'author'         => $idDelUsuario,
        'post_status'    => 'publish',
        'post_type'      => 'colab',
        'posts_per_page' => -1,
    ];

    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Cambia el estado de un post.
 */
function cambiarEstado($idDelPost, $nuevoEstado)
{
    $post = get_post($idDelPost);
    if (!$post) {
         wp_send_json_error(['message' => 'Post no encontrado.']);
    }
    $post->post_status = $nuevoEstado;
    $resultado = wp_update_post($post);

    if (is_wp_error($resultado)) {
         wp_send_json_error(['message' => 'Error al actualizar el post.', 'error' => $resultado->get_error_message()]);
    }
    
    wp_send_json_success(['new_status' => $nuevoEstado]);
}

/**
 * Gestiona los cambios de estado de los posts mediante AJAX.
 */
function cambioDeEstado()
{
    if (!isset($_POST['post_id'])) {
        wp_send_json_error(['message' => 'Falta el ID del post']);
    }

    $idDelPost = intval($_POST['post_id']); //Sanitizar y convertir a entero
    $accion = sanitize_text_field($_POST['action']); //Sanitizar
    $idDelUsuarioActual = get_current_user_id();

    if ($accion === 'aceptarcolab') {
        $colabsPublicadas = comprobarColabsUsuario($idDelUsuarioActual);

        if ($colabsPublicadas >= 3) {
            wp_send_json_error(['message' => 'Ya tienes 3 colaboraciones en curso. Debes finalizar una para aceptar otra.']);
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

    if ($accion === 'permitirDescarga') {
        permitirDescarga($idDelPost);
    } elseif (isset($estados[$accion])) {
        $nuevoEstado = $estados[$accion];
        cambiarEstado($idDelPost, $nuevoEstado);
    } else {
        wp_send_json_error(['message' => 'Acción inválida']);
    }

    wp_die();
}

/**
 * Verifica un post.
 */
function verificarPost()
{
    if (!isset($_POST['post_id'])) {
        wp_send_json_error(['message' => 'Falta el ID del post']);
    }

    $idDelPost = intval($_POST['post_id']); //Sanitizar y convertir a entero
    $usuarioActual = wp_get_current_user();

    if (!user_can($usuarioActual, 'administrator')) {
        wp_send_json_error(['message' => 'No tienes permisos para verificar este post']);
    }
    
    // Verificar que el post existe antes de actualizar metadatos.
    if (!get_post($idDelPost)) {
        wp_send_json_error(['message' => 'El post no existe.']);
    }

    update_post_meta($idDelPost, 'Verificado', true);

    wp_send_json_success(['message' => 'Post verificado correctamente']);
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