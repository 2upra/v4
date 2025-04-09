<?php

// Archivo creado para contener la lógica de negocio y manejo de datos para los comentarios.

class CommentService
{
    // TODO: Implementar métodos relacionados con la lógica de comentarios.
    // Por ejemplo: crearComentario, obtenerComentariosPorPost, eliminarComentario, etc.

    public function __construct()
    {
        // Inicialización del servicio, si es necesaria.
        // Por ejemplo, inyectar dependencias como el repositorio de comentarios.
    }

    // Ejemplo de método (a implementar)
    // public function getCommentsByPostId($postId)
    // {
    //     // Lógica para obtener comentarios
    // }
}

// Función procesarComentario() y hook AJAX movidos desde app/Content/Comentarios/logicComentarios.php
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
    $imagenId = isset($_POST['imagenId']) ? sanitize_text_field($_POST['imagenId']) : '';
    $audioId = isset($_POST['audioId']) ? sanitize_text_field($_POST['audioId']) : '';
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
        'post_type'     => 'comentarios',
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

    $post_url = get_permalink($postId);
    crearNotificacion($usuarioReceptor, $contenido, false, $postIdRelacionado, $Titulo, $post_url);

    // Incrementar el contador de comentarios recientes y actualizar el transient
    $comentarios_recientes++;
    set_transient('comentarios_recientes_' . $user_id, $comentarios_recientes, 60); // Expira en 60 segundos

    wp_send_json_success(array('message' => 'Comentario creado con éxito.', 'post_id' => $comentarioId));
}

// --- Inicio: Código movido desde app/Content/Comentarios/logicComentarios.php ---
/**
 * Redirige los accesos directos a las entradas de tipo 'comentarios'
 * a la entrada original a la que pertenecen.
 */
function redirigir_comentarios() {
    // Verifica si estamos en una página individual (singular)
    if (is_singular('comentarios')) {
        // Obtiene el ID del comentario actual
        $comment_id = get_the_ID();

        // Obtiene el ID del post original al que pertenece el comentario
        $post_id = get_post_meta($comment_id, 'postId', true);

        // Si se encuentra el ID del post original
        if ($post_id) {
            // Obtiene la URL del post original
            $post_url = get_permalink($post_id);

            // Realiza la redirección
            wp_redirect($post_url);
            exit; // Asegura que el script se detenga después de la redirección
        } else {
            // Si no se encuentra el post original, podrías redirigir a la página de inicio o mostrar un error.
            // Por ejemplo, redirigir a la página de inicio:
            wp_redirect(home_url());
            exit;
        }
    }
}
add_action('template_redirect', 'redirigir_comentarios');
// --- Fin: Código movido desde app/Content/Comentarios/logicComentarios.php ---

// Moved AJAX handler renderComentarios from app/Content/Comentarios/renderComentarios.php
function renderComentarios() {
    $postId = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $userId = get_current_user_id();
    $comentariosPorPagina = 12;
    $offset = ($page - 1) * $comentariosPorPagina;
    $comentarios_ids = get_post_meta($postId, 'comentarios_ids', true);

    // Inicializar la respuesta
    $response = array(
        'noComentarios' => false,
        'html' => ''
    );

    if (empty($comentarios_ids)) {
        // No hay comentarios asociados a este post
        $response['noComentarios'] = true;
        $response['html'] = '<p class="sinnotifi">No hay comentarios para este post</p>';
    } else {
        $args = array(
            'post_type' => 'comentarios',
            'post_status' => 'publish',
            'posts_per_page' => $comentariosPorPagina,
            'offset' => $offset,
            'post__in' => $comentarios_ids,
            'orderby' => 'post__in', // Ordenar los resultados en el mismo orden que el array de IDs.
        );

        $query = new WP_Query($args);

        ob_start(); // Iniciar el buffer de salida
        if ($query->have_posts()) {
            echo '<ul class="lista-comentarios">';
            while ($query->have_posts()) {
                $query->the_post();
                $comentarioId = get_the_ID();
                $autorComentarioId = get_the_author_meta('ID');
                $autorComentario = get_userdata($autorComentarioId); // Obtener el objeto de usuario.
                $nombreUsuario = $autorComentario->display_name; // Acceder a display_name.
                $contenidoComentario = get_the_content();
                $audio = get_post_meta($comentarioId, 'post_audio_lite', true);
                $imagenPortada = get_the_post_thumbnail_url($comentarioId, 'full');
                $imagenPortadaOptimizada = $imagenPortada ? img($imagenPortada) : ''; // Simplifica la condición y evita errores si img() no existe.
                $fechaPublicacion = get_the_date('Y-m-d H:i:s');
                $fechaRelativa = tiempoRelativo($fechaPublicacion);
                $avatar_optimizado = imagenPerfil($autorComentarioId);
                $audio_url = wp_get_attachment_url(get_post_meta($comentarioId, 'post_audio', true));
        ?>

                <li class="comentarioPost" id="comentario-<? echo $comentarioId ?>">
                    <div class="avatarComentario">
                        <img class="avatar" src="<? echo esc_url($avatar_optimizado); ?>" alt="Avatar del emisor">
                        <div class="spaceComentario">
                            <div class="MGDEOP">
                                <p><? echo $nombreUsuario ?> </p>
                                <span class="fecha"><? echo $fechaRelativa ?></span>
                                <? echo opcionesComentarios($comentarioId, $autorComentarioId) ?>
                            </div>
                            <div class="contenidoComentario">
                                <div class="texto"><? echo $contenidoComentario ?></div>
                                <? if ($imagenPortadaOptimizada): ?>
                                    <div class="imagenComentario">
                                        <img src="<? echo $imagenPortadaOptimizada ?>" alt="Imagen de portada" />
                                    </div>

                                <? endif; ?>
                                <? if (!empty($audio)) : ?>
                                    <div class="audioComentario">
                                        <? wave($audio_url, $audio, $comentarioId); ?>
                                    </div>
                                <? endif; ?>
                                <div class="controlComentario">
                                    <? echo renderPostControls($comentarioId, '', $audio); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </li>
    <?
            }
            echo '</ul>';
        } else {
            $response['noComentarios'] = true;
            $response['html'] = '<p class="sinnotifi">No hay comentarios para este post</p>';
        }
        wp_reset_postdata();

        $response['html'] = ob_get_clean(); // Obtener el contenido del buffer y limpiarlo
        $response['noComentarios'] = !$query->have_posts(); // Actualizar noComentarios basado en si hay posts
    }

    // Establecer el encabezado de tipo de contenido como JSON
    header('Content-Type: application/json');

    // Devolver la respuesta como JSON
    echo json_encode($response);

    // Finalizar la ejecución del script
    wp_die();
}

add_action('wp_ajax_renderComentarios', 'renderComentarios');

?>
