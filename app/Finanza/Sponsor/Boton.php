<?

function botonSponsor()
{
    ob_start();
    ?>

    <button class="DZYBQD<? if (is_user_logged_in()) echo ' subpro'; ?><? if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id=""><? echo $GLOBALS['iconoCorazon']; ?>Sponsor
    </button>

    <?
}