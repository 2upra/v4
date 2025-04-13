<?php

// Refactor(Org): Función variablesPosts() movida a app/Services/PostService.php

// Funciones botonseguir() y botonSeguirPerfilBanner() movidas a app/View/Helpers/FollowHelper.php

// Refactor(Org): Función opcionesRola() movida a app/View/Components/PostOptions.php

// Refactor(Org): Función opcionesComentarios() movida a app/View/Helpers/CommentHelper.php



// Refactor(Org): Función imagenPostList() movida a app/View/Helpers/PostHelper.php

// Refactor(Clean): Función imagenPost() movida a app/View/Helpers/ImageHelper.php

// Refactor(Org): Función subirImagenALibreria() movida a app/Utils/FileUtils.php

// Refactor(Org): Funciones agregar_soporte_jfif y extender_wp_check_filetype movidas a app/Setup/ThemeSetup.php


// Refactor(Org): Función ejecutarScriptPermisos() movida a app/Utils/SystemUtils.php

// Refactor(Org): Función infoPost() movida a app/View/Helpers/PostHelper.php


// Refactor(Org): Función botonSuscribir() movida a app/View/Helpers/SubscriptionHelper.php

// Función botonComentar() movida a app/View/Helpers/CommentHelper.php

// Refactor(Org): Función fondoPost() movida a app/View/Helpers/PostHelper.php

// Refactor(Org): Función wave() y generate_wave_html() movidas a app/View/Helpers/AudioHelper.php


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
