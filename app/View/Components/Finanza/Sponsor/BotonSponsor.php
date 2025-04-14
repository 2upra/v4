<?php

// Archivo creado para el componente de vista BotonSponsor
// Contendrá la lógica para renderizar el botón de patrocinio.

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class BotonSponsor extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Constructor del componente
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return string
     */
    public function render(): string
    {
        // Refactor(Org): Moved function body from app/Finanza/Sponsor/Boton.php
        ob_start();
        ?>

        <button class="DZYBQD<? if (is_user_logged_in()) echo ' subpro'; ?><? if (!is_user_logged_in()) echo ' boton-sesion'; ?>" id=""><? echo $GLOBALS['iconoCorazon']; ?>Sponsor
        </button>

        <?php
        return ob_get_clean();
    }

    // Aquí se podría añadir la lógica de la función botonSponsor() adaptada al componente.
}
