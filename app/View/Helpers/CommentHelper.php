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
