<?php

/**
 * Helper para generar elementos HTML relacionados con el audio, como el reproductor.
 */

/**
 * Genera el HTML para el reproductor de audio global que se muestra en el footer.
 * Este reproductor se controla mediante JavaScript para reproducir las pistas seleccionadas.
 */
function reproductor()
{
?>

    <div class="TMLIWT" style="display: none;">

        <audio class="GSJJHK" style="display:none;"></audio>
        <div class="GPFFDR">

            <div class="CMJUXB">
                <div class="progress-container">
                    <div class="progress-bar"></div>
                </div>
            </div>

            <div class="CMJUXC">
                <div class="HOYBKW">
                    <img class="LWXUER">
                </div>
                <div class="XKPMGD">
                    <p class="tituloR"></p>
                    <p class="AutorR"></p>
                </div>
                <div class="SOMGMR">
            
                </div>
                <div class="PQWXDA">
                    <button class="prev-btn">
                        <?php echo $GLOBALS['anterior']; ?>
                    </button>
                    <button class="play-btn">
                        <?php echo $GLOBALS['play']; ?>
                    </button>
                    <button class="pause-btn" style="display: none;">
                        <?php echo $GLOBALS['pause']; ?>
                    </button>
                    <button class="next-btn">
                        <?php echo $GLOBALS['siguiente']; ?>
                    </button>
                    <div class="BSUXDA">
                        <button class="JMFCAI">
                            <?php echo $GLOBALS['volumen']; ?>
                        </button>
                        <div class="TGXRDF">
                            <input type="range" class="volume-control" min="0" max="1" step="0.01" value="1">
                        </div>
                    </div>
                    <button class="PCNLEZ">
                        <?php echo $GLOBALS['cancelicon']; ?>
                    </button>
                </div>

            </div>

        </div>
    </div>
<?php

}

// Refactor(Org): Función audioPost() movida desde app/Content/Posts/View/componentPost.php
function audioPost($postId)
{
    $audio_id_lite = get_post_meta($postId, 'post_audio_lite', true);

    if (empty($audio_id_lite)) {
        return '';
    }

    $post_author_id = get_post_field('post_author', $postId);
    $urlAudioSegura = audioUrlSegura($audio_id_lite);

    ob_start();
?>
    <div id="audio-container-<?php echo $postId; ?>" class="audio-container" data-post-id="<?php echo $postId; ?>" artista-id="<?php echo $post_author_id; ?>">

        <div class="play-pause-sobre-imagen">
            <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;">
        </div>

        <audio id="audio-<?php echo $postId; ?>" src="<?php echo esc_url($urlAudioSegura); ?>"></audio>
    </div>
<?php
    return ob_get_clean();
}

// Acción no realizada: La función reproductor() y su hook ya se encuentran en este archivo (AudioHelper.php).
// No se encontraron en el archivo de origen especificado (reproductor.php).
add_action('wp_footer', 'reproductor');

// Refactor(Exec): Función audioColab() movida desde app/Content/Colab/partColab.php
function audioColab($post_id, $audio_id_lite)
{
    $wave = get_post_meta($post_id, 'waveform_image_url', true);
    $waveCargada = get_post_meta($post_id, 'waveCargada', true);
    $urlAudioSegura = audioUrlSegura($audio_id_lite);
?>
    <div id="waveform-<? echo $post_id; ?>"
        class="waveform-container without-image"
        postIDWave="<? echo $post_id; ?>"
        data-wave-cargada="<? echo $waveCargada ? 'true' : 'false'; ?>"
        data-audio-url="<? echo esc_url($urlAudioSegura); ?>">
        <div class="waveform-background" style="background-image: url('<? echo esc_url($wave); ?>');"></div>
        <div class="waveform-message"></div>
        <div class="waveform-loading" style="display: none;">Cargando...</div>
    </div>
<?php
}
