<?

function htmlPost($filtro)
{
    $post_id = get_the_ID();
    $vars = variablesPosts($post_id);
    extract($vars);
    $music = ($filtro === 'rola' || $filtro === 'likes');
    if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
        $filtro = 'rolastatus';
    }
    $sampleList = $filtro === 'sampleList';
    //llevar sample list
    $wave = get_post_meta($post_id, 'waveform_image_url', true);
    $waveCargada = get_post_meta($post_id, 'waveCargada', true);
    $postAut = get_post_meta($post_id, 'postAut', true);
    $verificado = get_post_meta($post_id, 'Verificado', true);
    $recortado = get_post_meta($post_id, 'recortado', true);

    $urlAudioSegura = audioUrlSegura($audio_id_lite);
    if (is_wp_error($urlAudioSegura)) {
        $urlAudioSegura = '';
    }
    ob_start();
    /*wavejs.js?ver=2.0.12.358813928:108 
 No se encontró wavesurfer para postId: 268787
 (anónimo)	@	wavejs.js?ver=2.0.12.358813928:108 
 
 el filtro es sampleList*/
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($author_id); ?>">

        <? if ($sampleList): ?>
            <div class="LISTSAMPLE">
                <div class="KLYJBY">
                    <? // echo audioPostList($post_id); 
                    ?>
                </div>
                <? echo imagenPostList($block, $es_suscriptor, $post_id); ?>
                <div class="INFOLISTSAMPLE">
                    <p class="CONTENTLISTSAMPLE">
                        <a href="<? echo esc_url(get_permalink()); ?>" id-post="<? echo get_the_ID(); ?>">
                            <? the_content(); ?>
                        </a>
                    </p>
                    <div class="TAGSLISTSAMPLE">
                        <div class="tags-container" id="tags-<? echo get_the_ID(); ?>"></div>
                        <p id-post-algoritmo="<? echo get_the_ID(); ?>" style="display:none;"><? echo esc_html($datosAlgoritmo); ?></p>
                    </div>
                </div>
                <div class="INFOTYPELIST">
                    <div class="verificacionPost">
                        <? if ($verificado == '1') : ?>
                            <? echo $GLOBALS['check']; ?>
                        <? elseif ($postAut == '1' && current_user_can('administrator')) : ?>
                            <div class="verificarPost" data-post-id="<? echo $post_id; ?> style="cursor: pointer;"> 
                            <? echo $GLOBALS['robot']; ?>
                            </div>
                        <? endif; ?>
                    </div>
                </div>
                <div class="ZQHOQY LISTWAVESAMPLE">
                    <div id="waveform-<? echo $post_id; ?>"
                        class="waveform-container without-image"
                        postIDWave="<? echo $post_id; ?>"
                        data-wave-cargada="<? echo $waveCargada ? 'true' : 'false'; ?>"
                        data-audio-url="<? echo esc_url($urlAudioSegura); ?>">
                        <div class="waveform-background" style="background-image: url('<? echo esc_url($wave); ?>');"></div>
                        <div class="waveform-message"></div>
                        <div class="waveform-loading" style="display: none;">Cargando...</div>
                    </div>
                </div>
                <? echo renderPostControls($post_id, $colab); ?>
                <? echo opcionesPost($post_id, $author_id); ?>
            </div>
        <? else: ?>
            <? echo fondoPost($filtro, $block, $es_suscriptor, $post_id); ?>
            <? if ($music): ?>
                <? renderMusicContent($filtro, $post_id, $author_name, $block, $es_suscriptor, $post_status, $audio_url); ?>
            <? else: ?>
                <? renderNonMusicContent($filtro, $post_id, $author_id, $author_avatar, $author_name, $post_date, $block, $colab, $es_suscriptor, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $post_status, $audio_id_lite); ?>
            <? endif; ?>
        <? endif; ?>
    </li>

    <li class="comentariosPost">

    </li>
<?
    return ob_get_clean();
}


function renderMusicContent($filtro, $post_id, $author_name, $block, $es_suscriptor, $post_status, $audio_url)
{
?>
    <div class="post-content">
        <div class="MFQOYC">
            <? echo like($post_id); ?>
            <? echo opcionesRola($post_id, $post_status, $audio_url); ?>
        </div>
        <div class="KLYJBY">
            <? echo audioPost($post_id); ?>
        </div>
        <div class="LRKHLC">
            <div class="XOKALG">
                <p><? echo $author_name; ?></p>
                <p>-</p>
                <? the_content(); ?>
            </div>
        </div>
        <div class="CPQBEN" style="display: none;">
            <div class="CPQBAU"><? echo $author_name; ?></div>
            <div class="CPQBCO"><? the_content(); ?></div>
        </div>
    </div>
<?
}


function renderNonMusicContent($filtro, $post_id, $author_id, $author_avatar, $author_name, $post_date, $block, $colab, $es_suscriptor, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $post_status, $audio_id_lite)
{
?>
    <div class="post-content">
        <div class="JNUZCN">
            <? if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])): ?>
                <? echo infoPost($author_id, $author_avatar, $author_name, $post_date, $post_id, $block, $colab); ?>
            <? else: ?>
                <div class="XABLJI">
                    <? echo esc_html($post_status); ?>
                    <? echo opcionesRola($post_id, $post_status, $audio_url); ?>
                    <div class="CPQBEN" style="display: none;">
                        <div class="CPQBAU"><? echo $author_name; ?></div>
                        <div class="CPQBCO"><? the_content(); ?></div>
                    </div>
                </div>
            <? endif; ?>
        </div>

        <div class="YGWCKC">
            <? if ($block && !$es_suscriptor): ?>
                <? renderSubscriptionPrompt($author_name, $author_id); ?>
            <? else: ?>
                <? renderContentAndMedia($filtro, $post_id, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $audio_id_lite); ?>
            <? endif; ?>
        </div>

        <div class="IZXEPH">
            <? renderPostControls($post_id, $colab); ?>
        </div>
    </div>
<?
}



function renderSubscriptionPrompt($author_name, $author_id)
{
?>
    <div class="ZHNDDD">
        <p>Suscríbete a <? echo esc_html($author_name); ?> para ver el contenido de este post</p>
        <? echo botonSuscribir($author_id, $author_name); ?>
    </div>
<?
}


function renderPostControls($post_id, $colab)
{
?>
    <div class="QSORIW">
        <? echo like($post_id); ?>
        <? echo botonComentar($post_id, $colab); ?>
        <? echo botonDescarga($post_id); ?>
        <? echo botonColab($post_id, $colab); ?>
        <? echo botonColeccion($post_id); ?>
    </div>
<?
}



function renderContentAndMedia($filtro, $post_id, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $audio_id_lite)
{
?>
    <div class="NERWFB">
        <div class="YWBIBG">
            <div class="thePostContet" data-post-id="<? echo esc_html($post_id); ?>">
                <? the_content(); ?>
            </div>
            <div>
                <?
                // Información bpm - escala - nota 
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
        <?
        if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])):
        ?>
            <div class="ZQHOQY">
                <? wave($audio_url, $audio_id_lite, $post_id); ?>
            </div>
        <? else: ?>
            <div class="KLYJBY">
                <? echo audioPost($post_id); ?>
            </div>
        <? endif; ?>
        <div class="FBKMJD">
            <div class="UKVPJI">
                <div class="tags-container" id="tags-<? echo get_the_ID(); ?>"></div>
                <!-- Datos del algoritmo -->

                <p id-post-algoritmo="<? echo get_the_ID(); ?>" style="display:none;">
                    <? echo esc_html(limpiarJSON($datosAlgoritmo)); ?>
                </p>
            </div>
        </div>
    </div>
<?
}


function limpiarJSON($json_data)
{
    // Si es una cadena y empieza y termina con comillas
    if (is_string($json_data) && substr($json_data, 0, 1) === '"' && substr($json_data, -1) === '"') {
        // Eliminar las comillas extras y decodificar los caracteres escapados
        $json_data = json_decode($json_data);
    }

    // Si es un array u objeto, convertirlo a JSON
    if (is_array($json_data) || is_object($json_data)) {
        $json_data = json_encode($json_data);
    }

    return $json_data;
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
        'ajax_url' => admin_url('admin-ajax.'),
    ));
    wp_enqueue_script('editar-post-js');
}

add_action('wp_enqueue_scripts', 'encolar_editar_post_script');
