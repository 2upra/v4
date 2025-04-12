<?php
// Refactor(Exec): Mueve función htmlPost() desde app/Content/Posts/View/renderPost.php
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

        <? if ($sampleList || $rolaList):
            // Refactor(Org): Función limpiarJSON movida a StringUtils.php
             ?>
            <? sampleListHtml($block, $es_suscriptor, $post_id, $datosAlgoritmo, $verificado, $postAut, $urlAudioSegura, $wave, $waveCargada, $colab, $author_id, $audio_id_lite); ?>
        <? else: ?>
            <? echo fondoPost($filtro, $block, $es_suscriptor, $post_id); ?>
            <? if ($music || $momento):
                // Refactor(Org): Función renderMusicContent movida a app/View/Renderers/PostRenderer.php
                 ?>
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

// Refactor(Exec): Función renderSubscriptionPrompt movida desde app/Content/Posts/View/renderPost.php
function renderSubscriptionPrompt($author_name, $author_id)
{
    ?>
        <div class="ZHNDDD">
            <p>Suscríbete a <? echo esc_html($author_name); ?> para ver el contenido de este post</p>
            <? echo botonSuscribir($author_id, $author_name); ?>
        </div>
    <?
}

// Refactor(Exec): Función renderContentAndMedia movida desde app/Content/Posts/View/renderPost.php
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
                        <?
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
                                    <? echo get_the_post_thumbnail($post_id, 'full'); ?>
                                </div>
                        <? endif;
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

// Refactor(Exec): Mueve función renderNonMusicContent() desde app/Content/Posts/View/renderPost.php
function renderNonMusicContent($filtro, $post_id, $author_id, $author_avatar, $author_name, $post_date, $block, $colab, $es_suscriptor, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $post_status, $audio_id_lite)
{
?>
    <div class="post-content">
        <div class="JNUZCN">
            <? if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])):
                // Refactor(Org): Función limpiarJSON movida a StringUtils.php
                 ?>
                <? echo infoPost($author_id, $author_avatar, $author_name, $post_date, $post_id, $block, $colab); ?>
            <? else: ?>
                <div class="XABLJI">
                    <? echo esc_html($post_status); ?>
                    <? echo opcionesRola($post_id, $post_status, $audio_url); ?>
                    <div class="CPQBEN" style="display: none;">
                        <div class="CPQBAU"><? echo $author_name; ?></div>
                        <div class="CPQBCO">

                            <?
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
                                        <? echo get_the_post_thumbnail($post_id, 'full'); ?>
                                    </div>
                            <? endif;
                            }
                            ?>

                        </div>
                    </div>
                <? endif; ?>
                </div>

                <div class="YGWCKC">
                    <? if ($block && !$es_suscriptor):
                        // Refactor(Exec): Función renderSubscriptionPrompt movida a app/View/Renderers/PostRenderer.php
                         ?>
                        <? renderSubscriptionPrompt($author_name, $author_id); ?>
                    <? else: ?>
                        <? renderContentAndMedia($filtro, $post_id, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $audio_id_lite); // Llamada a la función movida a PostRenderer.php ?>
                    <? endif; ?>
                </div>

                <div class="IZXEPH">
                    <? renderPostControls($post_id, $colab, $audio_id_lite); // Llamada a la función movida ?>
                </div>
        </div>
    <?
}

// Refactor(Org): Moved function postrolaresumen() from app/Perfiles/perfilmusic.php
function postrolaresumen() {
    global $post;
    $current_user_id = get_current_user_id();
    $author_id = get_the_author_meta('ID');
    $user = get_userdata($author_id); 
    $insignia_urls = get_insignia_urls();
    $insignia_html = ''; 
    $author_name = get_the_author();
    $audio_id_lite = get_post_meta(get_the_ID(), 'post_audio_lite', true);   
    $audio_id = get_post_meta(get_the_ID(), 'post_audio', true);
    $audio_url = wp_get_attachment_url($audio_id);
    $audio_lite = wp_get_attachment_url($audio_id_lite);
    $wave = get_post_meta(get_the_ID(), 'audio_waveform_image', true);
    $duration = get_post_meta(get_the_ID(), 'audio_duration', true); 

    // Obtener información de 'likes' NUEVO
    $current_post_id = get_the_ID();
    $like_count = contarLike($current_post_id);
    $user_has_liked = chequearLike($current_post_id, $current_user_id);
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    $post_content = get_the_content();
    $post_content = wp_strip_all_tags($post_content); 
    $post_content = esc_attr($post_content); 
    $post_thumbnail_id = get_post_thumbnail_id();
    $post_thumbnail_url = function_exists('jetpack_photon_url') 
        ? jetpack_photon_url(wp_get_attachment_image_url($post_thumbnail_id, 'medium'), array('quality' => 50, 'strip' => 'all')) 
        : wp_get_attachment_image_url($post_thumbnail_id, 'medium');

    ob_start();
    ?>
    <li class="social-post rola" data-post-id="<?php echo get_the_ID(); ?>">
        <input type="hidden" class="post-id" value="<?php echo get_the_ID(); ?>" />
        <div class="rola social-post-content" style="font-size: 13px;">
                    
            <div id="audio-container-<?php echo get_the_ID(); ?>" class="audio-container" 
             data-imagen="<?php echo esc_url($post_thumbnail_url); ?>"
             data-title="<?php echo $post_content; ?>"
             data-author="<?php echo esc_attr($author_name); ?>"
             data-post-id="<?php echo get_the_ID(); ?>"
             data-artist="<?php echo esc_attr($author_id); ?>"
             data-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
             style="width: 40px; height: 40px; aspect-ratio: 1 / 1; position: relative;">

                <img class="imagen-post" src="<?php echo esc_url($post_thumbnail_url); ?>" alt="Imagen del post" style="position: absolute; border-radius: 3%; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                <div class="play-pause-sobre-imagen" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); cursor: pointer; display: none;">
                    <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;"> 
                </div>
                <audio id="audio-<?php echo get_the_ID(); ?>" src="<?php echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"></audio>
            </div>


    <div class="contentrola"><?php the_content(); ?></div>
    <div class="duracionrola"><?php echo esc_html($duration); ?></div>
    <div class="social-post-like rola">
        <?php
        $current_post_id = get_the_ID();
        $nonce = wp_create_nonce('like_post_nonce');
        $like_count = contarLike($current_post_id);
        like($current_post_id);
        ?>          
    </div>

</li>
  <?php
  return ob_get_clean();
}
