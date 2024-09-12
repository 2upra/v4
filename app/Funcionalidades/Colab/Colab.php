<?php

//Boton en todos los post
function botonColab($post_id, $colab) {
    return $colab ? "<div class='XFFPOX'><button class='ZYSVVV' data-post-id='$post_id'>{$GLOBALS['iconocolab']}</button></div>" : '';
}

// Función para manejar la colaboración
// Función para manejar la colaboración
function empezarColab() {
    if (!is_user_logged_in() || !isset($_POST['post_id'])) {
        guardarLog('No autorizado o sin ID de publicación');
        wp_send_json_error(['message' => 'No autorizado o sin ID de publicación']);
    }

    $post_id = intval($_POST['post_id']);
    guardarLog("Intentando buscar post con ID $post_id");
    $original_post = get_post($post_id);
    if (!$original_post) {
        guardarLog('Publicación no encontrada');
        wp_send_json_error(['message' => 'Publicación no encontrada']);
    }

    $current_user_id = get_current_user_id();
    if ($current_user_id === $original_post->post_author) {
        guardarLog('No puedes colaborar contigo mismo.');
        wp_send_json_error(['message' => 'No puedes colaborar contigo mismo.']);
    }

    $new_post_id = wp_insert_post([
        'post_title' => 'Colaboración iniciada en ' . get_the_title($post_id),
        'post_type' => 'colaboracion',
        'post_status' => 'pending',
        'meta_input' => [
            'colabPostOrigen' => $post_id,
            'colabAutor' => $original_post->post_author,
            'colabColaborador' => $current_user_id
        ],
    ]);

    if ($new_post_id) {
        guardarLog('Colaboración iniciada correctamente');
        wp_send_json([
            'success' => true,
            'message' => 'Colaboración iniciada correctamente'
        ]);
    } else {
        guardarLog('Error al crear la colaboración');
        wp_send_json_error(['message' => 'Error al crear la colaboración']);
    }

    wp_die();
}
add_action('wp_ajax_empezarColab', 'empezarColab');
