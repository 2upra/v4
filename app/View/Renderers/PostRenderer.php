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
