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
    $rolaList = $filtro === 'rolaListLike';
    $momento = $filtro === 'momento';
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
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo get_the_ID(); ?>"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($author_id); ?>">

        <? if ($sampleList || $rolaList): ?>
            <? sampleListHtml($block, $es_suscriptor, $post_id, $datosAlgoritmo, $verificado, $postAut, $urlAudioSegura, $wave, $waveCargada, $colab, $author_id, $audio_id_lite); ?>
        <? else: ?>
            <? echo fondoPost($filtro, $block, $es_suscriptor, $post_id); ?>
            <? if ($music || $momento): ?>
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




function sampleListHtml($block, $es_suscriptor, $post_id, $datosAlgoritmo, $verificado, $postAut, $urlAudioSegura, $wave, $waveCargada, $colab, $author_id, $audio_id_lite = null)
{
    $rola_meta = get_post_meta($post_id, 'rola', true);
?>
    <div class="LISTSAMPLE">
        <?php if ($rola_meta === '1') : ?>
            <div class="KLYJBY">
                <?php echo audioPost($post_id); ?>
            </div>
            <?php echo imagenPostList($block, $es_suscriptor, $post_id); ?>
            <div class="INFOLISTSAMPLE">
                <div class="CONTENTLISTSAMPLE">
                    <a id-post="<?php echo $post_id; ?>">
                        <div class="LRKHLC">
                            <div class="XOKALG">
                                <?php
                                $nombre_rola_html = '';
                                $nombre_rola = get_post_meta($post_id, 'nombreRola', true);
                                if (empty($nombre_rola)) {
                                    $nombre_rola = get_post_meta($post_id, 'nombreRola1', true);
                                }
                                if (!empty($nombre_rola)) {
                                    $nombre_rola_html = '<p class="nameRola">' . esc_html($nombre_rola) . '</p>';
                                }
                                echo $nombre_rola_html;
                                ?>
                            </div>
                        </div>
                    </a>
                    <div class="CPQBAU"><?php echo get_the_author_meta('display_name', $author_id); ?></div>
                </div>
                <div class="MOREINFOLIST">
                    <?php
                    $audio_duration = get_post_meta($post_id, 'audio_duration_1', true);
                    $nombre_lanzamiento = get_post_meta($post_id, 'nombreLanzamiento', true);


                    if (!empty($nombre_lanzamiento)) {
                        echo '<p class="lanzamiento"><span>' . esc_html($nombre_lanzamiento) . '</span></p>';
                    }
                    if (!empty($audio_duration)) {
                        echo '<p class="duration"><span >' . esc_html($audio_duration) . '</span></p>';
                    }

                    ?>
                </div>
                <div class="CPQBEN" style="display: none;">
                    <?php echo like($post_id); ?>
                    <div class="CPQBAU"><?php echo get_the_author_meta('display_name', $author_id); ?></div>
                    <div class="CPQBCO">
                        <?php
                        $nombre_rola = get_post_meta($post_id, 'nombreRola', true);
                        if (empty($nombre_rola)) {
                            $nombre_rola = get_post_meta($post_id, 'nombreRola1', true);
                        }
                        if (!empty($nombre_rola)) {
                            echo "<p>" . esc_html($nombre_rola) . "</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <?php echo renderPostControls($post_id, $colab, $audio_id_lite); ?>
            <?php echo opcionesPost($post_id, $author_id); ?>
        <?php else : ?>
            <?php // Original structure when rola is not 1 
            ?>
            <?php echo imagenPostList($block, $es_suscriptor, $post_id); ?>
            <div class="INFOLISTSAMPLE">
                <div class="CONTENTLISTSAMPLE">
                    <a id-post="<?php echo $post_id; ?>">
                        <?php
                        $content = get_post_field('post_content', $post_id);
                        $content = wp_trim_words($content, 20, '...');
                        echo wp_kses_post($content);
                        ?>
                    </a>
                </div>
                <div class="CPQBEN" style="display: none;">
                    <?php echo like($post_id); ?>
                    <div class="CPQBAU"><?php echo get_the_author_meta('display_name', $author_id); ?></div>
                    <div class="CPQBCO">
                        <?php
                        $nombre_rola = get_post_meta($post_id, 'nombreRola', true);
                        if (empty($nombre_rola)) {
                            $nombre_rola = get_post_meta($post_id, 'nombreRola1', true);
                        }
                        if (!empty($nombre_rola)) {
                            echo "<p>" . esc_html($nombre_rola) . "</p>";
                        }
                        ?>
                    </div>
                </div>
                <div class="TAGSLISTSAMPLE">
                    <div class="tags-container" id="tags-<?php echo $post_id; ?>"></div>
                    <p id-post-algoritmo="<?php echo $post_id; ?>" style="display:none;">
                        <?php echo esc_html(limpiarJSON($datosAlgoritmo)); ?>
                    </p>
                </div>
            </div>
            <div class="INFOTYPELIST">
                <div class="verificacionPost">
                    <?php if ($verificado == '1') : ?>
                        <?php echo $GLOBALS['check']; ?>
                    <?php elseif ($postAut == '1' && current_user_can('administrator')) : ?>
                        <div class="verificarPost" data-post-id="<?php echo $post_id; ?>" style="cursor: pointer;">
                            <?php echo $GLOBALS['robot']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ZQHOQY LISTWAVESAMPLE">
                <div id="waveform-<?php echo $post_id; ?>"
                    class="waveform-container without-image"
                    postIDWave="<?php echo $post_id; ?>"
                    data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>"
                    data-audio-url="<?php echo esc_url($urlAudioSegura); ?>">
                    <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
                    <div class="waveform-message"></div>
                    <div class="waveform-loading" style="display: none;">Cargando...</div>
                </div>
            </div>
            <?php echo renderPostControls($post_id, $colab, $audio_id_lite); ?>
            <?php echo opcionesPost($post_id, $author_id); ?>
        <?php endif; ?>
    </div>
<?php
}




function renderMusicContent($filtro, $post_id, $author_name, $block, $es_suscriptor, $post_status, $audio_url)
{
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
    $optimized_thumbnail_url = img($thumbnail_url, 40, 'all');
    $momento = get_post_meta($post_id, 'momento', true);
    $esColeccion = get_post_meta($post_id, 'datosColeccion', true);
    if (!$esColeccion) {
        $esColeccion = get_post_meta($post_id, 'ultimaModificacion', true);
    }
    $permalink = get_permalink($post_id);

?>
    <?php if (!empty($momento) || !empty($esColeccion)) : ?>
        <a href="<?php echo esc_url($permalink); ?>">
        <?php endif; ?>
        <div class="post-content">
            <div class="MFQOYC">
                <? echo like($post_id); ?>
                <? echo opcionesRola($post_id, $post_status, $audio_url); ?>
            </div>
            <div class="KLYJBY">
                <? echo audioPost($post_id); ?>
            </div>

            <?php if (!empty($momento) || !empty($esColeccion)) : ?>
                <div class="contentMoment">
                    <?php
                    $content = get_the_content();
                    if (!empty($content)) {
                        echo $content;
                    } else {
                        echo '<p>' . get_the_title() . '</p>';
                    }
                    ?>
                </div>
            <?php else : ?>
                <div class="LRKHLC">
                    <div class="XOKALG">
                        <?php
                        $rola_meta = get_post_meta($post_id, 'rola', true);
                        $nombre_rola_html = ''; // Variable para almacenar el HTML de nombreRola

                        if ($rola_meta === '1') {
                            $nombre_rola = get_post_meta($post_id, 'nombreRola', true);
                            if (empty($nombre_rola)) {
                                $nombre_rola = get_post_meta($post_id, 'nombreRola1', true);
                            }
                            if (!empty($nombre_rola)) {
                                $nombre_rola_html = '<p class="nameRola">' . esc_html($nombre_rola) . '</p>';
                            }
                        }
                        $output = '<p>' . esc_html($author_name) . '</p>';
                        $output .= '<p>-</p>';
                        $output .= $nombre_rola_html;

                        echo $output;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="CPQBEN" style="display: none;">
                <div class="CPQBAU"><? echo $author_name; ?></div>
                <div class="CPQBCO">
                    <?php
                    $rola_meta = get_post_meta($post_id, 'rola', true);

                    if ($rola_meta === '1') {
                        $nombre_rola = get_post_meta($post_id, 'nombreRola', true);
                        if (empty($nombre_rola)) {
                            $nombre_rola = get_post_meta($post_id, 'nombreRola1', true);
                        }
                        if (!empty($nombre_rola)) {
                            echo "<p>" . esc_html($nombre_rola) . "</p>";
                        } else {
                        }
                    } else {
                    }
                    ?>
                </div>
                <img src="<?= esc_url($optimized_thumbnail_url); ?>" alt="">
            </div>
        </div>
        <?php if (!empty($momento) || !empty($esColeccion)) : ?>
        </a>
    <?php endif; ?>
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
                        <div class="CPQBCO">

                            <?php
                            $post_id = get_the_ID(); // Asegúrate de tener el ID del post actual
                            $rola_meta = get_post_meta($post_id, 'rola', true);

                            if ($rola_meta === '1') {
                                $nombre_rola = get_post_meta($post_id, 'nombreRola', true);
                                if (empty($nombre_rola)) {
                                    $nombre_rola = get_post_meta($post_id, 'nombreRola1', true);
                                }
                                if (!empty($nombre_rola)) {
                                    echo "<p>" . esc_html($nombre_rola) . "</p>";
                                } else {
                                }
                            } else {
                                the_content();
                                if (has_post_thumbnail($post_id) && empty($audio_id_lite)) : ?>
                                    <div class="post-thumbnail">
                                        <?php echo get_the_post_thumbnail($post_id, 'full'); ?>
                                    </div>
                            <?php endif;
                            }
                            ?>

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
                    <? renderPostControls($post_id, $colab, $audio_id_lite); ?>
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



function renderPostControls($post_id, $colab, $audio_id_lite = null)
{

    $mostrarBotonCompra = get_post_meta($post_id, 'tienda', true) === '1';
    ?>
        <div class="QSORIW">


            <? echo like($post_id); ?>
            <? if ($mostrarBotonCompra): ?>
                <? echo botonCompra($post_id); ?>
            <? endif; ?>
            <? echo botonComentar($post_id, $colab); ?>
            <? if (!empty($audio_id_lite)) : ?>
                <? echo botonDescarga($post_id); ?>
                <? echo botonColab($post_id, $colab); ?>
                <? echo botonColeccion($post_id); ?>
            <? endif; ?>
        </div>
    <?
}



function renderContentAndMedia($filtro, $post_id, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $audio_id_lite)
{
    ?>
        <div class="NERWFB">
            <div class="YWBIBG">
                <? if (!empty($audio_id_lite)) : ?>
                    <?
                    $has_post_thumbnail = has_post_thumbnail($post_id);
                    $imagen_temporal_id = get_post_meta($post_id, 'imagenTemporal', true);
                    ?>
                    <? if ($has_post_thumbnail || $imagen_temporal_id) : ?>
                        <div class="MRPDOR">
                            <? if ($has_post_thumbnail) : ?>
                                <div class="post-thumbnail">
                                    <?
                                    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
                                    $optimized_thumbnail_url = img($thumbnail_url, 40, 'all');
                                    ?>
                                    <img src="<? echo esc_url($optimized_thumbnail_url); ?>" alt="<? echo esc_attr(get_the_title($post_id)); ?>">
                                </div>
                            <? elseif ($imagen_temporal_id) : ?>
                                <div class="temporal-thumbnail">
                                    <?
                                    $temporal_image_url = wp_get_attachment_url($imagen_temporal_id);
                                    $optimized_temporal_image_url = img($temporal_image_url, 40, 'all');
                                    ?>
                                    <img src="<? echo esc_url($optimized_temporal_image_url); ?>" alt="Imagen temporal">
                                </div>
                            <? endif; ?>
                        </div>
                    <? endif; ?>
                <? endif; ?>

                <div class="OASDEF">

                    <div class="thePostContet" data-post-id="<? echo esc_attr($post_id); ?>">
                        <?php
                        $post_id = get_the_ID(); // Asegúrate de tener el ID del post actual
                        $rola_meta = get_post_meta($post_id, 'rola', true);

                        if ($rola_meta === '1') {
                            $nombre_rola = get_post_meta($post_id, 'nombreRola', true);
                            if (empty($nombre_rola)) {
                                $nombre_rola = get_post_meta($post_id, 'nombreRola1', true);
                            }
                            if (!empty($nombre_rola)) {
                                echo "<p>" . esc_html($nombre_rola) . "</p>";
                            } else {
                            }
                        } else {
                            the_content();
                            if (has_post_thumbnail($post_id) && empty($audio_id_lite)) : ?>
                                <div class="post-thumbnail">
                                    <?php echo get_the_post_thumbnail($post_id, 'full'); ?>
                                </div>
                        <?php endif;
                        }
                        ?>
                    </div>
                    <div>
                        <?
                        $key_info = $key ? $key : null;
                        $scale_info = $scale ? $scale : null;
                        $bpm_info = $bpm ? round($bpm) : null;

                        $info = array_filter([$key_info, $scale_info, $bpm_info]);
                        if (!empty($info)) {
                            echo '<p class="TRZPQD">' . implode(' - ', $info) . '</p>';
                        }
                        ?>
                    </div>
                    <? if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])) : ?>
                        <div class="ZQHOQY">
                            <? if (!empty($audio_id_lite)) : ?>
                                <? wave($audio_url, $audio_id_lite, $post_id); ?>
                            <? endif; ?>
                        </div>
                    <? else : ?>
                        <div class="KLYJBY">
                            <? echo audioPost($post_id); ?>
                        </div>
                    <? endif; ?>
                </div>

            </div>

            <? if (!empty($audio_id_lite)) : ?>
                <div class="FBKMJD">
                    <div class="UKVPJI">
                        <div class="tags-container" id="tags-<? echo esc_attr(get_the_ID()); ?>"></div>
                        <p id-post-algoritmo="<? echo esc_attr(get_the_ID()); ?>" style="display:none;">
                            <? echo esc_html(limpiarJSON($datosAlgoritmo)); ?>
                        </p>
                    </div>
                </div>
            <? endif; ?>
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
                <? echo $GLOBALS['emptystate']; ?>
                <p>Ñoño aqui no han puesto nada aún</p>
                <? if ($filtro === 'rolastatus'): ?>
                    <p>Cuando publiques tu primera rola, aparecerá aquí</p>
                <? endif; ?>
                <button class="borde"><a href="https://2upra.com/">Volver al inicio</a></button>
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
