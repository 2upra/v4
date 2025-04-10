<?

// Refactor(Org): Funcion htmlColec movida a app/View/Renderers/CollectionRenderer.php

// Funcion aplanarArray movida a app/Utils/ArrayUtils.php

// Refactor(Org): Funcion datosColeccion movida a app/Services/CollectionService.php

// Funcion maybe_unserialize_dos movida a app/Utils/ArrayUtils.php


// Refactor(Org): Funcion variablesColec movida a app/Services/CollectionService.php

// Refactor(Org): Funcion imagenColeccion movida a app/View/Helpers/CollectionHelper.php


// Refactor(Clean): Función imagenPost() movida a app/View/Helpers/ImageHelper.php

// Refactor(Org): Funcion singleColec movida a app/View/Renderers/CollectionRenderer.php

function masIdeasColeb($postId)
{
    ob_start()
?>

    <div class="LISTCOLECSIN">
        <? echo publicaciones(['post_type' => 'social_post', 'filtro' => 'sampleList', 'posts' => 12, 'colec' => $postId, 'idea' => true]);  ?>
    </div>

<?
    return ob_get_clean();
}

// Refactor(Org): Funcion opcionesColec movida a app/View/Helpers/CollectionHelper.php
