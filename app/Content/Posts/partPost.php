<?

//VARIABLES POSTS
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
        'author_avatar' => imagenPerfil($author_id),
        'audio_id_lite' => get_post_meta($post_id, 'post_audio_lite', true),
        'audio_id' => get_post_meta($post_id, 'post_audio', true),
        'audio_url' => wp_get_attachment_url(get_post_meta($post_id, 'post_audio', true)),
        'audio_lite' => wp_get_attachment_url(get_post_meta($post_id, 'post_audio_lite', true)),
        'wave' => get_post_meta($post_id, 'waveform_image_url', true),
        'post_date' => get_the_date('', $post_id),
        'block' => get_post_meta($post_id, 'esExclusivo', true),
        'colab' => get_post_meta($post_id, 'paraColab', true),
        'post_status' => get_post_status($post_id),
        'bpm' => get_post_meta($post_id, 'audio_bpm', true),
        'key' => get_post_meta($post_id, 'audio_key', true),
        'scale' => get_post_meta($post_id, 'audio_scale', true),
        'detallesIA' => get_post_meta($post_id, 'audio_descripcion', true),
        'datosAlgoritmo' => get_post_meta($post_id, 'datosAlgoritmo', true),
        'postAut' => get_post_meta($post_id, 'postAut', true),
        'ultimoEdit' => get_post_meta($post_id, 'ultimoEdit', true),

    ];
}

//BOTON DE SEGUIR
function botonseguir($author_id)
{
    $author_id = (int) $author_id;
    $current_user_id = get_current_user_id();

    if ($current_user_id === 0) {
        return ''; // Usuario no autenticado
    }

    // Si el usuario está viendo su propio perfil, añadimos una clase de deshabilitado
    if ($current_user_id === $author_id) {
        ob_start();
?>
        <button class="mismo-usuario" disabled>
            <? echo $GLOBALS['iconomisusuario']; ?>
        </button>
    <?php
        return ob_get_clean();
    }

    $siguiendo = get_user_meta($current_user_id, 'siguiendo', true);
    $es_seguido = is_array($siguiendo) && in_array($author_id, $siguiendo);

    $clase_boton = $es_seguido ? 'dejar-de-seguir' : 'seguir';
    $icono_boton = $es_seguido ? $GLOBALS['iconorestar'] : $GLOBALS['iconosumar'];

    ob_start();
    ?>
    <button class="<? echo esc_attr($clase_boton); ?>"
        data-seguidor-id="<? echo esc_attr($current_user_id); ?>"
        data-seguido-id="<? echo esc_attr($author_id); ?>">
        <? echo $icono_boton; ?>
    </button>
<?php
    return ob_get_clean();
}

/*
	$GLOBALS["iconorestar"] = '<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM5 7.25H4.25V8.75H5H11H11.75V7.25H11H5Z" fill="currentColor"></path></svg>';

	//SUMAR
	$GLOBALS["iconosumar"] = '<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM8.75 4.25V5V7.25H11H11.75V8.75H11H8.75V11V11.75L7.25 11.75V11V8.75H5H4.25V7.25H5H7.25V5V4.25H8.75Z" fill="currentColor"></path></svg>';

*/


function botonSeguirPerfilBanner($author_id)
{
    // Asegurarse de que $author_id sea un entero
    $author_id = (int) $author_id;
    $current_user_id = get_current_user_id();

    // No mostrar el botón si el usuario no está conectado o es el mismo autor
    if ($current_user_id === 0 || $current_user_id === $author_id) {
        return '';
    }

    $siguiendo = get_user_meta($current_user_id, 'siguiendo', true);
    $siguiendo = is_array($siguiendo) ? $siguiendo : array();
    $es_seguido = in_array($author_id, $siguiendo);

    // Determinar la clase y el texto del botón
    $clase_boton = $es_seguido ? 'dejar-de-seguir' : 'seguir';
    $texto_boton = $es_seguido ? 'Dejar de seguir' : 'Seguir';

    // Generar el botón con el texto correspondiente
    ob_start();
?>
    <button class="borde <? echo esc_attr($clase_boton); ?>"
        data-seguidor-id="<? echo esc_attr($current_user_id); ?>"
        data-seguido-id="<? echo esc_attr($author_id); ?>">
        <? echo esc_html($texto_boton); ?>
    </button>
<?
    return ob_get_clean();
}

//OPCIONES EN LAS ROLAS 
function opcionesRola($post_id, $post_status, $audio_url)
{
    ob_start();
?>
    <button class="HR695R7" data-post-id="<? echo $post_id; ?>"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionesrola-<? echo $post_id; ?>">
        <div class="A1806242">
            <? if (current_user_can('administrator') && $post_status != 'publish' && $post_status != 'pending_deletion') { ?>
                <button class="toggle-status-rola" data-post-id="<? echo $post_id; ?>">Cambiar estado</button>
            <? } ?>

            <? if (current_user_can('administrator') && $post_status != 'publish' && $post_status != 'rejected' && $post_status != 'pending_deletion') { ?>
                <button class="rechazar-rola" data-post-id="<? echo $post_id; ?>">Rechazar rola</button>
            <? } ?>

            <button class="download-button" data-audio-url="<? echo $audio_url; ?>" data-filename="<? echo basename($audio_url); ?>">Descargar</button>

            <? if ($post_status != 'rejected' && $post_status != 'pending_deletion') { ?>
                <? if ($post_status == 'pending') { ?>
                    <button class="request-deletion" data-post-id="<? echo $post_id; ?>">Cancelar publicación</button>
                <? } else { ?>
                    <button class="request-deletion" data-post-id="<? echo $post_id; ?>">Solicitar eliminación</button>
                <? } ?>
            <? } ?>

        </div>
    </div>

    <div id="modalBackground3" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>

<?
    return ob_get_clean();
}

function opcionesPost($post_id, $author_id)
{
    $current_user_id = get_current_user_id();
    $audio_id_lite = get_post_meta($post_id, 'post_audio_lite', true);
    $descarga_permitida = get_post_meta($post_id, 'paraDescarga', true);
    $post_verificado = get_post_meta($post_id, 'Verificado', true);
    ob_start();
?>
    <button class="HR695R8" data-post-id="<? echo $post_id; ?>"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionespost-<? echo $post_id; ?>">
        <div class="A1806242">
            <? if (current_user_can('administrator')) : ?>
                <button class="eliminarPost" data-post-id="<? echo $post_id; ?>">Eliminar</button>
                <? if (!$post_verificado) : ?>
                    <button class="verificarPost" data-post-id="<? echo $post_id; ?>">Verificar</button>
                <? endif; ?>
                <button class="editarPost" data-post-id="<? echo $post_id; ?>">Editar</button>
                <!-- Nuevo botón para ir al editor de WordPress -->
                <button class="editarWordPress" data-post-id="<? echo $post_id; ?>">Editar en WordPress</button>
                <button class="banearUsuario" data-post-id="<? echo $post_id; ?>">Banear</button>
                <? if ($audio_id_lite && $descarga_permitida != 1) : ?>
                    <button class="permitirDescarga" data-post-id="<? echo $post_id; ?>">Permitir descarga</button>
                <? endif; ?>
            <? elseif ($current_user_id == $author_id) : ?>
                <button class="editarPost" data-post-id="<? echo $post_id; ?>">Editar</button>
                <button class="eliminarPost" data-post-id="<? echo $post_id; ?>">Eliminar</button>
                <? if ($audio_id_lite && $descarga_permitida != 1) : ?>
                    <button class="permitirDescarga" data-post-id="<? echo $post_id; ?>">Permitir descarga</button>
                <? endif; ?>
            <? else : ?>
                <button class="reporte" data-post-id="<? echo $post_id; ?>" tipoContenido="social_post">Reportar</button>
                <button class="bloquear" data-post-id="<? echo $post_id; ?>">Bloquear</button>
            <? endif; ?>
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
    // Obtener los metadatos del post
    $postAut = get_post_meta($post_id, 'postAut', true);
    $ultimoEdit = get_post_meta($post_id, 'ultimoEdit', true);
    $verificado = get_post_meta($post_id, 'Verificado', true);
    // Verificar si el autor es el usuario actual
    $current_user_id = (int)get_current_user_id();
    $author_id = (int)$author_id;
    $is_current_user = ($current_user_id === $author_id);
    ob_start();
?>
    <div class="SOVHBY <?php echo ($is_current_user ? 'miContenido' : ''); ?>">
        <div class="CBZNGK">
            <a href="<? echo esc_url(get_author_posts_url($author_id)); ?>"></a>
            <img src="<? echo esc_url($author_avatar); ?>">
            <? echo botonseguir($author_id); ?>
        </div>
        <div class="ZVJVZA">
            <div class="JHVSFW">
                <a href="<? echo esc_url(get_author_posts_url($author_id)); ?>" class="profile-link">
                    <? echo esc_html($author_name); ?>
                </a>
            </div>
            <div class="HQLXWD">
                <a href="<? echo esc_url(get_permalink()); ?>" class="post-link">
                    <? echo esc_html($post_date); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="verificacionPost">
        <? if ($verificado == '1') : ?>
            <? echo $GLOBALS['check']; ?>
        <? elseif ($postAut == '1') : ?>
            <? echo $GLOBALS['robot']; ?>
        <? endif; ?>
    </div>


    <? if ($block || $colab) : ?>
        <div class="OFVWLS">
            <?
            if ($block) {
                echo "Exclusivo";
            } elseif ($colab) {
                echo "Colab";
            }
            ?>
        </div>
    <? endif; ?>

    <div class="spin"></div>

    <div class="YBZGPB">
        <? echo opcionesPost($post_id, $author_id); ?>
    </div>
<?
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
        data-offering-user-id="<? echo esc_attr($author_id); ?>"
        data-offering-user-login="<? echo esc_attr($author_name); ?>"
        data-offering-user-email="<? echo esc_attr(get_the_author_meta('user_email', $author_id)); ?>"
        data-subscriber-user-id="<? echo esc_attr($current_user->ID); ?>"
        data-subscriber-user-login="<? echo esc_attr($current_user->user_login); ?>"
        data-subscriber-user-email="<? echo esc_attr($current_user->user_email); ?>"
        data-price="<? echo esc_attr($subscription_price_id); ?>"
        data-url="<? echo esc_url(get_permalink()); ?>">
        Suscribirse
    </button>

<?

    return ob_get_clean();
}
//
function botonComentar($post_id)
{
    ob_start();
?>

    <div class="RTAWOD">
        <button class="WNLOFT" data-post-id="<? echo $post_id; ?>">
            <? echo $GLOBALS['iconocomentario']; ?>
        </button>
    </div>


<?
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
    <div id="audio-container-<? echo $post_id; ?>" class="audio-container" data-post-id="<? echo $post_id; ?>" artista-id="<? echo $post_author_id; ?>">

        <div class="play-pause-sobre-imagen">
            <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;">
        </div>

        <audio id="audio-<? echo $post_id; ?>" src="<? echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"></audio>
    </div>
<?
    return ob_get_clean();
}

//esto ahora debe soportar varias waves, en un post (entorno wordpress), puede haber varios audio_id_lite (buscar y comprobar cuantos hay, puede haber hasta treinta), el primero ya esta puesto y llega por lo general con $audio_id_lite, pero aqui dentro de esta funcion se puede comprobar, los audio id lite se guardan asi: el primero por defecto se guarda en una meta post_audio_lite, no hay que comprobar si existe porque sino exista la funcion no activa, lo que hay que comprobar es el resto que puede ser post_audio_lite_2, post_audio_lite_3, post_audio_lite_4 hasta treinta, y en consecuencia mostrar esas wave, si mas de una wave, por favor ponerlas toda en un div con clase multiwaves

function wave($audio_url, $audio_id_lite, $post_id)
{
    $wave = get_post_meta($post_id, 'waveform_image_url', true);
    $waveCargada = get_post_meta($post_id, 'waveCargada', true);
    $urlAudioSegura = audioUrlSegura($audio_id_lite); // Usando la URL segura
?>
    <div id="waveform-<? echo $post_id; ?>"
        class="waveform-container without-image"
        postIDWave="<? echo $post_id; ?>"
        data-wave-cargada="<? echo $waveCargada ? 'true' : 'false'; ?>"
        data-audio-url="<? echo esc_url($urlAudioSegura); ?>">
        <div class="waveform-background" style="background-image: url('<? echo esc_url($wave); ?>');"></div>
        <div class="waveform-message"></div>
        <div class="waveform-loading" style="display: none;">Cargando...</div>
    </div>
<?
}