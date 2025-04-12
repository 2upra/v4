<?php

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


function contenidoColab($var)
{
    $post_id = $var['post_id'];
    $colabMensaje  = $var['colabMensaje'];
    $post_audio_lite = $var['post_audio_lite'];
    $colabFileUrl = $var['colabFileUrl'];

    ob_start();
?>
    <div class="XZAKCB">

        <div class="BCGWEY">
            <span class="badge ver-contenido" data-post-id="<? echo esc_attr($post_id); ?>">Ver contenido</span>
        </div>
        <div class="colabfiles" id="colabfiles-<? echo esc_attr($post_id); ?>" style="display: none;">
            <? if (!empty($post_audio_lite)) : ?>
                <div class="DNPHZG">
                    <? echo audioColab($post_id, $post_audio_lite); ?>
                </div>
            <? else : ?>
                <div class="AIWZKN">
                    <? if (!empty($colabFileUrl)) : ?>
                        <? $file_name = basename($colabFileUrl); ?>
                        <a href="<? echo esc_url($colabFileUrl); ?>" download class="file-download no-ajax">
                            <div class="XQGSAN">
                                <? echo $GLOBALS['fileGrande']; ?>
                                <? echo esc_html($file_name); ?>
                            </div>
                        </a>
                        <p class="textoMuyPequeno">
                            El archivo ha sido analizado y no se encontraron virus. Sin embargo, si no confías en la persona que realizó la solicitud, no descargues archivos.
                        </p>
                        <p class="mensajeColab">Mensaje de solicitud: <? echo esc_html($colabMensaje); ?></p>
                    <? else : ?>
                        <p>No hay archivo adjunto.</p>
                    <? endif; ?>
                </div>
            <? endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function tituloColab($var)
{
    $post_id = $var['post_id'];
    $imagenPostOp = $var['imagenPostOp'];
    $postTitulo = $var['postTitulo'];
    $colabFecha = $var['colabFecha'];

    ob_start(); ?>

    <div class="MJYQLF">
        <div class="YXJIKK">
            <img src="<? echo esc_url($imagenPostOp) ?>">
        </div>
        <div class="SNVKQC">
            <p><? echo esc_html($postTitulo) ?></p>
            <a href="<? echo esc_url(get_permalink()); ?>" class="post-link">
                <? echo esc_html($colabFecha); ?>
            </a>
        </div>
    </div>

<?php return ob_get_clean();
}

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


// Refactor(Exec): Función opcionesColab() movida a app/View/Helpers/ColabHelper.php
// Refactor(Exec): Función opcionesColabActivo() movida a app/View/Helpers/ColabHelper.php
