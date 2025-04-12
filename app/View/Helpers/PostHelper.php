<?php

// Refactor(Org): Función infoPost() movida desde app/Content/Posts/View/componentPost.php
function infoPost($autId, $autAv, $autNom, $postF, $postId, $block, $colab)
{
    $postAut = get_post_meta($postId, 'postAut', true);
    $ultEd = get_post_meta($postId, 'ultimoEdit', true);
    $verif = get_post_meta($postId, 'Verificado', true);
    $rec = get_post_meta($postId, 'recortado', true);
    $usrAct = (int)get_current_user_id();
    $autId = (int)$autId;
    $esUsrAct = ($usrAct === $autId);

    ob_start();
?>
    <div class="SOVHBY <? echo ($esUsrAct ? 'miContenido' : ''); ?>">
        <div class="CBZNGK">
            <a href="<? echo esc_url(get_author_posts_url($autId)); ?>"> </a>
            <img src="<? echo esc_url($autAv); ?>">
            <? echo botonseguir($autId); ?>
        </div>
        <div class="ZVJVZA">
            <div class="JHVSFW">
                <a href="<? echo esc_url(home_url('/perfil/' .  get_the_author_meta('user_nicename', $autId))); ?>" class="profile-link">
                    <? echo esc_html($autNom); ?>
                    <? if (get_user_meta($autId, 'pro', true) || user_can($autId, 'administrator') || get_user_meta($autId, 'Verificado', true)) : ?>
                        <? echo $GLOBALS['verificado']; ?>
                    <? endif; ?>
                </a>
            </div>
            <div class="HQLXWD">
                <a href="<? echo esc_url(get_permalink()); ?>" class="post-link">
                    <? echo esc_html($postF); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="verificacionPost">
        <? if ($verif == '1') : ?>
            <? echo $GLOBALS['check']; ?>
        <? elseif ($postAut == '1' && current_user_can('administrator')) : ?>
            <? echo $GLOBALS['robot']; ?>
        <? endif; ?>
    </div>

    <div class="OFVWLS">
        <? if ($rec) : ?>
            <div><? echo "Preview"; ?></div>
        <? endif; ?>
        <? if ($block) : ?>
            <div><? echo "Exclusivo"; ?></div>
        <? elseif ($colab) : ?>
            <div><? echo "Colab"; ?></div>
        <? endif; ?>
    </div>

    <div class="spin"></div>

    <div class="YBZGPB">
        <? // Refactor(Org): Función opcionesPost() movida a app/View/Components/Posts/PostOptions.php
        // La llamada original se mantiene aquí, pero la función ahora reside en otro archivo.
        // Es posible que se necesiten ajustes posteriores para que esta llamada funcione correctamente.
        echo opcionesPost($postId, $autId); ?>
    </div>
<?
    return ob_get_clean();
}

// Refactor(Org): Función opcionesPost() movida a app/View/Components/Posts/PostOptions.php

// Refactor(Org): Función nohayPost movida desde app/Content/Posts/View/renderPost.php


// Refactor(Org): Mueve función renderPostControls() de renderPost.php a PostHelper.php
function renderPostControls($post_id, $colab, $audio_id_lite = null)
{

    $mostrarBotonCompra = get_post_meta($post_id, 'tienda', true) === '1';
?>
    <div class="QSORIW">


        <? echo like($post_id); ?>
        <? if ($mostrarBotonCompra):
            echo botonCompra($post_id); ?>
        <? endif; ?>
        <? echo botonComentar($post_id, $colab); ?>
        <? if (!empty($audio_id_lite)) : ?>
            <? echo botonDescarga($post_id); ?>
            <? echo botonColab($post_id, $colab); ?>
            <? echo botonColeccion($post_id); ?>
        <? endif; ?>
    </div>
<?
}

// Refactor(Org): Función imagenPostList() movida desde app/Content/Posts/View/componentPost.php
//MOSTRAR IMAGEN
function imagenPostList($block, $es_suscriptor, $postId)
{
    $blurred_class = ($block && !$es_suscriptor) ? 'blurred' : '';

    if ($block && !$es_suscriptor) {
        $image_size = 'thumbnail';
        $quality = 20;
    } else {
        $image_size = 'thumbnail';
        $quality = 20;
    }

    // Refactor(Clean): Usa la función centralizada imagenPost() de ImageHelper.php
    $image_url = imagenPost($postId, $image_size, $quality, 'all', ($block && !$es_suscriptor), true);

    $processed_image_url = img($image_url, $quality, 'all');

    ob_start();
?>
    <div class="post-image-container <?= esc_attr($blurred_class) ?>">
        <a>
            <img src="<?= esc_url($processed_image_url); ?>" alt="Post Image" />
        </a>
        <div class="botonesRep">
            <div class="reproducirSL" id-post="<?php echo $postId; ?>"><?php echo $GLOBALS['play']; ?></div>
            <div class="pausaSL" id-post="<?php echo $postId; ?>"><?php echo $GLOBALS['pause']; ?></div>
        </div>
    </div>
<?php

    $output = ob_get_clean();

    return $output;
}

// Refactor(Org): Función fondoPost() movida desde app/Content/Posts/View/componentPost.php
function fondoPost($filtro, $block, $es_suscriptor, $postId)
{
    // Refactor(Clean): Usa la función centralizada imagenPost() de ImageHelper.php
    $thumbnail_url = imagenPost($postId, 'full', 80, 'all', false, true); // Calidad 80 para fondo

    $blurred_class = ($block && !$es_suscriptor) ? 'blurred' : '';
    // Optimización adicional para el fondo si es necesario
    $optimized_thumbnail_url = img($thumbnail_url, 40, 'all');

    ob_start();
?>
    <div class="post-background <?= $blurred_class ?>"
        style="background-image: linear-gradient(to top, rgba(9, 9, 9, 10), rgba(0, 0, 0, 0) 100%), url(<?php echo esc_url($optimized_thumbnail_url); ?>);">
    </div>
<?php
    $output = ob_get_clean();
    return $output;
}


// Refactor(Exec): Función opcionesRola() movida desde app/Services/Post/PostAttachmentService.php a app/View/Helpers/PostHelper.php
// // OPCIONES EN LAS ROLAS 
function opcionesRola($postId, $post_status, $audio_url)
{
    ob_start();
?>
    <button class="HR695R7" data-post-id="<? echo $postId; ?>"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionesrola-<? echo $postId; ?>">
        <div class="A1806242">
            <? if (current_user_can('administrator') && $post_status != 'publish' && $post_status != 'pending_deletion') { ?>
                <button class="toggle-status-rola" data-post-id="<? echo $postId; ?>">Cambiar estado</button>
            <? } ?>

            <? if (current_user_can('administrator') && $post_status != 'publish' && $post_status != 'rejected' && $post_status != 'pending_deletion') { ?>
                <button class="rechazar-rola" data-post-id="<? echo $postId; ?>">Rechazar rola</button>
            <? } ?>

            <button class="download-button" data-audio-url="<? echo $audio_url; ?>" data-filename="<? echo basename($audio_url); ?>">Descargar</button>

            <? if ($post_status != 'rejected' && $post_status != 'pending_deletion') { ?>
                <? if ($post_status == 'pending') { ?>
                    <button class="request-deletion" data-post-id="<? echo $postId; ?>">Cancelar publicación</button>
                <? } else { ?>
                    <button class="request-deletion" data-post-id="<? echo $postId; ?>">Solicitar eliminación</button>
                <? } ?>
            <? } ?>

        </div>
    </div>

    <div id="modalBackground3" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>

<?
    return ob_get_clean();
}
