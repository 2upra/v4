<?php

/**
 * Wrappers deprecados para Posts
 * 
 * Este archivo contiene funciones de compatibilidad que redirigen
 * a las nuevas clases en src/. NO USAR EN CÓDIGO NUEVO.
 *
 * @deprecated Usar Kamples\Views\Components\PostComponents y relacionados
 * @package Theme\V4\Deprecated
 */

use Kamples\Views\Components\PostComponents;
use Kamples\Views\Components\PostContentComponents;
use Kamples\Services\PostRenderService;

/*
 * COMPONENTES DE POST
 */

if (!function_exists('variablesPosts')) {
    /**
     * @deprecated Usar PostRenderService::obtenerVariablesPost()
     */
    function variablesPosts($postId = null)
    {
        $service = new PostRenderService();
        return $service->obtenerVariablesPost($postId);
    }
}

if (!function_exists('htmlPost')) {
    /**
     * @deprecated Usar PostComponents::htmlPost()
     */
    function htmlPost($filtro)
    {
        $component = new PostComponents();
        return $component->htmlPost($filtro);
    }
}

if (!function_exists('fondoPost')) {
    /**
     * @deprecated Usar PostComponents::fondoPost()
     */
    function fondoPost($filtro, $block, $es_suscriptor, $postId)
    {
        $component = new PostComponents();
        return $component->fondoPost($filtro, $block, (bool)$es_suscriptor, $postId);
    }
}

if (!function_exists('imagenPostList')) {
    /**
     * @deprecated Usar PostComponents::imagenPostList()
     */
    function imagenPostList($block, $es_suscriptor, $postId)
    {
        $component = new PostComponents();
        return $component->imagenPostList($block, (bool)$es_suscriptor, $postId);
    }
}

if (!function_exists('infoPost')) {
    /**
     * @deprecated Usar PostComponents::infoPost()
     */
    function infoPost($autId, $autAv, $autNom, $postF, $postId, $block, $colab)
    {
        $component = new PostComponents();
        return $component->infoPost((int)$autId, $autAv, $autNom, $postF, (int)$postId, $block, $colab);
    }
}

if (!function_exists('botonseguir')) {
    /**
     * @deprecated Usar PostComponents::botonSeguir()
     */
    function botonseguir($autorId)
    {
        $component = new PostComponents();
        return $component->botonSeguir((int)$autorId);
    }
}

if (!function_exists('botonSeguirPerfilBanner')) {
    /**
     * @deprecated Usar PostComponents::botonSeguirPerfilBanner()
     */
    function botonSeguirPerfilBanner($autorId)
    {
        $component = new PostComponents();
        return $component->botonSeguirPerfilBanner((int)$autorId);
    }
}

if (!function_exists('opcionesRola')) {
    /**
     * @deprecated Usar PostComponents::opcionesRola()
     */
    function opcionesRola($postId, $post_status, $audio_url)
    {
        $component = new PostComponents();
        return $component->opcionesRola((int)$postId, $post_status, $audio_url);
    }
}

if (!function_exists('opcionesPost')) {
    /**
     * @deprecated Usar PostComponents::opcionesPost()
     */
    function opcionesPost($postId, $autorId)
    {
        $component = new PostComponents();
        return $component->opcionesPost((int)$postId, (int)$autorId);
    }
}

if (!function_exists('opcionesComentarios')) {
    /**
     * @deprecated Usar ComentarioComponents o PostComponents
     */
    function opcionesComentarios($postId, $autorId)
    {
        $usuarioActual = get_current_user_id();
        ob_start();
?>
        <button class="submenucomentario" data-post-id="<?php echo $postId; ?>"><?php echo $GLOBALS['iconotrespuntos']; ?></button>
        <div class="A1806241" id="opcionescomentarios-<?php echo $postId; ?>">
            <div class="A1806242">
                <?php if (current_user_can('administrator')): ?>
                    <button class="eliminarPost" data-post-id="<?php echo $postId; ?>">Eliminar</button>
                    <button class="editarPost" data-post-id="<?php echo $postId; ?>">Editar</button>
                    <button class="editarWordPress" data-post-id="<?php echo $postId; ?>">Editar en WordPress</button>
                    <button class="banearUsuario" data-post-id="<?php echo $postId; ?>">Banear</button>
                <?php elseif ($usuarioActual == $autorId): ?>
                    <button class="editarPost" data-post-id="<?php echo $postId; ?>">Editar</button>
                    <button class="eliminarPost" data-post-id="<?php echo $postId; ?>">Eliminar</button>
                <?php else: ?>
                    <button class="iralpost"><a ajaxUrl="<?php echo esc_url(get_permalink()); ?>">Ir al post</a></button>
                    <button class="reporte" data-post-id="<?php echo $postId; ?>" tipoContenido="social_post">Reportar</button>
                    <button class="bloquear" data-post-id="<?php echo $postId; ?>">Bloquear</button>
                <?php endif; ?>
            </div>
        </div>
        <div id="modalBackground4" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
    <?php
        return ob_get_clean();
    }
}

if (!function_exists('renderizarBotonDescarga')) {
    /**
     * @deprecated Usar PostComponents::renderizarBotonDescarga()
     */
    function renderizarBotonDescarga($postId, $usuarioActual, $paraDescarga)
    {
        $component = new PostComponents();
        return $component->renderizarBotonDescarga((int)$postId, (int)$usuarioActual, $paraDescarga);
    }
}

if (!function_exists('renderizarBotonSincronizar')) {
    /**
     * @deprecated Usar PostComponents::renderizarBotonSincronizar()
     */
    function renderizarBotonSincronizar($postId, $usuarioActual, $paraDescarga)
    {
        $component = new PostComponents();
        return $component->renderizarBotonSincronizar((int)$postId, (int)$usuarioActual, $paraDescarga);
    }
}

if (!function_exists('wave')) {
    /**
     * @deprecated Usar PostComponents::wave()
     */
    function wave($audio_url, $audio_id_lite, $postId)
    {
        $component = new PostComponents();
        $component->wave($audio_url, $audio_id_lite, (int)$postId);
    }
}

if (!function_exists('generate_wave_html')) {
    /**
     * @deprecated Usar PostComponents internamente
     */
    function generate_wave_html($audio_url, $audio_id_lite, $postId, $meta_key, $wave, $index)
    {
        $service = new PostRenderService();
        $urlAudioSegura = $service->obtenerUrlAudioSegura($audio_url);
        $waveCargada = get_post_meta($postId, 'waveCargada_' . $meta_key, true);
        $unique_id = $postId . '-' . $meta_key;
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
}

if (!function_exists('audioPost')) {
    /**
     * @deprecated Usar PostComponents::audioPost()
     */
    function audioPost($postId)
    {
        $component = new PostComponents();
        return $component->audioPost((int)$postId);
    }
}

if (!function_exists('audioPostList')) {
    /**
     * @deprecated Usar PostComponents::audioPost()
     */
    function audioPostList($postId)
    {
        $component = new PostComponents();
        return $component->audioPost((int)$postId);
    }
}

if (!function_exists('obtenerImagenAleatoria')) {
    /**
     * @deprecated Usar PostRenderService::obtenerImagenAleatoria()
     */
    function obtenerImagenAleatoria($directory)
    {
        $service = new PostRenderService();
        return $service->obtenerImagenAleatoria($directory);
    }
}

if (!function_exists('subirImagenALibreria')) {
    /**
     * @deprecated Usar PostRenderService::subirImagenALibreria()
     */
    function subirImagenALibreria($file_path, $postId)
    {
        $service = new PostRenderService();
        return $service->subirImagenALibreria($file_path, $postId);
    }
}

if (!function_exists('botonSuscribir')) {
    /**
     * @deprecated Migrar a componente de suscripción
     */
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
}

if (!function_exists('botonComentar')) {
    /**
     * @deprecated Migrar a componente de comentarios
     */
    function botonComentar($postId, $colab = null)
    {
        ob_start();
    ?>
        <div class="RTAWOD">
            <button class="WNLOFT" data-post-id="<?php echo $postId; ?>">
                <?php echo $GLOBALS['iconocomentario']; ?>
            </button>
        </div>
<?php
        return ob_get_clean();
    }
}

/*
 * RENDERIZADO DE POSTS
 */

if (!function_exists('sampleListHtml')) {
    /**
     * @deprecated Usar PostContentComponents::sampleListHtml()
     */
    function sampleListHtml($block, $es_suscriptor, $post_id, $datosAlgoritmo, $verificado, $postAut, $urlAudioSegura, $wave, $waveCargada, $colab, $author_id, $audio_id_lite = null)
    {
        $component = new PostContentComponents();
        $component->sampleListHtml($block, (bool)$es_suscriptor, $post_id, $datosAlgoritmo, $verificado, $postAut, $urlAudioSegura, $wave, $waveCargada, $colab, $author_id, $audio_id_lite);
    }
}

if (!function_exists('renderMusicContent')) {
    /**
     * @deprecated Usar PostContentComponents::renderMusicContent()
     */
    function renderMusicContent($filtro, $post_id, $author_name, $block, $es_suscriptor, $post_status, $audio_url)
    {
        $component = new PostContentComponents();
        $component->renderMusicContent($filtro, $post_id, $author_name, $block, (bool)$es_suscriptor, $post_status, $audio_url);
    }
}

if (!function_exists('renderNonMusicContent')) {
    /**
     * @deprecated Usar PostContentComponents::renderNonMusicContent()
     */
    function renderNonMusicContent($filtro, $post_id, $author_id, $author_avatar, $author_name, $post_date, $block, $colab, $es_suscriptor, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $post_status, $audio_id_lite)
    {
        $component = new PostContentComponents();
        $component->renderNonMusicContent($filtro, $post_id, $author_id, $author_avatar, $author_name, $post_date, $block, $colab, (bool)$es_suscriptor, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $post_status, $audio_id_lite);
    }
}

if (!function_exists('renderSubscriptionPrompt')) {
    /**
     * @deprecated Usar PostContentComponents::renderSubscriptionPrompt()
     */
    function renderSubscriptionPrompt($author_name, $author_id)
    {
        $component = new PostContentComponents();
        $component->renderSubscriptionPrompt($author_name, $author_id);
    }
}

if (!function_exists('renderPostControls')) {
    /**
     * @deprecated Usar PostComponents::renderPostControls()
     */
    function renderPostControls($post_id, $colab, $audio_id_lite = null)
    {
        $component = new PostComponents();
        echo $component->renderPostControls($post_id, $colab, $audio_id_lite);
    }
}

if (!function_exists('renderContentAndMedia')) {
    /**
     * @deprecated Usar PostContentComponents::renderContentAndMedia()
     */
    function renderContentAndMedia($filtro, $post_id, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $audio_id_lite)
    {
        $component = new PostContentComponents();
        $component->renderContentAndMedia($filtro, $post_id, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $audio_id_lite);
    }
}

if (!function_exists('limpiarJSON')) {
    /**
     * @deprecated Usar PostRenderService::limpiarJSON()
     */
    function limpiarJSON($json_data)
    {
        $service = new PostRenderService();
        return $service->limpiarJSON($json_data);
    }
}

if (!function_exists('nohayPost')) {
    /**
     * @deprecated Usar PostComponents::nohayPost()
     */
    function nohayPost($filtro, $is_ajax)
    {
        $component = new PostComponents();
        return $component->nohayPost($filtro, (bool)$is_ajax);
    }
}

/*
 * ARTICULOS
 */

if (!function_exists('variablesArticulo')) {
    /**
     * @deprecated Usar PostRenderService::obtenerVariablesArticulo()
     */
    function variablesArticulo($postId)
    {
        $service = new PostRenderService();
        return $service->obtenerVariablesArticulo($postId);
    }
}

if (!function_exists('imagenArticulo')) {
    /**
     * @deprecated Usar PostRenderService::obtenerImagenArticulo()
     */
    function imagenArticulo($postId)
    {
        $service = new PostRenderService();
        return $service->obtenerImagenArticulo($postId);
    }
}

if (!function_exists('htmlArticulo')) {
    /**
     * @deprecated Usar PostContentComponents::htmlArticulo()
     */
    function htmlArticulo($filtro)
    {
        $component = new PostContentComponents();
        return $component->htmlArticulo($filtro);
    }
}

/* Soporte para thumbnails */
add_theme_support('post-thumbnails');

/* Inicializar filtros de formato de imagen */
PostRenderService::inicializarFiltros();
