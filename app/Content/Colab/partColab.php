<?php

// Refactor(Exec): Función variablesColab() movida a app/Services/ColabService.php




// Refactor(Exec): Función audioColab() movida a app/View/Helpers/AudioHelper.php

// Refactor(Exec): Función contenidoColab() movida a app/View/Helpers/ColabHelper.php

// Refactor(Exec): Función tituloColab() movida a app/View/Renderers/ColabRenderer.php

// Refactor(Exec): Función participantesColab() movida a app/View/Helpers/ColabHelper.php

function opcionesColabActivo($var)
{
    $post_id = $var['post_id'];
    $colabColaborador = $var['colabColaborador'];

    ob_start();
?>
    <button data-post-id="<? echo $post_id; ?>" class="botonsecundario submenucolab"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionescolab-<? echo $post_id; ?>">
        <div class="A1806242">

            <button class="reporte" data-post-id="<? echo $post_id; ?>" tipoContenido="colab">Reportar</button>

        </div>
    </div>
<?php
    return ob_get_clean();
}


// Refactor(Exec): Función opcionesColab() movida a app/View/Helpers/ColabHelper.php
