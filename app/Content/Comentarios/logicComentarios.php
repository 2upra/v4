<?




add_action('wp_ajax_procesarComentario', 'procesarComentario'); // Para usuarios logueados

function procesarComentario()
{
    $user_id = get_current_user_id();
    $comentarios_recientes = get_transient('comentarios_recientes_' . $user_id);

    if ($comentarios_recientes === false) {
        $comentarios_recientes = 0;
    }

    if ($comentarios_recientes >= 3) {
        wp_send_json_error(array('message' => 'Has alcanzado el límite de comentarios por minuto. Por favor, espera un momento.'));
        return;
    }

    // Obtener y sanitizar datos
    $comentario = isset($_POST['comentario']) ? sanitize_textarea_field($_POST['comentario']) : '';
    $imagenUrl = isset($_POST['imagenUrl']) ? esc_url_raw($_POST['imagenUrl']) : '';
    $audioUrl = isset($_POST['audioUrl']) ? esc_url_raw($_POST['audioUrl']) : '';
    $imagenId = isset($_POST['imagenId']) ? sanitize_text_field($_POST['imagenId']) : ''; // Este es el hashIdImg
    $audioId = isset($_POST['audioId']) ? sanitize_text_field($_POST['audioId']) : ''; // Este es el hashIdAudio
    $postId = isset($_POST['postId']) ? intval($_POST['postId']) : 0;

    // Verificar datos obligatorios
    if (empty($comentario)) {
        wp_send_json_error(array('message' => 'El comentario no puede estar vacío.'));
        return;
    }

    if ($postId <= 0) {
        wp_send_json_error(array('message' => 'ID de publicación inválido.'));
        return;
    }

    // Verificar que el post al que se comenta exista y esté publicado
    $post_to_comment = get_post($postId);
    if (!$post_to_comment || $post_to_comment->post_status !== 'publish') {
        wp_send_json_error(array('message' => 'No se puede comentar en una publicación que no existe o no está publicada.'));
        return;
    }

    $post_title = get_the_title($postId);
    $post_title_short = wp_trim_words($post_title, 10, '...');
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;
    $comentario_title = sanitize_text_field($user_name . ' hace un comentario en ' . $post_title_short);

    // Crear el post del comentario
    $comentarioId = wp_insert_post(array(
        'post_title'    => $comentario_title,
        'post_content'  => $comentario,
        'post_status'   => 'publish',
        'post_type'     => 'comentarios', // Asegúrate de que este post type esté registrado
        'post_author'   => $user_id,
    ));

    if (is_wp_error($comentarioId)) {
        error_log('Error al crear el comentario: ' . $comentarioId->get_error_message());
        wp_send_json_error(array('message' => 'Error al crear el comentario.'));
        return;
    }

    // Adjuntar archivos si existen
    $attachment_image_id = null;
    $attachment_audio_id = null;

    if (!empty($imagenUrl)) {
        $attachment_image_id = adjuntarArchivo($comentarioId, $imagenUrl);
    }

    if (!empty($audioUrl)) {
        $attachment_audio_id = adjuntarArchivo($comentarioId, $audioUrl);
    }

    // Actualizar metadatos del comentario
    update_post_meta($comentarioId, 'postId', $postId);
    update_post_meta($comentarioId, 'hashIdImg', $imagenId); 
    update_post_meta($comentarioId, 'hashIdAudio', $audioId); 

    if ($attachment_image_id) {
        update_post_meta($comentarioId, 'imagenId', $attachment_image_id);
    }

    if ($attachment_audio_id) {
        update_post_meta($comentarioId, 'audioId', $attachment_audio_id); // ID del adjunto del audio
    }

    if (!empty($imagenId)) {
        confirmarHashId($imagenId, $comentarioId, 'imagen');
    }
    if (!empty($audioId)) {
        confirmarHashId($audioId, $comentarioId, 'audio');
    }

    $comentarios_ids = get_post_meta($postId, 'comentarios_ids', true);
    if (!is_array($comentarios_ids)) {
        $comentarios_ids = array(); 
    }

    $comentarios_ids[] = $comentarioId; // Añadir el nuevo ID
    update_post_meta($postId, 'comentarios_ids', $comentarios_ids); // Guardar el array actualizado

    // **Crear la notificación**
    $usuarioReceptor = $post_to_comment->post_author; // Autor del post original
    $contenido = $user_name . " ha comentado tu post."; // Contenido de la notificación
    $postIdRelacionado = $postId; // Post relacionado
    $Titulo = "Nuevo comentario"; // Título de la notificación
    $url = null; // URL (en este caso, null)

    crearNotificacion($usuarioReceptor, $contenido, false, $postIdRelacionado, $Titulo, $url);

    // Incrementar el contador de comentarios recientes y actualizar el transient
    $comentarios_recientes++;
    set_transient('comentarios_recientes_' . $user_id, $comentarios_recientes, 60); // Expira en 60 segundos

    wp_send_json_success(array('message' => 'Comentario creado con éxito.', 'post_id' => $comentarioId));
}