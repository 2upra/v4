<?

//VARIABLES POSTS
function variablesPosts($postId = null)
{
    if ($postId === null) {
        global $post;
        $postId = $post->ID;
    }

    $usuarioActual = get_current_user_id();
    $autores_suscritos = get_user_meta($usuarioActual, 'offering_user_ids', true);
    $autorId = get_post_field('post_author', $postId);

    $datos_algoritmo = get_post_meta($postId, 'datosAlgoritmo', true);
    $datos_algoritmo_respaldo = get_post_meta($postId, 'datosAlgoritmo_respaldo', true);

    if (is_array($datos_algoritmo_respaldo)) {
        $datos_algoritmo_respaldo = json_encode($datos_algoritmo_respaldo);
    } elseif (is_object($datos_algoritmo_respaldo)) {
        $datos_algoritmo_respaldo = serialize($datos_algoritmo_respaldo);
    }

    // Elegir entre datos_algoritmo o su respaldo
    $datos_algoritmo_final = empty($datos_algoritmo) ? $datos_algoritmo_respaldo : $datos_algoritmo;

    return [
        'current_user_id' => $usuarioActual,
        'autores_suscritos' => $autores_suscritos,
        'author_id' => $autorId,
        'es_suscriptor' => in_array($autorId, (array)$autores_suscritos),
        'author_name' => get_the_author_meta('display_name', $autorId),
        'author_avatar' => imagenPerfil($autorId),
        'audio_id_lite' => get_post_meta($postId, 'post_audio_lite', true),
        'audio_id' => get_post_meta($postId, 'post_audio', true),
        'audio_url' => wp_get_attachment_url(get_post_meta($postId, 'post_audio', true)),
        'audio_lite' => wp_get_attachment_url(get_post_meta($postId, 'post_audio_lite', true)),
        'wave' => get_post_meta($postId, 'waveform_image_url', true),
        'post_date' => get_the_date('', $postId),
        'block' => get_post_meta($postId, 'esExclusivo', true),
        'colab' => get_post_meta($postId, 'paraColab', true),
        'post_status' => get_post_status($postId),
        'bpm' => get_post_meta($postId, 'audio_bpm', true),
        'key' => get_post_meta($postId, 'audio_key', true),
        'scale' => get_post_meta($postId, 'audio_scale', true),
        'detallesIA' => get_post_meta($postId, 'audio_descripcion', true),
        'datosAlgoritmo' => $datos_algoritmo_final,
        'postAut' => get_post_meta($postId, 'postAut', true),
        'ultimoEdit' => get_post_meta($postId, 'ultimoEdit', true),
    ];
}

// Funciones botonseguir() y botonSeguirPerfilBanner() movidas a app/View/Helpers/FollowHelper.php

// Refactor(Org): Función opcionesRola() movida a app/View/Components/PostOptions.php

// Refactor(Org): Función opcionesComentarios() movida a app/View/Helpers/CommentHelper.php



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
            <div class="reproducirSL" id-post="<? echo $postId; ?>"><? echo $GLOBALS['play']; ?></div>
            <div class="pausaSL" id-post="<? echo $postId; ?>"><? echo $GLOBALS['pause']; ?></div>
        </div>
    </div>
<?

    $output = ob_get_clean();

    return $output;
}

// Refactor(Clean): Función imagenPost() movida a app/View/Helpers/ImageHelper.php

function subirImagenALibreria($file_path, $postId)
{
    if (!file_exists($file_path)) {
        return false;
    }
    $file_contents = file_get_contents($file_path);
    if ($file_contents === false) {
        return false;
    }
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($file_ext === 'jfif') {
        $file_ext = 'jpeg';
        $new_file_name = pathinfo($file_path, PATHINFO_FILENAME) . '.jpeg';
        $upload_file = wp_upload_bits($new_file_name, null, $file_contents);
    } else {
        $upload_file = wp_upload_bits(basename($file_path), null, $file_contents);
    }

    if ($upload_file['error']) {
        return false;
    }
    $filetype = wp_check_filetype($upload_file['file'], null);
    if (!$filetype['type']) {
        return false;
    }
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($upload_file['file'], PATHINFO_BASENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_parent'    => $postId,
    );
    $attach_id = wp_insert_attachment($attachment, $upload_file['file'], $postId);
    if (!is_wp_error($attach_id)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
    return false;
}

// Refactor(Org): Funciones agregar_soporte_jfif y extender_wp_check_filetype movidas a app/Setup/ThemeSetup.php




/**
 * Ejecuta un script de shell para corregir permisos.
 */
function ejecutarScriptPermisos()
{
    // Ejecutar el script de permisos y capturar la salida
    $output = shell_exec('sudo /var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh 2>&1');

    // Opcional: Puedes registrar el output para depuración
    error_log('Script de permisos ejecutado: ' . $output);
}

//MOSTRAR INFORMACIÓN DEL AUTOR

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
        <? echo opcionesPost($postId, $autId); ?>
    </div>
<?
    return ob_get_clean();
}


//BOTON PARA SUSCRIBIRSE
function botonSuscribir($autorId, $author_name, $subscription_price_id = 'price_1OqGjlCdHJpmDkrryMzL0BCK')
{
    ob_start();
    $current_user = wp_get_current_user();
?>
    <button
        class="ITKSUG"
        data-offering-user-id="<? echo esc_attr($autorId); ?>"
        data-offering-user-login="<? echo esc_attr($author_name); ?>"
        data-offering-user-email="<? echo esc_attr(get_the_author_meta('user_email', $autorId)); ?>"
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
// Función botonComentar() movida a app/View/Helpers/CommentHelper.php

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
        style="background-image: linear-gradient(to top, rgba(9, 9, 9, 10), rgba(0, 0, 0, 0) 100%), url(<? echo esc_url($optimized_thumbnail_url); ?>);">
    </div>
<?
    $output = ob_get_clean();
    return $output;
}

function wave($audio_url, $audio_id_lite, $postId)
{
    $wave = get_post_meta($postId, 'waveform_image_url', true);

    // Contar la cantidad de audios disponibles
    $audio_count = 0;
    $audio_urls = array();

    // Cargar la URL para post_audio_lite
    $audio_url_lite = get_post_meta($postId, 'post_audio_lite', true);
    if (!empty($audio_url_lite)) {
        $audio_count++;
        $audio_urls['post_audio_lite'] = $audio_url_lite;
    }

    // Cargar las URLs para post_audio_lite_2, post_audio_lite_3, ..., post_audio_lite_30
    for ($i = 2; $i <= 30; $i++) {
        $meta_key = 'post_audio_lite_' . $i;
        $audio_url_multiple = get_post_meta($postId, $meta_key, true);

        if (!empty($audio_url_multiple)) {
            $audio_count++;
            $audio_urls[$meta_key] = $audio_url_multiple;
        }
    }
?>
    <div class="waveforms-container-post" id="waveforms-container-<? echo $postId; ?>" data-post-id="<? echo esc_attr($postId); ?>">
        <?
        // Mostrar los botones solo si hay más de un audio
        if ($audio_count > 1) : ?>
            <div class="botonesWave">
                <button class="prevWave" data-post-id="<? echo esc_attr($postId); ?>">Anterior</button>
                <button class="nextWave" data-post-id="<? echo esc_attr($postId); ?>">Siguiente</button>
            </div>
        <? endif; ?>
        <?
        // Generar el HTML para cada audio
        $index = 0;
        foreach ($audio_urls as $meta_key => $audio_url) {
            generate_wave_html($audio_url, $audio_id_lite, $postId, $meta_key, $wave, $index);
            $index++;
        }
        ?>
    </div>
<?
}

function generate_wave_html($audio_url, $audio_id_lite, $postId, $meta_key, $wave, $index)
{

    $waveCargada = get_post_meta($postId, 'waveCargada_' . $meta_key, true); // Wave cargada para cada audio
    $urlAudioSegura = audioUrlSegura($audio_url);
    $unique_id = $postId . '-' . $meta_key; // ID único para cada waveform

    if (is_wp_error($urlAudioSegura)) {
        $urlAudioSegura = '';
    }
?>
    <div id="waveform-<? echo $unique_id; ?>"
        class="waveform-container without-image"
        postIDWave="<? echo $unique_id; ?>"
        data-wave-cargada="<? echo $waveCargada ? 'true' : 'false'; ?>"
        data-audio-url="<? echo esc_url($urlAudioSegura); ?>">
        <div class="waveform-background" style="background-image: url('<? echo esc_url($wave); ?>');"></div>
        <div class="waveform-message"></div>
        <div class="waveform-loading" style="display: none;">Cargando...</div>
    </div>
<?
}

function audioPost($postId)
{
    $audio_id_lite = get_post_meta($postId, 'post_audio_lite', true);

    if (empty($audio_id_lite)) {
        return '';
    }

    $post_author_id = get_post_field('post_author', $postId);
    $urlAudioSegura = audioUrlSegura($audio_id_lite);

    ob_start();
?>
    <div id="audio-container-<? echo $postId; ?>" class="audio-container" data-post-id="<? echo $postId; ?>" artista-id="<? echo $post_author_id; ?>">

        <div class="play-pause-sobre-imagen">
            <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;">
        </div>

        <audio id="audio-<? echo $postId; ?>" src="<? echo esc_url($urlAudioSegura); ?>"></audio>
    </div>
<?
    return ob_get_clean();
}

function audioPostList($postId)
{
    $audio_id_lite = get_post_meta($postId, 'post_audio_lite', true);

    if (empty($audio_id_lite)) {
        return '';
    }
    $urlAudioSegura = audioUrlSegura($audio_id_lite);
    $post_author_id = get_post_field('post_author', $postId);
    if (is_wp_error($urlAudioSegura)) {
        $urlAudioSegura = ''; // O establece un valor predeterminado o maneja el error de forma diferente
    }
    ob_start();
?>
    <div id="audio-container-<? echo $postId; ?>" class="audio-container" data-post-id="<? echo $postId; ?>" artista-id="<? echo $post_author_id; ?>">

        <div class="play-pause-sobre-imagen">
            <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;">
        </div>

        <audio id="audio-<? echo $postId; ?>" src="<? echo esc_url($urlAudioSegura); ?>"></audio>
    </div>
<?
    return ob_get_clean();
}
