<?php


function obtener_html_publicacion($filtro)
{
    $post_id = get_the_ID();
    $vars = variablesPosts($post_id);
    extract($vars);
    $music = ($filtro === 'rola' || $filtro === 'likes');
    if ($filtro === 'rolasEliminadas' || $filtro === 'rolasRechazadas' || $filtro === 'rola' || $filtro === 'likes') {
        $filtro = 'rolastatus';
    }
    ob_start();
?>

    <li class="POST-<?php echo esc_attr($filtro); ?> EDYQHV"
        filtro="<?php echo esc_attr($filtro); ?>"
        id-post="<?php echo get_the_ID(); ?>"
        autor="<?php echo esc_attr($author_id); ?>">

        <?php echo fondoPost($filtro, $block, $es_suscriptor, $post_id);  ?>

        <?php if ($music): ?>
            <div class="post-content">
                <div class="MFQOYC">
                    <?php echo like($post_id) ?>
                    <?php echo opcionesRola($post_id, $post_status, $audio_url); ?>
                </div>
                <div class="KLYJBY">
                    <?php echo audioPost($post_id) ?>
                </div>
                <div class="LRKHLC">
                    <div class="XOKALG">
                        <p>
                            <?php echo $author_name ?>
                        </p>
                        <p>-</p>
                        <?php the_content(); ?>
                    </div>
                </div>
                <div class="CPQBEN" style="display: none;">
                    <div class="CPQBAU"><?php echo $author_name ?></div>
                    <div class="CPQBCO"><?php the_content(); ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="post-content">
                <div class="JNUZCN">
                    <?php if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])): ?>
                        <?php echo infoPost($author_id, $author_avatar, $author_name, $post_date, $post_id, $block, $colab); ?>
                    <?php else: ?>
                        <div class="XABLJI">
                            <?php echo $post_status; ?>
                            <?php echo opcionesRola($post_id, $post_status, $audio_url); ?>
                            <div class="CPQBEN" style="display: none;">
                                <div class="CPQBAU"><?php echo $author_name ?></div>
                                <div class="CPQBCO"><?php the_content(); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="YGWCKC">
                    <?php if ($block && !$es_suscriptor): ?>
                        <div class="ZHNDDD">
                            <p>Suscríbete a <?php echo esc_html($author_name); ?> para ver el contenido de este post</p>
                            <?php echo botonSuscribir($author_id, $author_name); ?>
                        </div>
                    <?php else: ?>
                        <div class="NERWFB">
                            <?php if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])): ?>
                                <div class="ZQHOQY">
                                    <?php wave($audio_url, $audio_id_lite, $post_id); ?>
                                </div>
                            <?php else: ?>
                                <div class="KLYJBY">
                                    <?php echo audioPost($post_id) ?>
                                </div>
                            <?php endif; ?>

                            <?php the_content(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="IZXEPH">
                    <div class="QSORIW">
                        <?php echo like($post_id) ?>
                        <?php echo botonComentar($post_id, $colab) ?>
                        <?php echo botonDescarga($post_id) ?>
                        <?php echo botonColab($post_id, $colab) ?>
                    </div>

                    <div id="soliColab" class="soliColab" style="display:none;" post-id="<?php echo get_the_ID(); ?>">
                        <textarea id="mensajeColab" placeholder="Escribe un mensaje para colaborar"></textarea>
                        <div class="previewsFormColab">
                            <div class="previewAreaArchivosColab" id="previewAreaRolaColab">Arrastra tu sample
                                <label></label>
                            </div>
                            <input type="file" id="postAudioColab" name="post_audioColab" style="display:none;">
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </li>

    <li class="comentarios">
        <?php if (comments_open() || get_comments_number()) : comments_template();
        endif; ?>
    </li>
<?php
    return ob_get_clean();
}



function nohayPost($filtro, $is_ajax)
{
    $post_id = get_the_ID();
    $vars = variablesPosts($post_id);
    extract($vars);
    $music = ($filtro === 'rola' || $filtro === 'likes');
    if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
        $filtro = 'rolastatus';
    }

    ob_start();
?>

    <?php if ($filtro === 'momento' || $is_ajax): ?>
        <div id="no-more-posts"></div>
        <div id="no-more-posts-two" no-more="<?php echo esc_attr($filtro); ?>"></div>
    <?php else: ?>
        <div class="LNVHED no-<?php echo esc_attr($filtro); ?>">
            <p>Aún no hay nada aquí</p>
            <?php if ($filtro === 'rolastatus'): ?>
                <p>Cuando publiques tu primera rola, aparecerá aquí</p>
            <?php endif; ?>
            <button><a href="https://2upra.com/">Volver al inicio</a></button>
        </div>
    <?php endif; ?>

<?php
    return ob_get_clean();
}
































//Banear usuario desde el post
function handle_user_modification()
{
    if (current_user_can('administrator') && isset($_POST['author_id'])) {
        $author_id = intval($_POST['author_id']);
        if (!in_array('administrator', get_userdata($author_id)->roles)) {
            // Obtener todos los tipos de publicaciones
            $args = array(
                'author'         => $author_id,
                'posts_per_page' => -1,
                'post_type'      => 'any', // 'any' incluye todos los tipos de publicaciones
                'post_status'    => 'any'  // Incluye publicaciones en cualquier estado
            );

            $user_posts = get_posts($args);
            foreach ($user_posts as $post) {
                wp_delete_post($post->ID, true); // Borrado permanente
            }

            // Cambiar el rol del usuario a 'sin_acceso'
            $user = new WP_User($author_id);
            $user->set_role('sin_acceso');

            wp_send_json_success('Publicaciones eliminadas y usuario desactivado.');
        }
    }
    wp_send_json_error('No tienes permisos para realizar esta acción.');
}
add_action('wp_ajax_handle_user_modification', 'handle_user_modification');






/* EL HTML 

            <div class="texto-posts" data-post-id="<?php echo get_the_ID(); ?>">
                <?php
                    the_content();
                    echo get_the_tag_list('<div class="post-tags">', ', ', '</div>');
                    if($current_user_id == $post->post_author) {
                ?>
                    <button class="edit-post-btn" data-post-id="<?php echo get_the_ID(); ?>"> Editar Contenido</button>
                <?php } ?>
            </div>

            */



add_action('wp_ajax_update_post_content', 'update_post_content_callback');

function update_post_content_callback()
{
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'No tienes permiso para editar este post.'));
    }
    $post_data = array(
        'ID'           => $post_id,
        'post_content' => $content
    );
    $updated = wp_update_post($post_data);
    if (is_wp_error($updated)) {
        wp_send_json_error(array('message' => 'Error al actualizar el post.'));
    }
    wp_set_post_tags($post_id, $tags);
    wp_send_json_success(array('message' => 'Post y tags actualizados con éxito.'));
}

function encolar_editar_post_script()
{
    global $post;
    wp_register_script('editar-post-js', get_template_directory_uri() . '/js/editarpost.js', array('jquery'), '1.0.16', true);
    wp_localize_script('editar-post-js', 'ajax_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
    wp_enqueue_script('editar-post-js');
}

add_action('wp_enqueue_scripts', 'encolar_editar_post_script');
