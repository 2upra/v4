<?php

namespace Kamples\Views\Components;

/**
 * Componentes de vista para búsqueda.
 *
 * @since 1.0.0
 */
class BusquedaComponents
{
    /**
     * Renderiza el buscador local.
     *
     * @return string HTML del buscador
     */
    public static function buscador(): string
    {
        ob_start();
?>
        <div class="buscadorBL bloque" id="buscador-local-container">
            <input name="buscadorLocal" id="buscadorLocal" placeholder="Ingresa tu busqueda">
            <div class="resultadosBL"></div>
        </div>
<?php
        return ob_get_clean();
    }
}
