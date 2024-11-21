<?

function botonComprarAcciones($textoBoton = 'Comprar acciones')
{
    ob_start();
    ?>

    <button class="DZYBQD donar<? if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id="donarproyecto">
        <? echo $GLOBALS['dolar']; ?><? echo $textoBoton; ?>
    </button>

    <?
    return ob_get_clean();
}

