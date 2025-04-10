<?php

// Función movida desde app/Content/Notas/renderNota.php
function htmlNotas($filtro)
{
    $notaId = get_the_id();
    $contenido = get_the_content();
    $autorId = get_post_field('post_author', $notaId);

    ob_start();
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo $notaId; ?> "
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo $notaId; ?>"
        autor="<? echo esc_attr($autorId); ?>"
        draggable="true">

        <div class="contenidoNota notaPublicada" id-post="<? echo $notaId; ?>">
            <p class="contenidoNotaP"><? echo $contenido; ?></p>
        </div>
        <div class="botonesNotas">
            <button class="editarNota" style="display: none;" data-post-id="<? echo esc_attr($notaId); ?>">
                <? echo $GLOBALS['lapizIcon']; ?>
            </button>
            <button class="eliminarPost" data-post-id="<? echo esc_attr($notaId); ?>">
                <? echo $GLOBALS['papeleraV2']; ?>
            </button>
        </div>
    </li>
<?
    return ob_get_clean();
}
