<?php

function variablesColab($post_id = null)
{
    if ($post_id === null) {
        global $post;
        $post_id = $post->ID;
    }

    $current_user_id = get_current_user_id();
    $colabPostOrigen = get_post_meta($post_id, 'colabPostOrigen', true);
    $colabAutor = get_post_meta($post_id, 'colabAutor', true);
    $colabColaborador = get_post_meta($post_id, 'colabColaborador', true);
    $colabMensaje = get_post_meta($post_id, 'colabMensaje', true);
    $colabFileUrl = get_post_meta($post_id, 'colabFileUrl', true);
    $post_audio_lite = get_post_meta($post_id, 'post_audio_lite', true);

    return [
        'post_audio_lite' => $post_audio_lite,
        'current_user_id' => $current_user_id,
        'colabPostOrigen' => $colabPostOrigen,
        'colabAutor' => $colabAutor,
        'colabColaborador' => $colabColaborador,
        'colabMensaje' => $colabMensaje,
        'colabFileUrl' => $colabFileUrl,
        'colabAutorName' => get_the_author_meta('display_name', $colabAutor),
        'colabColaboradorName' => get_the_author_meta('display_name', $colabColaborador),
        'colabColaboradorAvatar' => imagenPerfil($colabColaborador),
        'colabAutorAvatar' => imagenPerfil($colabAutor),
        'colab_date' => get_the_date('', $post_id),
        'colab_status' => get_post_status($post_id),
    ];
}

function audioColab($post_id, $audio_id_lite) {
    $wave = get_post_meta($post_id, 'waveform_image_url', true);
    $waveCargada = get_post_meta($post_id, 'waveCargada', true);
    $urlAudioSegura = audioUrlSegura($audio_id_lite)
    ?>
        <div id="waveform-<?php echo $post_id; ?>"
             class="waveform-container without-image"
             postIDWave="<?php echo $post_id; ?>"
             data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>"
             data-audio-url="<?php echo esc_url($urlAudioSegura); ?>">
            <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
            <div class="waveform-message"></div>
            <div class="waveform-loading" style="display: none;">Cargando...</div>
        </div>
        <?php
}
