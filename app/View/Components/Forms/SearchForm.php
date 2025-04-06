<?php

// Refactor(Exec): Función busqueda() movida desde app/Content/Logic/busqueda.php

/**
 * Genera el HTML para el formulario de búsqueda local.
 *
 * @return string HTML del formulario.
 */
function busqueda()
{
    ob_start();
    ?>
    <div class="buscadorBL bloque">
        <input name="buscadorLocal" id="buscadorLocal" placeholder="Ingresa tu busqueda"></input>

        <div class="resultadosBL"></div>
    </div>
    <?php
    return ob_get_clean();
}

?>
