<?php
// Refactor(Org): Función opcionesPost() movida desde app/Content/Posts/View/opcionesPost.php

function opcionesPost($postId, $autorId)
{
    $usuarioActual = get_current_user_id();
    $post_meta = get_post_meta($postId);
    $audio_id_lite = isset($post_meta['post_audio_lite'][0]) ? intval($post_meta['post_audio_lite'][0]) : null;
    $paraDescarga = isset($post_meta['paraDescarga'][0]) ? intval($post_meta['paraDescarga'][0]) : null;
    $post_verificado = isset($post_meta['Verificado'][0]) && $post_meta['Verificado'][0] === '1';
    $esAdmin = current_user_can('administrator');
    $esTarea = get_post_type($postId) === 'tarea';
    $esAutor = ($usuarioActual == $autorId);

    ob_start();
?>
    <button class="HR695R8" data-post-id="<? echo esc_attr($postId); ?>"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionespost-<? echo esc_attr($postId); ?>">
        <div class="A1806242">


            <? if ($esTarea) : ?>
                <? if ($esAutor) : ?>
                    <button class="eliminarPost" data-post-id="<? echo esc_attr($postId); ?>">Eliminar tarea</button>
                <? endif; ?>
            <? else : ?>
                <button class="iralpost"><a ajaxUrl="<? echo esc_url(get_permalink($postId)); ?>">Ir al post</a></button>
                <? if ($esAdmin) : ?>
                    <button class="eliminarPost" data-post-id="<? echo esc_attr($postId); ?>">Eliminar</button>
                    <? echo renderizarBotonDescarga($postId, $usuarioActual, $paraDescarga); ?>
                    <? echo renderizarBotonSincronizar($postId, $usuarioActual, $paraDescarga); ?>
                    <? if (!$post_verificado) : ?>
                        <button class="verificarPost" data-post-id="<? echo esc_attr($postId); ?>">Verificar</button>
                    <? endif; ?>
                    <? if ($audio_id_lite !== 1) : ?>
                        <button class="corregirTags" data-post-id="<? echo esc_attr($postId); ?>">Corrección inteligente</button>
                    <? endif; ?>
                    <button class="editarPost" data-post-id="<? echo esc_attr($postId); ?>">Editar</button>
                    <button class="editarWordPress" data-post-id="<? echo esc_attr($postId); ?>">Editar en WordPress</button>
                    <button class="banearUsuario" data-post-id="<? echo esc_attr($postId); ?>">Banear</button>
                    <? if ($audio_id_lite && $paraDescarga !== 1) : ?>
                        <button class="permitirDescarga" data-post-id="<? echo esc_attr($postId); ?>">Permitir descarga</button>
                    <? endif; ?>
                <? elseif ($esAutor) : ?>
                    <? if ($audio_id_lite !== 1) : ?>
                        <button class="corregirTags" data-post-id="<? echo esc_attr($postId); ?>">Corrección inteligente</button>
                    <? endif; ?>
                    <button class="editarPost" data-post-id="<? echo esc_attr($postId); ?>">Editar</button>
                    <button class="eliminarPost" data-post-id="<? echo esc_attr($postId); ?>">Eliminar</button>
                    <? if ($audio_id_lite && $paraDescarga !== 1) : ?>
                        <button class="permitirDescarga" data-post-id="<? echo esc_attr($postId); ?>">Permitir descarga</button>
                    <? endif; ?>
                <? else : ?>
                    <button class="reporte" data-post-id="<? echo esc_attr($postId); ?>" tipoContenido="social_post">Reportar</button>
                    <button class="bloquear" data-post-id="<? echo esc_attr($postId); ?>">Bloquear</button>
                    <? echo renderizarBotonDescarga($postId, $usuarioActual, $paraDescarga); ?>
                    <? echo renderizarBotonSincronizar($postId, $usuarioActual, $paraDescarga); ?>
                <? endif; ?>
            <? endif; ?>
        </div>
    </div>

    <div id="modalBackground4" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
    <?
    return ob_get_clean();
}

// Refactor(Org): Función opcionesRola() movida desde app/Content/Posts/View/componentPost.php
//OPCIONES EN LAS ROLAS 
function opcionesRola($postId, $post_status, $audio_url)
{
    ob_start();
?>
    <button class="HR695R7" data-post-id="<? echo $postId; ?>"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionesrola-<? echo $postId; ?>">
        <div class="A1806242">
            <? if (current_user_can('administrator') && $post_status != 'publish' && $post_status != 'pending_deletion') { ?>
                <button class="toggle-status-rola" data-post-id="<? echo $postId; ?>">Cambiar estado</button>
            <? } ?>

            <? if (current_user_can('administrator') && $post_status != 'publish' && $post_status != 'rejected' && $post_status != 'pending_deletion') { ?>
                <button class="rechazar-rola" data-post-id="<? echo $postId; ?>">Rechazar rola</button>
            <? } ?>

            <button class="download-button" data-audio-url="<? echo $audio_url; ?>" data-filename="<? echo basename($audio_url); ?>">Descargar</button>

            <? if ($post_status != 'rejected' && $post_status != 'pending_deletion') { ?>
                <? if ($post_status == 'pending') { ?>
                    <button class="request-deletion" data-post-id="<? echo $postId; ?>">Cancelar publicación</button>
                <? } else { ?>
                    <button class="request-deletion" data-post-id="<? echo $postId; ?>">Solicitar eliminación</button>
                <? } ?>
            <? } ?>

        </div>
    </div>

    <div id="modalBackground3" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>

<?
    return ob_get_clean();
}
