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

function audioColab($post_id, $audio_id_lite)
{
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

function opcionesColab($post_id, $colabColaborador, $colabColaboradorAvatar, $colabColaboradorName, $colab_date)
{
    ob_start();
?>
    <div class="GFOPNU">
        <div class="CBZNGK">
            <a href="<?php echo esc_url(get_author_posts_url($colabColaborador)); ?>"></a>
            <img src="<?php echo esc_url($colabColaboradorAvatar); ?>">
        </div>

        <div class="ZVJVZA">
            <div class="JHVSFW">
                <a href="<?php echo esc_url(get_author_posts_url($colabColaborador)); ?>" class="profile-link">
                    <?php echo esc_html($colabColaboradorName); ?></a>
            </div>
            <div class="HQLXWD">
                <a href="<?php echo esc_url(get_permalink()); ?>" class="post-link"><?php echo esc_html($colab_date); ?></a>
            </div>
        </div>

        <div class="flex gap-3 justify-end ml-auto">
            
            <button data-post-id="<?php echo $post_id; ?>" class="botonsecundario rechazarcolab">Rechazar</button>
            <button data-post-id="<?php echo $post_id; ?>" class="botonprincipal aceptarcolab">Aceptar</button>
            <button data-post-id="<?php echo $post_id; ?>" class="botonsecundario submenucolab"><?php echo $GLOBALS['iconotrespuntos']; ?></button>
        </div>

        <div class="A1806241" id="opcionescolab-<?php echo $post_id; ?>">
            <div class="A1806242">

                <button class="reportarColab" data-post-id="<?php echo $post_id; ?>">Reportar</button>
                <button class="bloquearColab" data-post-id="<?php echo $post_id; ?>">Bloquear</button>
                <button class="enviarMensajeColab" data-post-id="<?php echo $post_id; ?>">Enviar Mensaje</button>


            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function contenidoColab($post_id, $colabMensaje, $post_audio_lite, $colabFileUrl)
{
    ob_start();
?>
    <div class="XZAKCB">
        <p>Mensaje de solicitud: <?php echo esc_html($colabMensaje); ?></p>
        <div class="BCGWEY">
            <span class="badge ver-contenido" data-post-id="<?php echo esc_attr($post_id); ?>">Ver contenido</span>
        </div>
        <div class="colabfiles" id="colabfiles-<?php echo esc_attr($post_id); ?>" style="display: none;">
            <?php if (!empty($post_audio_lite)) : ?>
                <div class="DNPHZG">
                    <?php echo audioColab($post_id, $post_audio_lite); ?>
                </div>
            <?php else : ?>
                <div class="AIWZKN">
                    <?php if (!empty($colabFileUrl)) : ?>
                        <?php $file_name = basename($colabFileUrl); ?>
                        <a href="<?php echo esc_url($colabFileUrl); ?>" download class="file-download no-ajax">
                            <div class="XQGSAN">
                                <?php echo $GLOBALS['fileGrande']; ?>
                                <?php echo esc_html($file_name); ?>
                            </div>
                        </a>
                        <p class="textoMuyPequeno">
                            El archivo ha sido analizado y no se encontraron virus. Sin embargo, si no confías en la persona que realizó la solicitud, no descargues archivos. Asegúrate de mantener siempre tu sistema operativo actualizado y reporta cualquier abuso.
                        </p>
                    <?php else : ?>
                        <p>No hay archivo adjunto.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
