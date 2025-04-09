<?

function htmlColec($filtro)
{
    ob_start();
    $postId = get_the_ID();
    // Refactor(Org): Funcion variablesColec movida a app/Services/CollectionService.php
    // Se asume que CollectionService.php es incluido o la función está disponible globalmente
    // Si no es así, se necesitará incluir el archivo o instanciar el servicio.
    // Por ahora, se llama directamente asumiendo disponibilidad global.
    $vars = variablesColec($postId); 
    extract($vars);
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV no-refresh"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo esc_attr($postId); ?>"
        autor="<? echo esc_attr($autorId); ?>">

        <div class="post-content">
            <? echo imagenColeccion($postId); ?>
            <div class="KLYJBY">
                <? echo audioPost($postId); ?>
            </div>

            <?
            $post_type = get_post_type($postId);
            if ($post_type !== 'social_post') {
            ?>
                <h2 class="post-title" data-post-id="<? echo esc_attr($postId); ?>">
                    <? echo get_the_title($postId); ?>
                </h2>
            <?
            } else {
            ?>
                <div class="LRKHLC">
                    <div class="XOKALG">
                        <?
                        $rola_meta = get_post_meta($postId, 'rola', true);
                        $tienda_meta = get_post_meta($postId, 'tienda', true);
                        $nombre_rola_html = '';

                        if ($rola_meta === '1' || $tienda_meta === '1') {
                            $nombre_rola = get_post_meta($postId, 'nombreRola', true);
                            if (empty($nombre_rola)) {
                                $nombre_rola = get_post_meta($postId, 'nombreRola1', true);
                            }
                            if (empty($nombre_rola)) {
                                $nombre_rola =  get_the_title($postId);
                            }
                            if (!empty($nombre_rola)) {
                                $nombre_rola_html = '<p class="nameRola">' . esc_html($nombre_rola) . '</p>';
                            }
                        }

                        $output .= $nombre_rola_html;

                        echo $output;
                        ?>
                    </div>
                </div>
            <?
            }
            ?>
            <div class="CPQBEN" style="display: none;">

                <? echo like($postId); ?>
                <? echo botonCompra($postId); ?>
                <div class="CPQBAU"><? echo get_the_author_meta('display_name', $autorId); ?></div>
                <div class="CPQBCO">
                    <?

                    $rola_meta = get_post_meta($postId, 'rola', true);
                    $tienda_meta = get_post_meta($postId, 'tienda', true);

                    if ($rola_meta === '1' || $tienda_meta === '1') {
                        $nombre_rola = get_post_meta($postId, 'nombreRola', true);
                        if (empty($nombre_rola)) {
                            $nombre_rola = get_post_meta($postId, 'nombreRola1', true);
                        }
                        if (!empty($nombre_rola)) {
                            echo "<p>" . esc_html($nombre_rola) . "</p>";
                        }
                    }
                    ?>
                </div>
            </div>
            <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>

            <?
            $colecciones_meta = get_post_meta($postId, 'colecciones', true);
            $rola_meta_bottom = get_post_meta($postId, 'rola', true);

            if (!$colecciones_meta && !$rola_meta_bottom) {
                echo botonCompra($postId);
            }
            ?>
        </div>
    </li>
<?
    return ob_get_clean();
}

// Funcion aplanarArray movida a app/Utils/ArrayUtils.php

// Refactor(Org): Funcion datosColeccion movida a app/Services/CollectionService.php

// Funcion maybe_unserialize_dos movida a app/Utils/ArrayUtils.php


// Refactor(Org): Funcion variablesColec movida a app/Services/CollectionService.php


function imagenColeccion($postId)
{
    $imagenSize = 'large';
    $quality = 60;
    // Refactor(Clean): Usa la función centralizada imagenPost() de ImageHelper.php
    $imagenUrl = imagenPost($postId, $imagenSize, $quality, 'all', false, true);
    $imagenProcesada = img($imagenUrl, $quality, 'all');
    $postType = get_post_type($postId);

    ob_start();
?>
    <div class="post-image-container">
        <? if ($postType !== 'social_post') : ?>
            <a href="<? echo esc_url(get_permalink($postId)); ?>" data-post-id="<? echo $postId; ?>" class="imagenColecS">
            <? endif; ?>
            <img class="imagenMusic" src="<? echo esc_url($imagenProcesada); ?>" alt="Post Image" data-post-id="<? echo $postId; ?>" />
            <div class="KLYJBY">
                <? echo audioPost($postId); ?>
            </div>
            <? if ($postType !== 'social_post') : ?>
            </a>
        <? endif; ?>
    </div>
<?

    $output = ob_get_clean();

    return $output;
}

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
        <? echo imagenColeccion($postId); ?>
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
