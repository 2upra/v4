<?

// Refactor(Org): Funcion htmlColec movida a app/View/Renderers/CollectionRenderer.php

// Funcion aplanarArray movida a app/Utils/ArrayUtils.php

// Refactor(Org): Funcion datosColeccion movida a app/Services/CollectionService.php

// Funcion maybe_unserialize_dos movida a app/Utils/ArrayUtils.php


// Refactor(Org): Funcion variablesColec movida a app/Services/CollectionService.php

// Refactor(Org): Funcion imagenColeccion movida a app/View/Helpers/CollectionHelper.php


// Refactor(Clean): Función imagenPost() movida a app/View/Helpers/ImageHelper.php

function singleColec($postId)
{
    // Refactor(Org): Funcion variablesColec movida a app/Services/CollectionService.php
    // Se asume que CollectionService.php es incluido o la función está disponible globalmente.
    $vars = variablesColec($postId);
    extract($vars);
    ob_start()
?>
    <div class="AMORP">
        <? echo imagenColeccion($postId); // Llamada a la función movida a CollectionHelper.php ?>
        <div class="ORGDE">

            <div class="AGDEORF">
                <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>
                <h2 class="tituloColec" data-post-id="<? echo $postId; ?>"><? echo get_the_title($postId); ?></h2>
                <div class="DSEDBE">
                    <? echo $samples ?>
                </div>
                <div class="BOTONESCOLEC">
                    <? echo botonDescargaColec($postId, $sampleCount); ?>
                    <? echo botonSincronizarColec($postId, $sampleCount); ?>
                    <? echo like($postId); ?>
                    <? // Refactor(Org): Funcion opcionesColec movida a app/View/Helpers/CollectionHelper.php
                       // Se asume que CollectionHelper.php es incluido o la función está disponible globalmente.
                       // Si no es así, se necesitará incluir el archivo.
                       // Por ahora, se llama directamente asumiendo disponibilidad global.
                       echo opcionesColec($postId, $autorId); ?>
                </div>
            </div>

            <div class="INFEIS">
                <? echo datosColeccion($postId); // ADVERTENCIA: Esta función fue movida a CollectionService.php y esta llamada puede fallar. ?>
                <div class="tags-container-colec" id="tags-<? echo get_the_ID(); ?>"></div>

                <p id="dataColec" id-post-algoritmo="<? echo get_the_ID(); ?>" style="display:none;">
                    <? echo esc_html(limpiarJSON($datosColeccion)); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="LISTCOLECSIN">
        <? echo publicaciones(['post_type' => 'social_post', 'filtro' => 'sampleList', 'posts' => 12, 'colec' => $postId]); ?>
    </div>

<?
    return ob_get_clean();
}

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
