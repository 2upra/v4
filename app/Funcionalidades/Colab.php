<?php

//Boton en todos los post
function botonColab($postId, $colab)
{
    return $colab ? "<div class='XFFPOX'><button class='ZYSVVV' data-post-id='$postId'>{$GLOBALS['iconocolab']}</button></div>" : '';
}

/*

2024-09-25 22:09:35 - Resultado de wp_handle_upload: Array
(
    [file] => /var/www/wordpress/wp-content/uploads/2024/09/DRUM-FILL-11.wav
    [url] => https://2upra.com/wp-content/uploads/2024/09/DRUM-FILL-11.wav
    [type] => audio/wav
)
2024-09-25 22:09:35 - Carga exitosa. Hash guardado: 073f39259941ebdbfb32a677fc9182476db036dd10d046f5cf1fe6ac07c43fc6. URL del nuevo archivo: https://2upra.com/wp-content/uploads/2024/09/DRUM-FILL-11.wav
2024-09-25 22:09:39 - Colaboración creada con ID: 232055
2024-09-25 22:09:39 - No se encontró el archivo para adjuntar al post.
2024-09-25 22:12:37 - INICIO subidaArchivo
2024-09-25 22:12:37 - Hash recibido: 5152dc1c537df630bf409cae5be5d6e720ce4d540461c6fbc028fd06842b6d36
2024-09-25 22:12:37 - No se encontró un archivo existente con este hash o el archivo está pendiente.
2024-09-25 22:12:37 - Resultado de wp_handle_upload: Array
(
    [file] => /var/www/wordpress/wp-content/uploads/2024/09/DRUM-FILL-10.wav
    [url] => https://2upra.com/wp-content/uploads/2024/09/DRUM-FILL-10.wav
    [type] => audio/wav
)
2024-09-25 22:12:37 - Carga exitosa. Hash guardado: 5152dc1c537df630bf409cae5be5d6e720ce4d540461c6fbc028fd06842b6d36. URL del nuevo archivo: https://2upra.com/wp-content/uploads/2024/09/DRUM-FILL-10.wav
2024-09-25 22:12:39 - Colaboración creada con ID: 232056
2024-09-25 22:12:39 - No se encontró el archivo para adjuntar al post.

*/

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
            'colabMensaje' => $mensaje
        ],
    ]);

    if ($newPostId) {
        guardarLog("Colaboración creada con ID: $newPostId");

        $existing_colabs[] = $current_user_id;
        update_post_meta($postId, 'colabs', $existing_colabs);

        // Asociar el archivo desde el URL si ya existe
        if (!empty($fileUrl)) {
            $attached = handle_and_attach_file($newPostId, $fileUrl);
            if (!$attached) {
                wp_send_json_error(['message' => 'No se pudo adjuntar el archivo correctamente.']);
            }
        }

        if ($fileId) {
            confirmarHashId($fileId);
            guardarLog("Archivo $fileId confirmado");
        }

        wp_send_json_success(['message' => 'Colaboración iniciada correctamente']);
    } else {
        guardarLog('Error al crear la colaboración');
        wp_send_json_error(['message' => 'Error al crear la colaboración']);
    }

    wp_die();
}

function handle_and_attach_file($newPostId, $fileUrl) {
    global $wpdb;

    // Revisa si el archivo ya está registrado por la URL.
    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE guid = %s",
        $fileUrl
    ));

    if (!$attachment_id) {
        // Si no se encuentra el adjunto, intenta crear uno.
        $uploads_dir = wp_upload_dir();
        $file_path = str_replace($uploads_dir['baseurl'], $uploads_dir['basedir'], $fileUrl);

        if (file_exists($file_path)) {
            // Crea el adjunto
            $data = [
                'guid'           => $fileUrl,
                'post_mime_type' => mime_content_type($file_path),
                'post_title'     => wp_basename($file_path),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $attachment_id = wp_insert_attachment($data, $file_path, $newPostId);

            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $attach_data);
            }
        } else {
            guardarLog("Archivo físico no encontrado para adjuntar.");
            return false;
        }
    }

    if ($attachment_id) {
        // Asocia el adjunto al post, como un archivo destacado o cualquier meta deseada
        update_post_meta($newPostId, '_thumbnail_id', $attachment_id);
        guardarLog("Archivo adjuntado con ID: $attachment_id");
        return true;
    }

    return false;
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
    <div class="IBPDFF">
        <?php echo publicaciones(['post_type' => 'colab', 'filtro' => 'colab', 'posts' => 20]); ?>
    </div>
<?php
    return ob_get_clean();
}
