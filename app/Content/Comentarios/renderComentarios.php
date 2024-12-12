<? 


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


