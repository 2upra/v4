<?php

/**
 * Helper para generar elementos HTML relacionados con el audio, como el reproductor.
 */

// Refactor(Org): Función reproductor() movida a app/View/Partials/GlobalAudioPlayer.php

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

// Refactor(Org): Función wave() y generate_wave_html() movidas desde app/Content/Posts/View/componentPost.php
function wave($audio_url, $audio_id_lite, $postId)
{
    $wave = get_post_meta($postId, 'waveform_image_url', true);

    // Contar la cantidad de audios disponibles
    $audio_count = 0;
    $audio_urls = array();

    // Cargar la URL para post_audio_lite
    $audio_url_lite = get_post_meta($postId, 'post_audio_lite', true);
    if (!empty($audio_url_lite)) {
        $audio_count++;
        $audio_urls['post_audio_lite'] = $audio_url_lite;
    }

    // Cargar las URLs para post_audio_lite_2, post_audio_lite_3, ..., post_audio_lite_30
    for ($i = 2; $i <= 30; $i++) {
        $meta_key = 'post_audio_lite_' . $i;
        $audio_url_multiple = get_post_meta($postId, $meta_key, true);

        if (!empty($audio_url_multiple)) {
            $audio_count++;
            $audio_urls[$meta_key] = $audio_url_multiple;
        }
    }
?>
    <div class="waveforms-container-post" id="waveforms-container-<?php echo $postId; ?>" data-post-id="<?php echo esc_attr($postId); ?>">
        <?php
        // Mostrar los botones solo si hay más de un audio
        if ($audio_count > 1) : ?>
            <div class="botonesWave">
                <button class="prevWave" data-post-id="<?php echo esc_attr($postId); ?>">Anterior</button>
                <button class="nextWave" data-post-id="<?php echo esc_attr($postId); ?>">Siguiente</button>
            </div>
        <?php endif; ?>
        <?php
        // Generar el HTML para cada audio
        $index = 0;
        foreach ($audio_urls as $meta_key => $audio_url) {
            generate_wave_html($audio_url, $audio_id_lite, $postId, $meta_key, $wave, $index);
            $index++;
        }
        ?>
    </div>
<?php
}

function generate_wave_html($audio_url, $audio_id_lite, $postId, $meta_key, $wave, $index)
{

    $waveCargada = get_post_meta($postId, 'waveCargada_' . $meta_key, true); // Wave cargada para cada audio
    $urlAudioSegura = audioUrlSegura($audio_url);
    $unique_id = $postId . '-' . $meta_key; // ID único para cada waveform

    if (is_wp_error($urlAudioSegura)) {
        $urlAudioSegura = '';
    }
?>
    <div id="waveform-<?php echo $unique_id; ?>"
        class="waveform-container without-image"
        postIDWave="<?php echo $unique_id; ?>"
        data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>"
        data-audio-url="<?php echo esc_url($urlAudioSegura); ?>">
        <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
        <div class="waveform-message"></div>
        <div class="waveform-loading" style="display: none;">Cargando...</div>
    </div>
<?php
}

// Refactor(Org): Mueve función audioPostList() desde componentPost.php
function audioPostList($postId)
{
    $audio_id_lite = get_post_meta($postId, 'post_audio_lite', true);

    if (empty($audio_id_lite)) {
        return '';
    }
    $urlAudioSegura = audioUrlSegura($audio_id_lite);
    $post_author_id = get_post_field('post_author', $postId);
    if (is_wp_error($urlAudioSegura)) {
        $urlAudioSegura = ''; // O establece un valor predeterminado o maneja el error de forma diferente
    }
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
