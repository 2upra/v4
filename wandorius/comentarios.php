<?php
//COMENTAR IMAGENES Y AUDIOS
/*
add_action('comment_post', 'handle_comment_upload', 10, 2);
function handle_comment_upload($comment_ID, $comment_approved) {

    if (isset($_FILES['comment_image']) && $_FILES['comment_image']['error'] == UPLOAD_ERR_OK) {
        $uploaded_image = media_handle_upload('comment_image', 0);
        if (is_wp_error($uploaded_image)) {
            wp_die('Error al subir imagen: ' . $uploaded_image->get_error_message());
        } else {
            add_comment_meta($comment_ID, 'comment_image', $uploaded_image);
        }
    }

    if (isset($_FILES['comment_audio']) && $_FILES['comment_audio']['error'] == UPLOAD_ERR_OK) {
        $uploaded_audio = media_handle_upload('comment_audio', 0);
        if (is_wp_error($uploaded_audio)) {
            wp_die('Error al subir audio: ' . $uploaded_audio->get_error_message());
        } else {
            add_comment_meta($comment_ID, 'comment_audio', $uploaded_audio);
        }
    }
}
*/

//COMENTARIOS
function avada_comment($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    $is_own_comment = (get_current_user_id() == $comment->user_id) ? 'own-comment' : 'other-comment'; 

    ?>
    <li <?php comment_class($is_own_comment); ?> id="li-comment-<?php comment_ID() ?>">
        <div id="comment-<?php comment_ID(); ?>" class="comment-body">
            <div class="comment-avatar">
                <?php echo get_avatar($comment, $size = '30'); ?>
            </div>
            
            <div class="comment-content">
                <div class="comment-author vcard">
                    <?php
                    $author_link = get_comment_author_link();
                    $author_link_styled = preg_replace('/<a /', '<a style="color: white; font-style: normal;" ', $author_link);
                    printf('<cite class="fn">%s</cite>', $author_link_styled);
                    ?>
                </div>

                <div class="comment-text">
                    <?php comment_text(); ?>
                    <?php
                    $image_id = get_comment_meta($comment->comment_ID, 'comment_image', true);
                    if ($image_id) {
                        $image_url = wp_get_attachment_url($image_id);
                        // Añadimos un enlace de descarga para la imagen
                        echo "<div class='comment-attachment'><img src='" . esc_url($image_url) . "' alt='Imagen adjunta' style='max-width:100%; height:auto;'></div>";
                        echo "<a href='" . esc_url($image_url) . "' download='ImagenAdjunta'>Descargar Imagen</a>";
                    }

                    $audio_id = get_comment_meta($comment->comment_ID, 'comment_audio', true);
                    if ($audio_id) {
                        $audio_url = wp_get_attachment_url($audio_id);
                        // Añadimos un enlace de descarga para el audio
                        echo "<div id='waveform-" . get_the_ID() . "' class='waveform-container-venta' data-audio-url='" . esc_url($audio_url) . "'>
                        <div class='waveform-background' style='height: 1px;width: 1px;display: none;'></div>
                        <div class='waveform-message'></div>
                        <div class='waveform-loading' style='display: none;'>Cargando...</div>
                        </div>";
                        echo "<a href='" . esc_url($audio_url) . "' download='AudioAdjunto'>Descargar Audio</a>";
                    }

                    ?>
                </div>
            </div> 
        </div>
        <div class="comment-details">
            <div class="comment-meta commentmetadata">
                <?php if (current_user_can('edit_comment', $comment->comment_ID)) {
                    echo '<a href="#" class="edit-comment-button" data-comment-id="' . $comment->comment_ID . '" style="color: grey;">Editar</a>';
                }
                if (get_current_user_id() == $comment->user_id || current_user_can('moderate_comments')) {
                    echo '<a href="#" class="delete-comment-button" data-comment_id="' . get_comment_ID() . '" data-nonce="' . wp_create_nonce('delete_comment_nonce') . '">Eliminar</a>';
                }
                ?>
            </div>
        </div>
    </li>
    <?php
}

function auto_approve_comments($commentdata) {
    $commentdata['comment_approved'] = 1;
    return $commentdata;
}
add_filter('preprocess_comment', 'auto_approve_comments');













add_action('wp_ajax_mytheme_handle_comment', 'mytheme_handle_comment_callback');
add_action('wp_ajax_nopriv_mytheme_handle_comment', 'mytheme_handle_comment_callback');

function mytheme_handle_comment_callback() {
    $comment_post_ID = isset($_POST['comment_post_ID']) ? intval($_POST['comment_post_ID']) : null;
    $comment_parent = isset($_POST['comment_parent']) ? intval($_POST['comment_parent']) : 0;
    if (!$comment_post_ID) {
        wp_send_json_error(array('error' => 'ID del post no válido.'));
        wp_die();
    }
    $user = wp_get_current_user();
    $comment_author = $user->exists() ? $user->display_name : 'Anónimo';

    $comment_data = array(
        'comment_post_ID'      => $comment_post_ID,
        'comment_author'       => $comment_author,
        'comment_content'      => isset($_POST['comment']) ? trim($_POST['comment']) : '',
        'comment_type'         => 'comment', // Pon 'comment' para hacerlo explícito que es un comentario.
        'comment_parent'       => $comment_parent,
        'user_id'              => $user->ID,
        'comment_author_IP'    => $_SERVER['REMOTE_ADDR'],
        'comment_agent'        => $_SERVER['HTTP_USER_AGENT'],
        'comment_date'         => current_time('mysql'),
        'comment_approved'     => 1,
    );

 $comment_id = wp_insert_comment($comment_data);

    if ($comment_id > 0) {
        // Manejar la carga de la imagen del comentario
        if (isset($_FILES['comment_image']) && $_FILES['comment_image']['error'] == UPLOAD_ERR_OK) {
            $uploaded_image = media_handle_upload('comment_image', 0);
            if (is_wp_error($uploaded_image)) {
                wp_send_json_error(array('error' => 'Error al subir imagen: ' . $uploaded_image->get_error_message()));
                wp_die();
            } else {
                add_comment_meta($comment_id, 'comment_image', $uploaded_image);
            }
        }

        // Manejar la carga del audio del comentario
        if (isset($_FILES['comment_audio']) && $_FILES['comment_audio']['error'] == UPLOAD_ERR_OK) {
            $uploaded_audio = media_handle_upload('comment_audio', 0);
            if (is_wp_error($uploaded_audio)) {
                wp_send_json_error(array('error' => 'Error al subir audio: ' . $uploaded_audio->get_error_message()));
                wp_die();
            } else {
                add_comment_meta($comment_id, 'comment_audio', $uploaded_audio);
            }
        }

        $comment = get_comment($comment_id);

        // Generar el HTML del comentario
        ob_start();
        avada_comment($comment, array(), 1);
        $comment_html = ob_get_clean();

        wp_send_json_success(array('comment_html' => $comment_html));
    } else {
        wp_send_json_error(array('error' => 'No se pudo insertar el comentario.'));
    }

    wp_die();
}



function theme_enqueue_scripts() {
    wp_enqueue_script('ajax-comments', get_template_directory_uri() . '/js/comentario.js', array('jquery'), '1.1.5', true);


    wp_localize_script('ajax-comments', 'ajax_var', array( // Localiza el script con el nombre 'ajax-comments'
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce') // Nonce para seguridad
    ));

}
add_action('wp_enqueue_scripts', 'theme_enqueue_scripts');