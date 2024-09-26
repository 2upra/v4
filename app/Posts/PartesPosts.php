<?php

//VARIABLES COLAB
// VARIABLES COLAB
function variablesColab($post_id = null)
{
    if ($post_id === null) {
        global $post;
        $post_id = $post->ID;
    }

    $current_user_id = get_current_user_id();
    $colabPostOrigen = get_post_meta($post_id, 'colabPostOrigen', true);
    $colabAutor = get_post_meta($post_id, 'colabAutor', true);
    $colabColaborador = get_post_meta($post_id, 'colabColaborador', true);
    $colabMensaje = get_post_meta($post_id, 'colabMensaje', true);
    $colabFileUrl = get_post_meta($post_id, 'colabFileUrl', true);

    return [
        'current_user_id' => $current_user_id,
        'colabPostOrigen' => $colabPostOrigen,
        'colabAutor' => $colabAutor,
        'colabColaborador' => $colabColaborador,
        'colabMensaje' => $colabMensaje,
        'colabFileUrl' => $colabFileUrl,
        'colabAutorName' => get_the_author_meta('display_name', $colabAutor),
        'colabColaboradorName' => get_the_author_meta('display_name', $colabColaborador),
        'colabColaboradorAvatar' => imagenPerfil($colabColaborador),
        'colabAutorAvatar' => imagenPerfil($colabAutor),
        'colab_date' => get_the_date('', $post_id),
        'colab_status' => get_post_status($post_id),
    ];
}

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
//
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

// Función para obtener la URL segura del audio
function tokenAudio($audio_id) {
    $expiration = time() + 20; 
    $data = $audio_id . '|' . $expiration;
    $signature = hash_hmac('sha256', $data, ($_ENV['AUDIOCLAVE']));
    return base64_encode($data . '|' . $signature);
}

// Función para verificar el token
function verificarAudio($token) {
    $parts = explode('|', base64_decode($token));
    if (count($parts) !== 3) return false;
    
    list($audio_id, $expiration, $signature) = $parts;
    if (time() > $expiration) return false;
    
    $data = $audio_id . '|' . $expiration;
    $expected_signature = hash_hmac('sha256', $data, ($_ENV['AUDIOCLAVE']));
    return hash_equals($expected_signature, $signature);
}

// Modificar la función audioUrlSegura
function audioUrlSegura($audio_id) {
    $token = tokenAudio($audio_id);
    return site_url("/wp-json/1/v1/2?token=" . urlencode($token));
}

// Modificar el endpoint REST
add_action('rest_api_init', function () {
    register_rest_route('1/v1', '/2', array(
        'methods' => 'GET',
        'callback' => 'serve_audio_endpoint',
        'args' => array(
            'token' => array(
                'required' => true,
            ),
        ),
        'permission_callback' => function($request) {
            return verificarAudio($request->get_param('token'));
        }
    ));
});

// Modificar la función serve_audio_endpoint para implementar streaming
function serve_audio_endpoint($data) {
    $token = $data['token'];
    $parts = explode('|', base64_decode($token));
    $audio_id = $parts[0];
    
    $file = get_attached_file($audio_id);
    if (!file_exists($file)) {
        return new WP_Error('no_audio', 'Archivo de audio no encontrado.', array('status' => 404));
    }

    $fp = @fopen($file, 'rb');
    $size = filesize($file);
    $length = $size;
    $start = 0;
    $end = $size - 1;

    header('Content-Type: ' . get_post_mime_type($audio_id));
    header("Accept-Ranges: 0-$length");
    
    if (isset($_SERVER['HTTP_RANGE'])) {
        $c_start = $start;
        $c_end = $end;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            exit;
        }
        if ($range == '-') {
            $c_start = $size - substr($range, 1);
        } else {
            $range = explode('-', $range);
            $c_start = $range[0];
            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
        }
        $c_end = ($c_end > $end) ? $end : $c_end;
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            exit;
        }
        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1;
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
    }
    
    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: " . $length);
    
    $buffer = 1024 * 8;
    while(!feof($fp) && ($p = ftell($fp)) <= $end) {
        if ($p + $buffer > $end) {
            $buffer = $end - $p + 1;
        }
        echo fread($fp, $buffer);
        flush();
    }
    
    fclose($fp);
    exit();
}

// Función para cargar el audio en el frontend
function wave($audio_url, $audio_id_lite, $post_id)
{
    if ($audio_url) :
        $wave = get_post_meta($post_id, 'waveform_image_url', true);
        $waveCargada = get_post_meta($post_id, 'waveCargada', true);
        $secure_audio_url = audioUrlSegura($audio_id_lite); // Usando la URL segura
    ?>
        <div id="waveform-<?php echo $post_id; ?>"
             class="waveform-container without-image"
             postIDWave="<?php echo $post_id; ?>"
             data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>">
            <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
            <div class="waveform-message"></div>
            <div class="waveform-loading" style="display: none;">Cargando...</div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                loadAudio('<?php echo esc_js($post_id); ?>', '<?php echo esc_url($secure_audio_url); ?>');
            });
        </script>
    <?php endif;
}



/*
luego hay un script que hace esto
function loadAudio(postId, audioUrl) {
    fetch(audioUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.blob(); // Convierto el audio a un Blob
        })
        .then(blob => {
            const audioUrl = URL.createObjectURL(blob); // Creo una URL de objeto
            const audioElement = document.createElement('audio');
            audioElement.src = audioUrl; // Asigno la URL del Blob al elemento de audio
            audioElement.controls = true; // Añade controles para que el usuario reproduzca el audio
            document.getElementById(`waveform-${postId}`).appendChild(audioElement);
        })
        .catch(error => console.error('Error al cargar el audio:', error));
}
*/


function fileColab($post_id, $colabFileUrl)
{
    $waveCargada = get_post_meta($post_id, 'waveCargada', true);
    $audioExts = array('mp3', 'wav', 'ogg', 'aac');
    $fileExt = pathinfo($colabFileUrl, PATHINFO_EXTENSION);
    $isAudio = in_array(strtolower($fileExt), $audioExts);

    // Depuración: Imprimir la URL del archivo
    error_log('URL del archivo colaborativo: ' . $colabFileUrl);

    if ($isAudio) {
        $audioColab = obtenerArchivoIdAlt($colabFileUrl, $post_id);

        if ($audioColab && !is_wp_error($audioColab)) {
            update_post_meta($post_id, 'audioColab', $audioColab);
        }

        // Depuración: Imprimir el ID del audioColab
        error_log('ID del audioColab: ' . $audioColab);
    ?>
        <div id="waveform-<?php echo esc_attr($post_id); ?>"
            class="waveform-container without-image"
            postIDWave="<?php echo esc_attr($post_id); ?>"
            data-audio-url="<?php echo esc_url(site_url('?custom-audio-stream=1&audio_id=' . $audioColab)); ?>"
            data-wave-cargada="<?php echo esc_attr($waveCargada ? 'true' : 'false'); ?>">
            <div class="waveform-background"></div>
            <div class="waveform-message"></div>
            <div class="waveform-loading" style="display: none;">Cargando...</div>
        </div>
<?php
    } else {
        $fileName = basename($colabFileUrl);
        echo '<p>Archivo: ' . esc_html($fileName) . '</p>';
    }

    return ob_get_clean();
}

function obtenerArchivoIdAlt($url, $postId)
{
    global $wpdb;
    // Preparar la consulta incluyendo la verificación del tipo de post 'attachment'
    $sql = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment';", $url);
    $attachment = $wpdb->get_col($sql);

    // Depuración: Imprimir el resultado de la consulta
    error_log('Resultado de la consulta de archivo: ' . print_r($attachment, true));

    return !empty($attachment) ? $attachment[0] : 0;
}
