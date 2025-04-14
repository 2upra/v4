<?
// Funcion movida desde app/Finanza/Sponsor/Boton.php

function botonSponsor()
{
    ob_start();
    ?>

    <button class="DZYBQD<? if (is_user_logged_in()) echo ' subpro'; ?><? if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id=""><? echo $GLOBALS['iconoCorazon']; ?>Sponsor
    </button>

    <?
    // Nota: La funcion original no cerraba el ob_start() ni retornaba el buffer.
    // Se mantiene como estaba en el origen.
}
