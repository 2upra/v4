<?php

// Refactor(Org): Mueve función botonColeccion() de UIHelper a CollectionHelper
function botonColeccion($postId)
{

    $extraClass = '';
    if (is_user_logged_in()) {
        $userId = get_current_user_id();
        $coleccion = get_user_meta($userId, 'samplesGuardados', true);
        if (is_array($coleccion) && isset($coleccion[$postId])) {
            $extraClass = ' colabGuardado';
        }
    }

    ob_start();
?>
    <div class="ZAQIBB botonColeccion<? echo esc_attr($extraClass); ?>">
        <button class="botonColeccionBtn" aria-label="Guardar sonido" data-post_id="<? echo esc_attr($postId); ?>" data-nonce="<? echo wp_create_nonce('colec_nonce'); ?>">
            <? echo isset($GLOBALS['iconoGuardar']) ? $GLOBALS['iconoGuardar'] : ''; // Verifica si $GLOBALS['iconoGuardar'] está definida 
            ?>
        </button>
    </div>
<?
    return ob_get_clean();
}

// Refactor(Org): Funcion opcionesColec movida desde app/Content/Colecciones/View/renderPostColec.php
function opcionesColec($postId, $autorId)
{
    $usuarioActual = get_current_user_id();
    $post_verificado = get_post_meta($postId, 'Verificado', true);
    ob_start();
?>
    <button class="HR695R8" data-post-id="<? echo $postId; ?>"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionespost-<? echo $postId; ?>">
        <div class="A1806242">
            <? if (current_user_can('administrator')) : ?>
                <button class="eliminarPost" data-post-id="<? echo $postId; ?>">Eliminar</button>
                <button class="cambiarTitulo" data-post-id="<? echo $postId; ?>">Cambiar titulo</button>
                <button class="cambiarImagen" data-post-id="<? echo $postId; ?>">Cambiar imagen</button>
                <? if (!$post_verificado) : ?>
                    <button class="verificarPost" data-post-id="<? echo $postId; ?>">Verificar</button>
                <? endif; ?>
                <button class="editarWordPress" data-post-id="<? echo $postId; ?>">Editar en WordPress</button>
                <button class="banearUsuario" data-post-id="<? echo $postId; ?>">Banear</button>
            <? elseif ($usuarioActual == $autorId) : ?>
                <button class="eliminarPost" data-post-id="<? echo $postId; ?>">Eliminar</button>
                <button class="cambiarImagen" data-post-id="<? echo $postId; ?>">Cambiar Imagen</button>
            <? else : ?>
                <button class="reporte" data-post-id="<? echo $postId; ?>" tipoContenido="social_post">Reportar</button>
                <button class="bloquear" data-post-id="<? echo $postId; ?>">Bloquear</button>
            <? endif; ?>
        </div>
    </div>

    <div id="modalBackground4" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
<?
    return ob_get_clean();
}

// Refactor(Org): Funcion imagenColeccion movida desde app/Content/Colecciones/View/renderPostColec.php
function imagenColeccion($postId)
{
    $imagenSize = 'large';
    $quality = 60;
    // Refactor(Clean): Usa la función centralizada imagenPost() de ImageHelper.php
    // Asume que ImageHelper.php está cargado globalmente
    $imagenUrl = imagenPost($postId, $imagenSize, $quality, 'all', false, true);
    // Asume que img() de ImageUtils.php está cargado globalmente
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
                <? // Asume que audioPost() de AudioHelper.php está cargado globalmente
                   echo audioPost($postId); ?>
            </div>
            <? if ($postType !== 'social_post') : ?>
            </a>
        <? endif; ?>
    </div>
<?

    $output = ob_get_clean();

    return $output;
}
