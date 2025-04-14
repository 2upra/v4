<?php

// Archivo creado para componente de botón de compra de acciones
// Contiene la función botonComprarAcciones() para renderizar el botón de compra de acciones.

// Refactor(Org): Moved function botonComprarAcciones from app/Finanza/ComprarAcciones/BotonComprar.php
if (!function_exists('botonComprarAcciones')) {
    /**
     * Renderiza el botón de compra de acciones.
     *
     * @param string $textoBoton Texto del botón. Por defecto 'Donar'.
     * @return string HTML del botón
     */
    function botonComprarAcciones($textoBoton = 'Donar')
    {
        ob_start();
        ?>

        <button class="DZYBQD donar<? if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id="donarproyecto">
            <? echo $GLOBALS['dolar']; ?><? echo $textoBoton; ?>
        </button>

        <?
        return ob_get_clean();
    }
}

?>