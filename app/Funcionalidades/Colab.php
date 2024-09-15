<?php

//Boton en todos los post
function botonColab($postId, $colab)
{
    return $colab ? "<div class='XFFPOX'><button class='ZYSVVV' data-post-id='$postId'>{$GLOBALS['iconocolab']}</button></div>" : '';
}

//verifica si fileId se procesa correctamente

function empezarColab() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No autorizado. Debes estar logueado']);
    }

    $postId = isset($_POST['postId']) ? intval($_POST['postId']) : null;
    $fileId = isset($_POST['fileId']) ? intval($_POST['fileId']) : null;
    $mensaje = isset($_POST['mensaje']) ? sanitize_textarea_field($_POST['mensaje']) : '';
    $fileUrl = isset($_POST['fileUrl']) ? esc_url_raw($_POST['fileUrl']) : '';

    if (!$postId) {
        guardarLog('No se ha proporcionado el ID de la publicación');
        wp_send_json_error(['message' => 'No se ha proporcionado el ID de la publicación']);
    }

    $original_post = get_post($postId);
    if (!$original_post) {
        guardarLog('Publicación no encontrada');
        wp_send_json_error(['message' => 'Publicación no encontrada']);
    }

    $current_user_id = get_current_user_id();
    if ($current_user_id === $original_post->post_author) {
        wp_send_json_error(['message' => 'No puedes colaborar contigo mismo.']);
    }

    $existing_colabs = get_post_meta($postId, 'colabs', true) ?: [];
    if (in_array($current_user_id, $existing_colabs)) {
        wp_send_json_error(['message' => 'Ya existe una colaboración existente para esta publicación']);
    }

    $author_name = get_the_author_meta('display_name', $original_post->post_author);
    $collaborator_name = get_the_author_meta('display_name', $current_user_id);

    $newPostId = wp_insert_post([
        'post_title' => "Colab entre $author_name y $collaborator_name",
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

    if ($newPostId) {
        guardarLog("Colaboración creada con ID: $newPostId");

        $existing_colabs[] = $current_user_id;
        update_post_meta($postId, 'colabs', $existing_colabs);

        if ($fileId) {
            confirmarArchivo($fileId);
            guardarLog("Archivo $fileId confirmado");
        }

        wp_send_json_success(['message' => 'Colaboración iniciada correctamente']);
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
