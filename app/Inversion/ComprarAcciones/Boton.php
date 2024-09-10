<?php

function botonComprarAcciones($textoBoton = 'Donar')
{
    ob_start();
    ?>

    <button class="DZYBQD donar<?php if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id="donarproyecto">
        <?php echo $GLOBALS['dolar']; ?><?php echo $textoBoton; ?>
    </button>

    <?php
    return ob_get_clean();
}

