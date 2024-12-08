<?

/*
const data = {
    comentario: document.getElementById('comentContent').value,
    imagenUrl: CimagenUrl,
    audioUrl: CaudioUrl,
    imagenId: CimagenId,
    audioId: CaudioId,
    postId: CpostId #post al que se le hace el oomentario

};

wordpress 

usa para adjuntar al post, la imagen y el audio no son obligatorios
adjuntarArchivo($newPostId, $fileUrl) 

la funcion va a crear un post type comentarios

confima los id con confirmarHashId al completarse el post 

el titulo va a hacer Usuario hace comentario en (titulo del post postId) (recortado)

el nombre de usuario obviamente es el nombre del usuario actual

toda la informacion la vas a guardar en la meta del post, las imagenes o audio si existen (sus id de adjunto y sus id que llegan), no confundas las id de adjunto con las id que llegan que esas que llegan son id para confirmar el hash, asi que la meta de CimagenId y CaudioId serían hashIdImg y hashIdAudio 

no se si olvido algo pero santiiza y el autor del post es quien hace el comentario, importante guardar el postId en la meta  

el post al que se hace el comentario tiene que estar publicado

agrega un limite de comentarios a los usuarios de 3 comentarios por minuto como maximo

usa wp_send, y los error_log estos para errores importantes
*/




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
    update_post_meta($comentarioId, 'postId', $postId); // ID del post al que se comenta
    update_post_meta($comentarioId, 'hashIdImg', $imagenId); // ID para confirmar el hash de la imagen
    update_post_meta($comentarioId, 'hashIdAudio', $audioId); // ID para confirmar el hash del audio

    if ($attachment_image_id) {
        update_post_meta($comentarioId, 'imagenId', $attachment_image_id); // ID del adjunto de la imagen
    }

    if ($attachment_audio_id) {
        update_post_meta($comentarioId, 'audioId', $attachment_audio_id); // ID del adjunto del audio
    }

    // Confirmar hashes si existen
    if (!empty($imagenId)) {
        confirmarHashId($imagenId, $comentarioId, 'imagen');
    }
    if (!empty($audioId)) {
        confirmarHashId($audioId, $comentarioId, 'audio');
    }

    // Guardar ID del comentario en los metadatos del post
    add_post_meta($postId, 'comentario_id', $comentarioId, false); // Permite múltiples valores

    // Incrementar el contador de comentarios recientes y actualizar el transient
    $comentarios_recientes++;
    set_transient('comentarios_recientes_' . $user_id, $comentarios_recientes, 60); // Expira en 60 segundos

    wp_send_json_success(array('message' => 'Comentario creado con éxito.', 'post_id' => $comentarioId));
}
