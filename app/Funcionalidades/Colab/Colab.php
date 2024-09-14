<?php

//Boton en todos los post
function botonColab($postId, $colab)
{
    return $colab ? "<div class='XFFPOX'><button class='ZYSVVV' data-post-id='$postId'>{$GLOBALS['iconocolab']}</button></div>" : '';
}

//verifica si file_id se procesa correctamente

function empezarColab()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
    }
    if (!isset($_POST['postId'])) {
        guardarLog('No se ha proporcionado el ID de la publicación');
        wp_send_json_error(['message' => 'No se ha proporcionado el ID de la publicación']);
    }

    $postId = intval($_POST['postId']);
    $file_id = intval($_POST['fileId']);
    $mensaje = sanitize_textarea_field($_POST['mensaje']);
    $fileUrl = isset($_POST['fileUrl']) ? esc_url_raw($_POST['fileUrl']) : '';

    guardarLog('postId: ' . $postId . ', fileId: ' . $file_id . ', mensaje: ' . $mensaje . ', fileUrl: ' . $fileUrl);

    $original_post = get_post($postId);
    if (!$original_post) {
        guardarLog('Publicación no encontrada');
        wp_send_json_error(['message' => 'Publicación no encontrada']);
    }

    $current_user_id = get_current_user_id();
    if ($current_user_id === $original_post->post_author) {
        wp_send_json_error(['message' => 'No puedes colaborar contigo mismo.']);
    }

    $existing_colabs_meta = get_post_meta($postId, 'colabs', true);
    if (!$existing_colabs_meta) {
        $existing_colabs_meta = [];
    }

    if (in_array($current_user_id, $existing_colabs_meta)) {
        wp_send_json_error(['message' => 'Ya existe una colaboración entre el autor y el colaborador para esta publicación']);
    }

    $author_name = get_the_author_meta('display_name', $original_post->post_author);
    $collaborator_name = get_the_author_meta('display_name', $current_user_id);

    $new_post_id = wp_insert_post([
        'post_title' => 'Colab entre ' . $author_name . ' y ' . $collaborator_name,
        'post_type' => 'colab',
        'post_status' => 'pending',
        'meta_input' => [
            'colabPostOrigen' => $postId,
            'colabAutor' => $original_post->post_author,
            'colabColaborador' => $current_user_id,
            'colabMensaje' => $mensaje,
            'colabFileUrl' => $fileUrl
        ],
    ]);

    if ($new_post_id) {
        guardarLog('Colaboración creada con ID: ' . $new_post_id);
        
        $existing_colabs_meta[] = $current_user_id;
        update_post_meta($postId, 'colabs', $existing_colabs_meta);

        // Confirmar archivo
        guardarLog('Confirmando archivo con ID: ' . $file_id);
        confirmarArchivo($file_id);
        guardarLog('Archivo ' . $file_id . ' confirmado');

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


function actualizarEstadoColab($postId, $post_after, $post_before)
{
    if ($post_after->post_type === 'colab') {
        $post_origen_id = get_post_meta($postId, 'colabPostOrigen', true);
        $colaborador_id = get_post_meta($postId, 'colabColaborador', true);

        if ($post_after->post_status !== 'publish' && $post_after->post_status !== 'pending') {
            $existing_colabs_meta = get_post_meta($post_origen_id, 'colabs', true);

            if (($key = array_search($colaborador_id, $existing_colabs_meta)) !== false) {
                unset($existing_colabs_meta[$key]);
                $result = update_post_meta($post_origen_id, 'colabs', $existing_colabs_meta);
                if (!$result) {
                    guardarLog("Error al actualizar los metadatos de colaboración para el post origen ID: $post_origen_id");
                }
            }
        }
    }
}
add_action('post_updated', 'actualizarEstadoColab', 10, 3);

function colab()
{

    ob_start()
    // Aqui tiene que aparecer los colas pendientes
    // Tambien tiene que aparecer los colabs en cursos
?>

<?php
    return ob_get_clean();
}
