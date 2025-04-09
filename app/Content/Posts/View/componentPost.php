<?php

// Refactor(Org): Función variablesPosts() movida a app/Services/PostService.php

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
            <div class="reproducirSL" id-post="<?php echo $postId; ?>"><?php echo $GLOBALS['play']; ?></div>
            <div class="pausaSL" id-post="<?php echo $postId; ?>"><?php echo $GLOBALS['pause']; ?></div>
        </div>
    </div>
<?php

    $output = ob_get_clean();

    return $output;
}

// Refactor(Clean): Función imagenPost() movida a app/View/Helpers/ImageHelper.php

// Refactor(Org): Función subirImagenALibreria() movida a app/Utils/FileUtils.php

// Refactor(Org): Funciones agregar_soporte_jfif y extender_wp_check_filetype movidas a app/Setup/ThemeSetup.php


// Refactor(Org): Función ejecutarScriptPermisos() movida a app/Utils/SystemUtils.php

// Refactor(Org): Función infoPost() movida a app/View/Helpers/PostHelper.php


//BOTON PARA SUSCRIBIRSE
function botonSuscribir($autorId, $author_name, $subscription_price_id = 'price_1OqGjlCdHJpmDkrryMzL0BCK')
{
    ob_start();
    $current_user = wp_get_current_user();
?>
    <button
        class="ITKSUG"
        data-offering-user-id="<?php echo esc_attr($autorId); ?>"
        data-offering-user-login="<?php echo esc_attr($author_name); ?>"
        data-offering-user-email="<?php echo esc_attr(get_the_author_meta('user_email', $autorId)); ?>"
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
        style="background-image: linear-gradient(to top, rgba(9, 9, 9, 10), rgba(0, 0, 0, 0) 100%), url(<?php echo esc_url($optimized_thumbnail_url); ?>);">
    </div>
<?php
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
    <div class="waveforms-container-post" id="waveforms-container-<?php echo $postId; ?>" data-post-id="<?php echo esc_attr($postId); ?>">
        <?php
        // Mostrar los botones solo si hay más de un audio
        if ($audio_count > 1) : ?>
            <div class="botonesWave">
                <button class="prevWave" data-post-id="<?php echo esc_attr($postId); ?>">Anterior</button>
                <button class="nextWave" data-post-id="<?php echo esc_attr($postId); ?>">Siguiente</button>
            </div>
        <?php endif; ?>
        <?php
        // Generar el HTML para cada audio
        $index = 0;
        foreach ($audio_urls as $meta_key => $audio_url) {
            generate_wave_html($audio_url, $audio_id_lite, $postId, $meta_key, $wave, $index);
            $index++;
        }
        ?>
    </div>
<?php
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
    <div id="waveform-<?php echo $unique_id; ?>"
        class="waveform-container without-image"
        postIDWave="<?php echo $unique_id; ?>"
        data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>"
        data-audio-url="<?php echo esc_url($urlAudioSegura); ?>">
        <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
        <div class="waveform-message"></div>
        <div class="waveform-loading" style="display: none;">Cargando...</div>
    </div>
<?php
}

// Refactor(Org): Función audioPost() movida a app/View/Helpers/AudioHelper.php

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
    <div id="audio-container-<?php echo $postId; ?>" class="audio-container" data-post-id="<?php echo $postId; ?>" artista-id="<?php echo $post_author_id; ?>">

        <div class="play-pause-sobre-imagen">
            <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;">
        </div>

        <audio id="audio-<?php echo $postId; ?>" src="<?php echo esc_url($urlAudioSegura); ?>"></audio>
    </div>
<?php
    return ob_get_clean();
}
