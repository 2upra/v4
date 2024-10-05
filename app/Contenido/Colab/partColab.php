<?

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

    $imagenPost = get_the_post_thumbnail_url($post_id, 'full');
    if (!$imagenPost) {
        $imagenPost = 'https://i0.wp.com/2upra.com/wp-content/uploads/2024/09/1ndoryu_1725478496.webp?quality=40&strip=all';
    }
    $imagenPostOp = img($imagenPost, 40, 'all');

    $postTitulo = get_the_title($post_id);

    return [
        'post_id' => $post_id,
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
<?
}

function opcionesColab($var)
{
    $post_id = $var['post_id'];
    $colabColaborador = $var['colabColaborador'];
    $colabColaboradorAvatar = $var['colabColaboradorAvatar'];
    $colabColaboradorName = $var['colabColaboradorName'];
    $colabFecha = $var['colabFecha'];
    ob_start();
?>
    <div class="GFOPNU">

        <div class="CBZNGK">
            <a href="<? echo esc_url(get_author_posts_url($colabColaborador)); ?>"></a>
            <img src="<? echo esc_url($colabColaboradorAvatar); ?>">
        </div>

        <div class="ZVJVZA">
            <div class="JHVSFW">
                <a href="<? echo esc_url(get_author_posts_url($colabColaborador)); ?>" class="profile-link">
                    <? echo esc_html($colabColaboradorName); ?></a>
            </div>
            <div class="HQLXWD">
                <a href="<? echo esc_url(get_permalink()); ?>" class="post-link">
                    <? echo esc_html($colabFecha); ?>
                </a>
            </div>
        </div>

        <div class="flex gap-3 justify-end ml-auto">

            <button data-post-id="<? echo $post_id; ?>" class="botonsecundario rechazarcolab">Rechazar</button>
            <button data-post-id="<? echo $post_id; ?>" class="botonprincipal aceptarcolab">Aceptar</button>
            <button data-post-id="<? echo $post_id; ?>" class="botonsecundario submenucolab"><? echo $GLOBALS['iconotrespuntos']; ?></button>
        </div>

        <div class="A1806241" id="opcionescolab-<? echo $post_id; ?>">
            <div class="A1806242">

                <button class="reporte" data-post-id="<? echo $post_id; ?>" tipoContenido="colab">Reportar</button>
                <button class="bloquear" data-post-id="<? echo $post_id; ?>">Bloquear</button>
                <button class="mensajeBoton" data-receptor="<? echo $colabColaborador; ?>">Enviar mensaje</button>

            </div>
        </div>
    </div>
<?
    return ob_get_clean();
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
<?
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

<? return ob_get_clean();
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
<?
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
<?
    return ob_get_clean();
}

function chatColab($var) {
    $post_id = intval($var['post_id']);
    ob_start();
?>
    <div class="borde bloqueChatColab" id="chatcolab-<?php echo esc_attr($post_id); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
        <ul class="listaMensajes"></ul>
    </div>
<?php
    return ob_get_clean();
}

