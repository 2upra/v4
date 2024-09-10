<?php

function botonComprarAcciones()
{
    ob_start();
?>

    <button class="DZYBQD donar<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id="donarproyecto"><?php echo $GLOBALS['dolar']; ?>Donar
    </button>
<?php
}
