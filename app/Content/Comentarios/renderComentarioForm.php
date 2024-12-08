<?

function comentariosForm()
{
    ob_start();
    $user = wp_get_current_user();
    $nombreUsuario = $user->display_name;
    $urlImagenperfil = imagenPerfil($user->ID);
?>
    <div class="bloque anadircomentario" id="rsComentario" style="display: none;">

        <div class="W8DK25">
            <img id="perfil-imagen" src="<? echo esc_url($urlImagenperfil); ?>" alt="Perfil"
                style="max-width: 35px; max-height: 35px; border-radius: 50%;">
            <p><? echo $nombreUsuario ?></p>
        </div>

        <div>
            <textarea id="comentContent" name="comentContent" rows="1" required placeholder="Escribe tu comentario"></textarea>
        </div>

        <div class="previevsComent" id="previevsComent" style="display: none;">
            <div class="previewAreaArchivos pimagen" id="pcomentImagen" style="display: none;">
                <label></label>
            </div>
            <div class="previewAreaArchivos paudio" id="pcomentAudio" style="display: none;">
                <label></label>
            </div>
        </div>

        <div class="botonesForm R0A915">
            <button class="botonicono borde" id="audioComent"><? echo $GLOBALS['subiraudio']; ?></button>

            <button class="botonicono borde" id="imagenComent"><? echo $GLOBALS['subirimagen']; ?></button>

            <button class="botonicono borde" id="ArchivoComent" style="display: none;"><? echo $GLOBALS['subirarchivo']; ?></button>

            <button class="borde" id="enviarComent">Publicar</button>
        </div>

    </div>
    <?
    return ob_get_clean();
}



//solo tiene que mostrar los comentarios en comentarios_ids, pero sin importar el postId que reciba muestra todos comentarios, que estoy haciendo mal
function renderComentarios()
{
    $postId = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $userId = get_current_user_id();
    $comentariosPorPagina = 12;
    $offset = ($page - 1) * $comentariosPorPagina;
    $comentarios_ids = get_post_meta($postId, 'comentarios_ids', true);

    if (empty($comentarios_ids)) {
        // No hay comentarios asociados a este post, puedes mostrar un mensaje o simplemente no mostrar nada.
        echo '<p class="sinnotifi">No hay comentarios para este post</p>';
        return;
    }

    $args = array(
        'post_type' => 'comentarios',
        'post_status' => 'publish',
        'posts_per_page' => $comentariosPorPagina,
        'offset' => $offset,
        'post__in' => $comentarios_ids,
        'orderby' => 'post__in', // Ordenar los resultados en el mismo orden que el array de IDs.
    );

    $query = new WP_Query($args);


    ob_start();
    echo '<ul class="lista-comentarios">';
    if ($query->have_posts()) {
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
        //echo '0';
    }
    wp_reset_postdata();

    // Obtén el contenido del buffer y límpialo
    $output = ob_get_clean();

    // Construye la respuesta
    $response = array();
    if (trim($output) === '0' || empty($output)) {
        $response['noComentarios'] = true;
        $response['html'] = '<p class="sinnotifi">No hay comentarios para este post</p>';
    } else {
        $response['noComentarios'] = false;
        $response['html'] = $output;
    }

    // Establece el encabezado de tipo de contenido como JSON
    header('Content-Type: application/json');
    
    // Devuelve la respuesta como JSON
    echo json_encode($response);

    // Finaliza la ejecución del script
    wp_die();
}

add_action('wp_ajax_renderComentarios', 'renderComentarios');
