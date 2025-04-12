<?php

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
