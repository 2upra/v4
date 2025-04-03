<?php

# Permite la descarga de un post.
function permitirDescarga($idPost)
{
    update_post_meta($idPost, 'paraDescarga', true);
    return wp_send_json_success(['message' => 'Descarga permitida']);
}

# Comprueba el número de colaboraciones publicadas por un usuario.
function comprobarColaboracionesUsuario($idUsuario)
{
    $args = [
        'author'         => $idUsuario,
        'post_status'    => 'publish',
        'post_type'      => 'colab',
        'posts_per_page' => -1,
    ];

    $query = new WP_Query($args);
    return $query->found_posts;
}

# Cambia el estado de un post.
function cambiarEstado($idPost, $nuevoEstado)
{
    $post = get_post($idPost);
    if (!$post) {
       return wp_send_json_error(['message' => 'Post no encontrado']);
    }
    
    $post->post_status = $nuevoEstado;
    $resultado = wp_update_post($post);

    if (is_wp_error($resultado)) {
       return wp_send_json_error(['message' => 'Error al actualizar el post', 'error' => $resultado->get_error_message()]);
    }

    return wp_send_json_success(['new_status' => $nuevoEstado]);
}

# Maneja los cambios de estado de los posts a través de AJAX.
function cambioDeEstado()
{
    if (!isset($_POST['post_id'])) {
        return wp_send_json_error(['message' => 'Falta el ID del post']);
    }

    $idPost = intval($_POST['post_id']);  //Sanitizar y asegurar que es un entero
    $accion = sanitize_key($_POST['action']); //Sanitizar la acción
    $idUsuarioActual = get_current_user_id();

    if ($accion === 'aceptarcolab') {
        $colaboracionesPublicadas = comprobarColaboracionesUsuario($idUsuarioActual);

        if ($colaboracionesPublicadas >= 3) {
            return wp_send_json_error(['message' => 'Ya tienes 3 colaboraciones en curso. Debes finalizar una para aceptar otra.']);
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
        wp_send_json(permitirDescarga($idPost));
    } elseif (isset($estados[$accion])) {
        $nuevoEstado = $estados[$accion];
        wp_send_json(cambiarEstado($idPost, $nuevoEstado));
    } else {
       return wp_send_json_error(['message' => 'Acción inválida']);
    }

    wp_die();
}

# Verifica un post por un administrador.
function verificarPost()
{
    if (!isset($_POST['post_id'])) {
        return wp_send_json_error(['message' => 'Falta el ID del post']);
    }

    $idPost = intval($_POST['post_id']); //Sanitizar
    $usuarioActual = wp_get_current_user();

    if (!user_can($usuarioActual, 'administrator')) {
        return wp_send_json_error(['message' => 'No tienes permisos para verificar este post']);
    }

    //Escapar el valor antes de usarlo en la base de datos
    update_post_meta($idPost, 'Verificado', true);

    return wp_send_json_success(['message' => 'Post verificado correctamente']);
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