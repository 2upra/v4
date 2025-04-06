<?php

// Refactor(Org): Función AJAX buscar_resultados() y sus hooks movidos a app/Services/SearchService.php

// Refactor(Org): Funciones realizar_busqueda, buscar_posts, buscar_usuarios, balancear_resultados y obtenerImagenPost movidas a app/Services/SearchService.php
// Nota: Las definiciones de las funciones ya no están aquí, fueron movidas previamente.

// Refactor(Exec): Función generar_html_resultados() movida a app/Services/SearchService.php


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
