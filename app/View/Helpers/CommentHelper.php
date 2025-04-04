<?php

// Helper para funciones relacionadas con comentarios

// Función movida desde app/Content/Posts/View/componentPost.php
function botonComentar($postId)
{
    ob_start();
?>

    <div class="RTAWOD">
        <button class="WNLOFT" data-post-id="<? echo $postId; ?>">
            <? echo $GLOBALS['iconocomentario']; ?>
        </button>
    </div>


<?
    return ob_get_clean();
}

// Refactor(Org): Función opcionesComentarios() movida desde app/Content/Posts/View/componentPost.php
function opcionesComentarios($postId, $autorId)
{
    $usuarioActual = get_current_user_id();
    ob_start();
?>
    <button class="submenucomentario" data-post-id="<? echo $postId; ?>"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionescomentarios-<? echo $postId; ?>">
        <div class="A1806242">
            <? if (current_user_can('administrator')) : ?>
                <button class="eliminarPost" data-post-id="<? echo $postId; ?>">Eliminar</button>
                <button class="editarPost" data-post-id="<? echo $postId; ?>">Editar</button>
                <button class="editarWordPress" data-post-id="<? echo $postId; ?>">Editar en WordPress</button>
                <button class="banearUsuario" data-post-id="<? echo $postId; ?>">Banear</button>
            <? elseif ($usuarioActual == $autorId) : ?>
                <button class="editarPost" data-post-id="<? echo $postId; ?>">Editar</button>
                <button class="eliminarPost" data-post-id="<? echo $postId; ?>">Eliminar</button>
            <? else : ?>
                <button class="iralpost"><a ajaxUrl="<? echo esc_url(get_permalink()); ?>">Ir al post</a></button>
                <button class="reporte" data-post-id="<? echo $postId; ?>" tipoContenido="social_post">Reportar</button>
                <button class="bloquear" data-post-id="<? echo $postId; ?>">Bloquear</button>
            <? endif; ?>
        </div>
    </div>

    <div id="modalBackground4" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
<?
    return ob_get_clean();
}
