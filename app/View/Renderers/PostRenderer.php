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
            <? // Refactor(Exec): Llamada a función sampleListHtml() movida a este archivo
               sampleListHtml($block, $es_suscriptor, $post_id, $datosAlgoritmo, $verificado, $postAut, $urlAudioSegura, $wave, $waveCargada, $colab, $author_id, $audio_id_lite); ?>
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

// Refactor(Exec): Mueve función sampleListHtml() desde app/Content/Posts/View/renderPost.php
function sampleListHtml($block, $es_suscriptor, $post_id, $datosAlgoritmo, $verificado, $postAut, $urlAudioSegura, $wave, $waveCargada, $colab, $author_id, $audio_id_lite = null)
{
    $rola_meta = get_post_meta($post_id, 'rola', true);
?>
    <div class="LISTSAMPLE">
        <? if ($rola_meta === '1') : ?>
            <div class="KLYJBY">
                <? echo audioPost($post_id); ?>
            </div>
            <? echo imagenPostList($block, $es_suscriptor, $post_id); ?>
            <div class="INFOLISTSAMPLE">
                <div class="CONTENTLISTSAMPLE">
                    <a id-post="<? echo $post_id; ?>">
                        <div class="LRKHLC">
                            <div class="XOKALG">
                                <?
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
                    <div class="CPQBAU"><? echo get_the_author_meta('display_name', $author_id); ?></div>
                </div>
                <div class="MOREINFOLIST">
                    <?
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
                    <? echo like($post_id); ?>
                    <div class="CPQBAU"><? echo get_the_author_meta('display_name', $author_id); ?></div>
                    <div class="CPQBCO">
                        <?
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

            <? echo renderPostControls($post_id, $colab, $audio_id_lite); // Llamada a la función movida ?>
            <? echo opcionesPost($post_id, $author_id); ?>
        <? else : ?>
            <? // Original structure when rola is not 1
            ?>
            <? echo imagenPostList($block, $es_suscriptor, $post_id); ?>
            <div class="INFOLISTSAMPLE">
                <div class="CONTENTLISTSAMPLE">
                    <a id-post="<? echo $post_id; ?>">
                        <?
                        $content = get_post_field('post_content', $post_id);
                        $content = wp_trim_words($content, 20, '...');
                        echo wp_kses_post($content);
                        ?>
                    </a>
                </div>
                <div class="CPQBEN" style="display: none;">
                    <? echo like($post_id); ?>
                    <div class="CPQBAU"><? echo get_the_author_meta('display_name', $author_id); ?></div>
                    <div class="CPQBCO">
                        <?
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
                    <div class="tags-container" id="tags-<? echo $post_id; ?>"></div>
                    <p id-post-algoritmo="<? echo $post_id; ?>" style="display:none;">
                        <? echo esc_html(limpiarJSON($datosAlgoritmo)); ?>
                    </p>
                </div>
            </div>
            <div class="INFOTYPELIST">
                <div class="verificacionPost">
                    <? if ($verificado == '1') : ?>
                        <? echo $GLOBALS['check']; ?>
                    <? elseif ($postAut == '1' && current_user_can('administrator')) : ?>
                        <div class="verificarPost" data-post-id="<? echo $post_id; ?>" style="cursor: pointer;">
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
            <? echo renderPostControls($post_id, $colab, $audio_id_lite); // Llamada a la función movida ?>
            <? echo opcionesPost($post_id, $author_id); ?>
        <? endif; ?>
    </div>
<?
}
