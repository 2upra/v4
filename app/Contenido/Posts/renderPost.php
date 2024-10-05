<?
function htmlPost($filtro)
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

    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($author_id); ?>">

        <? echo fondoPost($filtro, $block, $es_suscriptor, $post_id);  ?>

        <? if ($music): ?>
            <div class="post-content">
                <div class="MFQOYC">
                    <? echo like($post_id) ?>
                    <? echo opcionesRola($post_id, $post_status, $audio_url); ?>
                </div>
                <div class="KLYJBY">
                    <? echo audioPost($post_id) ?>
                </div>
                <div class="LRKHLC">
                    <div class="XOKALG">
                        <p>
                            <? echo $author_name ?>
                        </p>
                        <p>-</p>
                        <? the_content(); ?>
                    </div>
                </div>
                <div class="CPQBEN" style="display: none;">
                    <div class="CPQBAU"><? echo $author_name ?></div>
                    <div class="CPQBCO"><? the_content(); ?></div>
                </div>
            </div>
        <? else: ?>
            <div class="post-content">
                <div class="JNUZCN">
                    <? if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])): ?>
                        <? echo infoPost($author_id, $author_avatar, $author_name, $post_date, $post_id, $block, $colab); ?>
                    <? else: ?>
                        <div class="XABLJI">
                            <? echo $post_status; ?>
                            <? echo opcionesRola($post_id, $post_status, $audio_url); ?>
                            <div class="CPQBEN" style="display: none;">
                                <div class="CPQBAU"><? echo $author_name ?></div>
                                <div class="CPQBCO"><? the_content(); ?></div>
                            </div>
                        </div>
                    <? endif; ?>
                </div>

                <div class="YGWCKC">
                    <? if ($block && !$es_suscriptor): ?>
                        <div class="ZHNDDD">
                            <p>Suscríbete a <? echo esc_html($author_name); ?> para ver el contenido de este post</p>
                            <? echo botonSuscribir($author_id, $author_name); ?>
                        </div>
                    <? else: ?>
                        <div class="NERWFB">
                            <div class="YWBIBG">
                                <div class="thePostContet" data-post-id="<? echo esc_html($post_id) ?>">
                                    <? the_content(); ?>
                                </div>
                                <div>
                                    <?
                                    //Información bpm - escala- nota 
                                    $key_info = $key ? $key : null;
                                    $scale_info = $scale ? $scale : null;
                                    $bpm_info = $bpm ? round($bpm) : null;

                                    $info = array_filter([$key_info, $scale_info, $bpm_info]);
                                    if (!empty($info)) {
                                        echo '<p class="TRZPQD">' . implode(' - ', $info) . '</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <? //POST GENERICO DE RS
                            if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])): ?>
                                <div class="ZQHOQY">
                                    <? wave($audio_url, $audio_id_lite, $post_id); ?>
                                </div>
                            <? else: ?>
                                <div class="KLYJBY">
                                    <? echo audioPost($post_id) ?>
                                </div>
                            <? endif; ?>
                            <div class="FBKMJD">
                                <div class="UKVPJI">


                                    <div class="tags-container" id="tags-<? echo get_the_ID(); ?>"></div>
                                    <!-- Datos del algoritmo -->
                                    <p id-post-algoritmo="<? echo get_the_ID(); ?>" style="display:none;"><? echo esc_html($datosAlgoritmo); ?></p>

                                    <span class="infoIA-btn" data-post-id="<? echo get_the_ID(); ?>">Detalles</span>

                                    <!-- Detalles IA -->
                                    <p id-post-detalles-ia="<? echo get_the_ID(); ?>" style="display:none;"><? echo esc_html($detallesIA); ?></p>
                                </div>
                            </div>
                        </div>
                    <? endif; ?>
                </div>

                <div class="IZXEPH">
                    <div class="QSORIW">
                        <? echo like($post_id) ?>
                        <? echo botonComentar($post_id, $colab) ?>
                        <? echo botonDescarga($post_id) ?>
                        <? echo botonColab($post_id, $colab) ?>
                    </div>
                </div>
            </div>
        <? endif; ?>
    </li>

    <li class="comentarios">
        <? if (comments_open() || get_comments_number()) : comments_template();
        endif; ?>
    </li>
<?
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

    <? if ($filtro === 'momento' || $is_ajax): ?>
        <div id="no-more-posts"></div>
        <div id="no-more-posts-two" no-more="<? echo esc_attr($filtro); ?>"></div>
    <? else: ?>
        <div class="LNVHED no-<? echo esc_attr($filtro); ?>">
            <p>Aún no hay nada aquí</p>
            <? if ($filtro === 'rolastatus'): ?>
                <p>Cuando publiques tu primera rola, aparecerá aquí</p>
            <? endif; ?>
            <button><a href="https://2upra.com/">Volver al inicio</a></button>
        </div>
    <? endif; ?>

<?
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

            <div class="texto-posts" data-post-id="<? echo get_the_ID(); ?>">
                <?
                    the_content();
                    echo get_the_tag_list('<div class="post-tags">', ', ', '</div>');
                    if($current_user_id == $post->post_author) {
                ?>
                    <button class="edit-post-btn" data-post-id="<? echo get_the_ID(); ?>"> Editar Contenido</button>
                <? } ?>
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
