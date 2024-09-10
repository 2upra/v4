<?php

function botonSponsor()
{
    ob_start();
?>

    <button class="DZYBQD subpro<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id=""><?php echo $GLOBALS['iconoCorazon']; ?>Sponsor
    </button>
<?php
}
