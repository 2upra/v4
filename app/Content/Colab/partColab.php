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
    $participantes = get_post_meta($post_id, 'participantes', true);
    $conversacion_id = get_post_meta($post_id, 'conversacion_id', true);

    $imagenPost = get_the_post_thumbnail_url($post_id, 'full');
    if (!$imagenPost) {
        $imagenPost = 'https://i0.wp.com/2upra.com/wp-content/uploads/2024/09/1ndoryu_1725478496.webp?quality=40&strip=all';
    }
    $imagenPostOp = img($imagenPost, 40, 'all');

    $postTitulo = get_the_title($post_id);

    return [
        'post_id' => $post_id,
        'conversacion_id' => $conversacion_id,
        'participantes' => $participantes,
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
        'colabFecha' => get_the_date('', $post_id),
        'colab_status' => get_post_status($post_id),
        'imagenPostOp' => $imagenPostOp,
        'postTitulo' => $postTitulo, // Añadir el título del post
    ];
}




function audioColab($post_id, $audio_id_lite)
{
    $wave = get_post_meta($post_id, 'waveform_image_url', true);
    $waveCargada = get_post_meta($post_id, 'waveCargada', true);
    $urlAudioSegura = audioUrlSegura($audio_id_lite)
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


// Refactor(Exec): Función contenidoColab() movida a app/View/Helpers/ColabHelper.php

// Refactor(Exec): Función tituloColab() movida a app/View/Renderers/ColabRenderer.php

function participantesColab($var)
{
    $post_id = $var['post_id'];
    $colabColaboradorAvatar = $var['colabColaboradorAvatar'];
    $colabAutorAvatar = $var['colabAutorAvatar'];

    ob_start();
?>
    <div class="LIFVXC">
        <img src="<? echo esc_url($colabColaboradorAvatar); ?>">
        <img src="<? echo esc_url($colabAutorAvatar); ?>">
    </div>
<?php
    return ob_get_clean();
}

function opcionesColabActivo($var)
{
    $post_id = $var['post_id'];
    $colabColaborador = $var['colabColaborador'];

    ob_start();
?>
    <button data-post-id="<? echo $post_id; ?>" class="botonsecundario submenucolab"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionescolab-<? echo $post_id; ?>">
        <div class="A1806242">

            <button class="reporte" data-post-id="<? echo $post_id; ?>" tipoContenido="colab">Reportar</button>

        </div>
    </div>
<?php
    return ob_get_clean();
}


// Refactor(Exec): Función opcionesColab() movida a app/View/Helpers/ColabHelper.php
