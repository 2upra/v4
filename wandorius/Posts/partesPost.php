<?php

//VARIABLES 
function variablesPosts($post_id = null)
{
    if ($post_id === null) {
        global $post;
        $post_id = $post->ID;
    }

    $current_user_id = get_current_user_id();
    $autores_suscritos = get_user_meta($current_user_id, 'offering_user_ids', true);
    $author_id = get_post_field('post_author', $post_id);

    return [
        'current_user_id' => $current_user_id,
        'autores_suscritos' => $autores_suscritos,
        'author_id' => $author_id,
        'es_suscriptor' => in_array($author_id, (array)$autores_suscritos),
        'author_name' => get_the_author_meta('display_name', $author_id),
        'author_avatar' => obtener_url_imagen_perfil_o_defecto($author_id),
        'audio_id_lite' => get_post_meta($post_id, 'post_audio_lite', true),
        'audio_id' => get_post_meta($post_id, 'post_audio', true),
        'audio_url' => wp_get_attachment_url(get_post_meta($post_id, 'post_audio', true)),
        'audio_lite' => wp_get_attachment_url(get_post_meta($post_id, 'post_audio_lite', true)),
        'wave' => get_post_meta($post_id, 'waveform_image_url', true),
        'post_date' => get_the_date('', $post_id),
        'block' => get_post_meta($post_id, 'content-block', true),
        'colab' => get_post_meta($post_id, 'para_colab', true),
        'post_status' => get_post_status($post_id)
    ];
}

//BOTON DE SEGUIR 

function botonseguir($author_id)
{
    $current_user_id = get_current_user_id();

    if ($current_user_id === 0 || $current_user_id === $author_id) {
        return '';
    }

    $siguiendo = get_user_meta($current_user_id, 'siguiendo', true);
    $es_seguido = is_array($siguiendo) && in_array($author_id, $siguiendo);

    $clase_boton = $es_seguido ? 'dejar-de-seguir' : 'seguir';
    $icono_boton = $es_seguido ? $GLOBALS['iconorestar'] : $GLOBALS['iconosumar'];

    ob_start();
?>
    <button class="<?php echo esc_attr($clase_boton); ?>"
        data-seguidor-id="<?php echo esc_attr($current_user_id); ?>"
        data-seguido-id="<?php echo esc_attr($author_id); ?>">
        <?php echo $icono_boton; ?>
    </button>
<?php
    return ob_get_clean();
}


//OPCIONES EN LAS ROLAS 
function opcionesRola($post_id, $post_status, $audio_url)
{
    ob_start();
?>
    <button class="HR695R7" data-post-id="<?php echo $post_id; ?>"><?php echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionesrola-<?php echo $post_id; ?>">
        <div class="A1806242">
            <?php if (current_user_can('administrator') && $post_status != 'publish' && $post_status != 'pending_deletion') { ?>
                <button class="toggle-status-rola" data-post-id="<?php echo $post_id; ?>">Cambiar estado</button>
            <?php } ?>

            <?php if (current_user_can('administrator') && $post_status != 'publish' && $post_status != 'rejected' && $post_status != 'pending_deletion') { ?>
                <button class="rechazar-rola" data-post-id="<?php echo $post_id; ?>">Rechazar rola</button>
            <?php } ?>

            <button class="download-button" data-audio-url="<?php echo $audio_url; ?>" data-filename="<?php echo basename($audio_url); ?>">Descargar</button>

            <?php if ($post_status != 'rejected' && $post_status != 'pending_deletion') { ?>
                <?php if ($post_status == 'pending') { ?>
                    <button class="request-deletion" data-post-id="<?php echo $post_id; ?>">Cancelar publicación</button>
                <?php } else { ?>
                    <button class="request-deletion" data-post-id="<?php echo $post_id; ?>">Solicitar eliminación</button>
                <?php } ?>
            <?php } ?>

        </div>
    </div>

    <div id="modalBackground3" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>

<?php
    return ob_get_clean();
}

//OPCIONES EN LOS POST
function opcionesPost($post_id, $author_id)
{
    $current_user_id = get_current_user_id();
    ob_start();
?>
    <button class="HR695R8" data-post-id="<?php echo $post_id; ?>"><?php echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionespost-<?php echo $post_id; ?>">
        <div class="A1806242">
            <?php if (current_user_can('administrator')) : ?>
                <button class="eliminarPost" data-post-id="<?php echo $post_id; ?>">Eliminar</button>
            <?php elseif ($current_user_id == $author_id) : ?>
                <button class="eliminarPost" data-post-id="<?php echo $post_id; ?>">Eliminar</button>
            <?php endif; ?>

            <button class="reportarPost" data-post-id="<?php echo $post_id; ?>">Reportar</button>

            <?php if (current_user_can('administrator')) : ?>
                <button class="banearUsuario" data-post-id="<?php echo $post_id; ?>">Banear</button>
            <?php endif; ?>
        </div>
    </div>

    <div id="modalBackground4" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
<?php
    return ob_get_clean();
}




//MOSTRAR IMAGEN
function imagenPost($post_id, $size = 'medium', $quality = 50, $strip = 'all', $pixelated = false)
{
    $post_thumbnail_id = get_post_thumbnail_id($post_id);

    if (function_exists('jetpack_photon_url')) {
        $url = wp_get_attachment_image_url($post_thumbnail_id, $size);
        $args = array('quality' => $quality, 'strip' => $strip);

        if ($pixelated) {
            $args['w'] = 50; // Reducir el ancho a 50 píxeles
            $args['h'] = 50; // Reducir el alto a 50 píxeles
            $args['zoom'] = 2; // Ampliar la imagen pequeña
        }

        return jetpack_photon_url($url, $args);
    } else {
        return wp_get_attachment_image_url($post_thumbnail_id, $size);
    }
}

//MOSTRAR INFORMACIÓN DEL AUTOR
function infoPost($author_id, $author_avatar, $author_name, $post_date, $post_id, $block, $colab)
{
    ob_start();
?>
    <div class="SOVHBY">
        <div class="CBZNGK">
            <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>"></a>
            <img src="<?php echo esc_url($author_avatar); ?>">
            <?php echo botonseguir($author_id); ?>
        </div>
        <div class="ZVJVZA">
            <div class="JHVSFW">
                <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>" class="profile-link">
                    <?php echo esc_html($author_name); ?></a>
            </div>
            <div class="HQLXWD">
                <a href="<?php echo esc_url(get_permalink()); ?>" class="post-link"><?php echo esc_html($post_date); ?></a>
            </div>
        </div>
    </div>
    <?php if ($block || $colab) : ?>
        <div class="OFVWLS">
            <?php
            if ($block) {
                echo "Exclusive";
            } elseif ($colab) {
                echo "Colab";
            }
            ?>
        </div>
    <?php endif; ?>
    <div class="YBZGPB">
        <?php echo opcionesPost($post_id, $author_id); ?>
    </div>
<?php
    return ob_get_clean();
}

//BOTON PARA SUSCRIBIRSE
function botonSuscribir($author_id, $author_name, $subscription_price_id = 'price_1OqGjlCdHJpmDkrryMzL0BCK')
{
    ob_start();
    $current_user = wp_get_current_user();
?>
    <button
        class="ITKSUG"
        data-offering-user-id="<?php echo esc_attr($author_id); ?>"
        data-offering-user-login="<?php echo esc_attr($author_name); ?>"
        data-offering-user-email="<?php echo esc_attr(get_the_author_meta('user_email', $author_id)); ?>"
        data-subscriber-user-id="<?php echo esc_attr($current_user->ID); ?>"
        data-subscriber-user-login="<?php echo esc_attr($current_user->user_login); ?>"
        data-subscriber-user-email="<?php echo esc_attr($current_user->user_email); ?>"
        data-price="<?php echo esc_attr($subscription_price_id); ?>"
        data-url="<?php echo esc_url(get_permalink()); ?>">
        Suscribirse
    </button>

    <?php

    return ob_get_clean();
}




function botonComentar($post_id)
{
    ob_start();
    ?>

    <div class="RTAWOD">
        <button class="WNLOFT" data-post-id="<?php echo $post_id; ?>">
            <?php echo $GLOBALS['iconocomentario']; ?>
        </button>
    </div>


<?php
    return ob_get_clean();
}

function fondoPost($filtro, $block, $es_suscriptor, $post_id)
{
    if (!in_array($filtro, ['rolastatus1', 'rolasEliminadas1', 'rolasRechazadas1'])) {
        $blurred_class = ($block && !$es_suscriptor) ? 'blurred' : '';
        $image_size = ($block && !$es_suscriptor) ? 'thumbnail' : 'large';
        $quality = ($block && !$es_suscriptor) ? 20 : 80;

        echo '<div class="post-background ' . $blurred_class . '" style="background-image: linear-gradient(to top, rgba(9, 9, 9, 10), rgba(0, 0, 0, 0) 100%), url(' . esc_url(imagenPost($post_id, $image_size, $quality, 'all', ($block && !$es_suscriptor))) . ');"></div>';
    }
}

function audioPost($post_id)
{
    $audio_id_lite = get_post_meta($post_id, 'post_audio_lite', true);

    if (empty($audio_id_lite)) {
        return '';
    }

    // Get the post author ID
    $post_author_id = get_post_field('post_author', $post_id);

    ob_start();
?>
    <div id="audio-container-<?php echo $post_id; ?>" class="audio-container" data-post-id="<?php echo $post_id; ?>" artista-id="<?php echo $post_author_id; ?>">

        <div class="play-pause-sobre-imagen">
            <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;">
        </div>

        <audio id="audio-<?php echo $post_id; ?>" src="<?php echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"></audio>
    </div>
<?php
    return ob_get_clean();
}

