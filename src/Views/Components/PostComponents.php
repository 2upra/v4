<?php

/**
 * Componentes de vista para Posts
 * 
 * Contiene todos los métodos de renderizado HTML para posts.
 *
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

use Kamples\Services\Publicacion\PostRenderService;

class PostComponents
{
    private PostRenderService $renderService;
    private PostContentComponents $contentComponents;

    public function __construct()
    {
        $this->renderService = new PostRenderService();
    }

    /**
     * Obtiene la instancia de PostContentComponents (lazy loading para evitar ciclo)
     * 
     * @return PostContentComponents
     */
    private function getContentComponents(): PostContentComponents
    {
        if (!isset($this->contentComponents)) {
            /** @var PostContentComponents */
            $this->contentComponents = new PostContentComponents();
        }
        return $this->contentComponents;
    }

    /**
     * Renderiza el HTML completo de un post
     * 
     * @param string $filtro Tipo de filtro
     * @return string HTML del post
     */
    public function htmlPost(string $filtro): string
    {
        $postId = get_the_ID();
        $vars = $this->renderService->obtenerVariablesPost($postId);
        extract($vars);

        $music = ($filtro === 'rola' || $filtro === 'likes');

        if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
            $filtro = 'rolastatus';
        }

        $sampleList = $filtro === 'sampleList';
        $rolaList = $filtro === 'rolaListLike';
        $momento = $filtro === 'momento';

        $wave = get_post_meta($postId, 'waveform_image_url', true);
        $waveCargada = get_post_meta($postId, 'waveCargada', true);
        $postAut = get_post_meta($postId, 'postAut', true);
        $verificado = get_post_meta($postId, 'Verificado', true);
        $urlAudioSegura = $this->renderService->obtenerUrlAudioSegura($audio_id_lite);

        ob_start();
?>
        <li class="POST-<?php echo esc_attr($filtro); ?> EDYQHV <?php echo get_the_ID(); ?>"
            filtro="<?php echo esc_attr($filtro); ?>"
            id-post="<?php echo get_the_ID(); ?>"
            autor="<?php echo esc_attr($author_id); ?>">

            <?php if ($sampleList || $rolaList): ?>
                <?php $this->getContentComponents()->sampleListHtml($block, $es_suscriptor, $postId, $datosAlgoritmo, $verificado, $postAut, $urlAudioSegura, $wave, $waveCargada, $colab, $author_id, $audio_id_lite); ?>
            <?php else: ?>
                <?php echo $this->fondoPost($filtro, $block, $es_suscriptor, $postId); ?>
                <?php if ($music || $momento): ?>
                    <?php $this->getContentComponents()->renderMusicContent($filtro, $postId, $author_name, $block, $es_suscriptor, $post_status, $audio_url); ?>
                <?php else: ?>
                    <?php $this->getContentComponents()->renderNonMusicContent($filtro, $postId, $author_id, $author_avatar, $author_name, $post_date, $block, $colab, $es_suscriptor, $audio_url, $scale, $key, $bpm, $datosAlgoritmo, $post_status, $audio_id_lite); ?>
                <?php endif; ?>
            <?php endif; ?>
        </li>

        <li class="comentariosPost"></li>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el fondo del post
     * 
     * @param string $filtro Filtro
     * @param mixed $block Si es exclusivo
     * @param bool $esSuscriptor Si es suscriptor
     * @param int $postId ID del post
     * @return string HTML
     */
    public function fondoPost(string $filtro, $block, bool $esSuscriptor, int $postId): string
    {
        $thumbnailUrl = get_the_post_thumbnail_url($postId, 'full');

        if (!$thumbnailUrl) {
            $imagenTemporalId = get_post_meta($postId, 'imagenTemporal', true);
            if ($imagenTemporalId) {
                $thumbnailUrl = wp_get_attachment_url($imagenTemporalId);
            }
        }

        $blurredClass = ($block && !$esSuscriptor) ? 'blurred' : '';
        $optimizedUrl = function_exists('img') ? img($thumbnailUrl, 40, 'all') : $thumbnailUrl;

        ob_start();
    ?>
        <div class="post-background <?php echo esc_attr($blurredClass); ?>"
            style="background-image: linear-gradient(to top, rgba(9, 9, 9, 10), rgba(0, 0, 0, 0) 100%), url(<?php echo esc_url($optimizedUrl); ?>);">
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la imagen del post en lista
     * 
     * @param mixed $block Si es exclusivo
     * @param bool $esSuscriptor Si es suscriptor
     * @param int $postId ID del post
     * @return string HTML
     */
    public function imagenPostList($block, bool $esSuscriptor, int $postId): string
    {
        $blurredClass = ($block && !$esSuscriptor) ? 'blurred' : '';
        $imageSize = 'thumbnail';
        $quality = 20;

        $imageUrl = $this->renderService->obtenerImagenPost(
            $postId,
            $imageSize,
            $quality,
            'all',
            ($block && !$esSuscriptor),
            true
        );

        if (!$imageUrl) {
            $imageUrl = get_the_post_thumbnail_url($postId, $imageSize) ?: '';
        }

        $processedUrl = function_exists('img') ? img($imageUrl, $quality, 'all') : $imageUrl;

        ob_start();
    ?>
        <div class="post-image-container <?php echo esc_attr($blurredClass); ?>">
            <a>
                <img src="<?php echo esc_url($processedUrl); ?>" alt="Post Image" />
            </a>
            <div class="botonesRep">
                <div class="reproducirSL" id-post="<?php echo $postId; ?>"><?php echo $GLOBALS['play']; ?></div>
                <div class="pausaSL" id-post="<?php echo $postId; ?>"><?php echo $GLOBALS['pause']; ?></div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza información del autor del post
     * 
     * @param int $autId ID del autor
     * @param string $autAv Avatar del autor
     * @param string $autNom Nombre del autor
     * @param string $postF Fecha del post
     * @param int $postId ID del post
     * @param mixed $block Si es exclusivo
     * @param mixed $colab Si es colab
     * @return string HTML
     */
    public function infoPost(int $autId, string $autAv, string $autNom, string $postF, int $postId, $block, $colab): string
    {
        $postAut = get_post_meta($postId, 'postAut', true);
        $verif = get_post_meta($postId, 'Verificado', true);
        $rec = get_post_meta($postId, 'recortado', true);
        $usrAct = (int)get_current_user_id();
        $esUsrAct = ($usrAct === $autId);

        ob_start();
    ?>
        <div class="SOVHBY <?php echo ($esUsrAct ? 'miContenido' : ''); ?>">
            <div class="CBZNGK">
                <a href="<?php echo esc_url(get_author_posts_url($autId)); ?>"> </a>
                <img src="<?php echo esc_url($autAv); ?>">
                <?php echo $this->botonSeguir($autId); ?>
            </div>
            <div class="ZVJVZA">
                <div class="JHVSFW">
                    <a href="<?php echo esc_url(home_url('/perfil/' . get_the_author_meta('user_nicename', $autId))); ?>" class="profile-link">
                        <?php echo esc_html($autNom); ?>
                        <?php if (get_user_meta($autId, 'pro', true) || user_can($autId, 'administrator') || get_user_meta($autId, 'Verificado', true)): ?>
                            <?php echo $GLOBALS['verificado']; ?>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="HQLXWD">
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="post-link">
                        <?php echo esc_html($postF); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="verificacionPost">
            <?php if ($verif == '1'): ?>
                <?php echo $GLOBALS['check']; ?>
            <?php elseif ($postAut == '1' && current_user_can('administrator')): ?>
                <?php echo $GLOBALS['robot']; ?>
            <?php endif; ?>
        </div>

        <div class="OFVWLS">
            <?php if ($rec): ?>
                <div><?php echo "Preview"; ?></div>
            <?php endif; ?>
            <?php if ($block): ?>
                <div><?php echo "Exclusivo"; ?></div>
            <?php elseif ($colab): ?>
                <div><?php echo "Colab"; ?></div>
            <?php endif; ?>
        </div>

        <div class="spin"></div>

        <div class="YBZGPB">
            <?php echo $this->opcionesPost($postId, $autId); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza botón de seguir
     * 
     * @param int $autorId ID del autor
     * @return string HTML
     */
    public function botonSeguir(int $autorId): string
    {
        $usuarioActual = get_current_user_id();

        if ($usuarioActual === 0) {
            return '';
        }

        if ($usuarioActual === $autorId) {
            ob_start();
        ?>
            <button class="mismo-usuario" disabled></button>
        <?php
            return ob_get_clean();
        }

        $siguiendo = get_user_meta($usuarioActual, 'siguiendo', true);
        $esSeguido = is_array($siguiendo) && in_array($autorId, $siguiendo);

        $claseBoton = $esSeguido ? 'dejar-de-seguir' : 'seguir';
        $iconoBoton = $esSeguido ? $GLOBALS['iconorestar'] : $GLOBALS['iconosumar'];

        ob_start();
        ?>
        <button class="<?php echo esc_attr($claseBoton); ?>"
            data-seguidor-id="<?php echo esc_attr($usuarioActual); ?>"
            data-seguido-id="<?php echo esc_attr($autorId); ?>">
            <?php echo $iconoBoton; ?>
        </button>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza botón de seguir para banner de perfil
     * 
     * @param int $autorId ID del autor
     * @return string HTML
     */
    public function botonSeguirPerfilBanner(int $autorId): string
    {
        $usuarioActual = get_current_user_id();

        if ($usuarioActual === 0 || $usuarioActual === $autorId) {
            return '';
        }

        $siguiendo = get_user_meta($usuarioActual, 'siguiendo', true);
        $siguiendo = is_array($siguiendo) ? $siguiendo : [];
        $esSeguido = in_array($autorId, $siguiendo);

        $claseBoton = $esSeguido ? 'dejar-de-seguir' : 'seguir';
        $textoBoton = $esSeguido ? 'Dejar de seguir' : 'Seguir';

        ob_start();
    ?>
        <button class="borde <?php echo esc_attr($claseBoton); ?>"
            data-seguidor-id="<?php echo esc_attr($usuarioActual); ?>"
            data-seguido-id="<?php echo esc_attr($autorId); ?>">
            <?php echo esc_html($textoBoton); ?>
        </button>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza opciones de la rola (audio)
     * 
     * @param int $postId ID del post
     * @param string $postStatus Estado del post
     * @param string $audioUrl URL del audio
     * @return string HTML
     */
    public function opcionesRola(int $postId, string $postStatus, string $audioUrl): string
    {
        ob_start();
    ?>
        <button class="HR695R7" data-post-id="<?php echo $postId; ?>"><?php echo $GLOBALS['iconotrespuntos']; ?></button>

        <div class="A1806241" id="opcionesrola-<?php echo $postId; ?>">
            <div class="A1806242">
                <?php if (current_user_can('administrator') && $postStatus != 'publish' && $postStatus != 'pending_deletion'): ?>
                    <button class="toggle-status-rola" data-post-id="<?php echo $postId; ?>">Cambiar estado</button>
                <?php endif; ?>

                <?php if (current_user_can('administrator') && $postStatus != 'publish' && $postStatus != 'rejected' && $postStatus != 'pending_deletion'): ?>
                    <button class="rechazar-rola" data-post-id="<?php echo $postId; ?>">Rechazar rola</button>
                <?php endif; ?>

                <button class="download-button" data-audio-url="<?php echo esc_url($audioUrl); ?>" data-filename="<?php echo basename($audioUrl); ?>">Descargar</button>

                <?php if ($postStatus != 'rejected' && $postStatus != 'pending_deletion'): ?>
                    <?php if ($postStatus == 'pending'): ?>
                        <button class="request-deletion" data-post-id="<?php echo $postId; ?>">Cancelar publicación</button>
                    <?php else: ?>
                        <button class="request-deletion" data-post-id="<?php echo $postId; ?>">Solicitar eliminación</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="modalBackground3" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza opciones del post
     * 
     * @param int $postId ID del post
     * @param int $autorId ID del autor
     * @return string HTML
     */
    public function opcionesPost(int $postId, int $autorId): string
    {
        $usuarioActual = get_current_user_id();
        $postMeta = get_post_meta($postId);
        $audioIdLite = isset($postMeta['post_audio_lite'][0]) ? intval($postMeta['post_audio_lite'][0]) : null;
        $paraDescarga = isset($postMeta['paraDescarga'][0]) ? intval($postMeta['paraDescarga'][0]) : null;
        $postVerificado = isset($postMeta['Verificado'][0]) && $postMeta['Verificado'][0] === '1';
        $esAdmin = current_user_can('administrator');
        $esTarea = get_post_type($postId) === 'tarea';
        $esAutor = ($usuarioActual == $autorId);

        ob_start();
    ?>
        <button class="HR695R8" data-post-id="<?php echo esc_attr($postId); ?>"><?php echo $GLOBALS['iconotrespuntos']; ?></button>

        <div class="A1806241" id="opcionespost-<?php echo esc_attr($postId); ?>">
            <div class="A1806242">
                <?php if ($esTarea): ?>
                    <?php if ($esAutor): ?>
                        <button class="eliminarPost" data-post-id="<?php echo esc_attr($postId); ?>">Eliminar tarea</button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="iralpost"><a ajaxUrl="<?php echo esc_url(get_permalink($postId)); ?>">Ir al post</a></button>

                    <?php if ($esAdmin): ?>
                        <button class="eliminarPost" data-post-id="<?php echo esc_attr($postId); ?>">Eliminar</button>
                        <?php echo $this->renderizarBotonDescarga($postId, $usuarioActual, $paraDescarga); ?>
                        <?php echo $this->renderizarBotonSincronizar($postId, $usuarioActual, $paraDescarga); ?>
                        <?php if (!$postVerificado): ?>
                            <button class="verificarPost" data-post-id="<?php echo esc_attr($postId); ?>">Verificar</button>
                        <?php endif; ?>
                        <?php if ($audioIdLite !== 1): ?>
                            <button class="corregirTags" data-post-id="<?php echo esc_attr($postId); ?>">Corrección inteligente</button>
                        <?php endif; ?>
                        <button class="editarPost" data-post-id="<?php echo esc_attr($postId); ?>">Editar</button>
                        <button class="editarWordPress" data-post-id="<?php echo esc_attr($postId); ?>">Editar en WordPress</button>
                        <button class="banearUsuario" data-post-id="<?php echo esc_attr($postId); ?>">Banear</button>
                        <?php if ($audioIdLite && $paraDescarga !== 1): ?>
                            <button class="permitirDescarga" data-post-id="<?php echo esc_attr($postId); ?>">Permitir descarga</button>
                        <?php endif; ?>
                    <?php elseif ($esAutor): ?>
                        <?php if ($audioIdLite !== 1): ?>
                            <button class="corregirTags" data-post-id="<?php echo esc_attr($postId); ?>">Corrección inteligente</button>
                        <?php endif; ?>
                        <button class="editarPost" data-post-id="<?php echo esc_attr($postId); ?>">Editar</button>
                        <button class="eliminarPost" data-post-id="<?php echo esc_attr($postId); ?>">Eliminar</button>
                        <?php if ($audioIdLite && $paraDescarga !== 1): ?>
                            <button class="permitirDescarga" data-post-id="<?php echo esc_attr($postId); ?>">Permitir descarga</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="reporte" data-post-id="<?php echo esc_attr($postId); ?>" tipoContenido="social_post">Reportar</button>
                        <button class="bloquear" data-post-id="<?php echo esc_attr($postId); ?>">Bloquear</button>
                        <?php echo $this->renderizarBotonDescarga($postId, $usuarioActual, $paraDescarga); ?>
                        <?php echo $this->renderizarBotonSincronizar($postId, $usuarioActual, $paraDescarga); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="modalBackground4" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza botón de descarga
     * 
     * @param int $postId ID del post
     * @param int $usuarioActual ID del usuario actual
     * @param mixed $paraDescarga Meta de descarga
     * @return string HTML
     */
    public function renderizarBotonDescarga(int $postId, int $usuarioActual, $paraDescarga): string
    {
        ob_start();

        if ($paraDescarga == '1') {
            if ($usuarioActual) {
                $descargasAnteriores = get_user_meta($usuarioActual, 'descargas', true);
                $yaDescargado = isset($descargasAnteriores[$postId]);
                $claseExtra = $yaDescargado ? 'yaDescargado' : '';
        ?>
                <div class="ZAQIBB">
                    <button class="icon-arrow-down <?php echo esc_attr($claseExtra); ?>"
                        data-post-id="<?php echo esc_attr($postId); ?>"
                        aria-label="Boton Descarga"
                        id="download-button-<?php echo esc_attr($postId); ?>"
                        onclick="return procesarDescarga('<?php echo esc_js($postId); ?>', '<?php echo esc_js($usuarioActual); ?>', 'false', '1', 'false')">
                        Descargar
                    </button>
                </div>
            <?php
            } else {
            ?>
                <div class="ZAQIBB">
                    <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                        Descargar
                    </button>
                </div>
            <?php
            }
        }

        return ob_get_clean();
    }

    /**
     * Renderiza botón de sincronizar
     * 
     * @param int $postId ID del post
     * @param int $usuarioActual ID del usuario actual
     * @param mixed $paraDescarga Meta de descarga
     * @return string HTML
     */
    public function renderizarBotonSincronizar(int $postId, int $usuarioActual, $paraDescarga): string
    {
        ob_start();

        if ($paraDescarga == '1') {
            if ($usuarioActual) {
                $descargasAnteriores = get_user_meta($usuarioActual, 'descargas', true);
                $yaDescargado = isset($descargasAnteriores[$postId]);
                $claseExtra = $yaDescargado ? 'yaDescargado' : '';
            ?>
                <div class="ZAQIBB">
                    <button class="icon-arrow-down <?php echo esc_attr($claseExtra); ?>"
                        data-post-id="<?php echo esc_attr($postId); ?>"
                        aria-label="Boton Descarga"
                        id="download-button-<?php echo esc_attr($postId); ?>"
                        onclick="return procesarDescarga('<?php echo esc_js($postId); ?>', '<?php echo esc_js($usuarioActual); ?>', 'false', '1', 'true')">
                        Sincronizar
                    </button>
                </div>
            <?php
            } else {
            ?>
                <div class="ZAQIBB">
                    <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                        Sincronizar
                    </button>
                </div>
        <?php
            }
        }

        return ob_get_clean();
    }

    /**
     * Renderiza el waveform del audio
     * 
     * @param string $audioUrl URL del audio
     * @param mixed $audioIdLite ID del audio lite
     * @param int $postId ID del post
     * @return void
     */
    public function wave(string $audioUrl, $audioIdLite, int $postId): void
    {
        $wave = get_post_meta($postId, 'waveform_image_url', true);
        $audioCount = 0;
        $audioUrls = [];

        $audioUrlLite = get_post_meta($postId, 'post_audio_lite', true);
        if (!empty($audioUrlLite)) {
            $audioCount++;
            $audioUrls['post_audio_lite'] = $audioUrlLite;
        }

        for ($i = 2; $i <= 30; $i++) {
            $metaKey = 'post_audio_lite_' . $i;
            $audioUrlMultiple = get_post_meta($postId, $metaKey, true);
            if (!empty($audioUrlMultiple)) {
                $audioCount++;
                $audioUrls[$metaKey] = $audioUrlMultiple;
            }
        }
        ?>
        <div class="waveforms-container-post" id="waveforms-container-<?php echo $postId; ?>" data-post-id="<?php echo esc_attr($postId); ?>">
            <?php if ($audioCount > 1): ?>
                <div class="botonesWave">
                    <button class="prevWave" data-post-id="<?php echo esc_attr($postId); ?>">Anterior</button>
                    <button class="nextWave" data-post-id="<?php echo esc_attr($postId); ?>">Siguiente</button>
                </div>
            <?php endif; ?>
            <?php
            $index = 0;
            foreach ($audioUrls as $metaKey => $audUrl) {
                $this->generateWaveHtml($audUrl, $audioIdLite, $postId, $metaKey, $wave, $index);
                $index++;
            }
            ?>
        </div>
    <?php
    }

    /**
     * Genera HTML del waveform individual
     * 
     * @param string $audioUrl URL del audio
     * @param mixed $audioIdLite ID del audio lite
     * @param int $postId ID del post
     * @param string $metaKey Clave de meta
     * @param string $wave URL de la imagen del wave
     * @param int $index Índice
     * @return void
     */
    private function generateWaveHtml(string $audioUrl, $audioIdLite, int $postId, string $metaKey, string $wave, int $index): void
    {
        $waveCargada = get_post_meta($postId, 'waveCargada_' . $metaKey, true);
        $urlAudioSegura = $this->renderService->obtenerUrlAudioSegura($audioUrl);
        $uniqueId = $postId . '-' . $metaKey;
    ?>
        <div id="waveform-<?php echo $uniqueId; ?>"
            class="waveform-container without-image"
            postIDWave="<?php echo $uniqueId; ?>"
            data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>"
            data-audio-url="<?php echo esc_url($urlAudioSegura); ?>">
            <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
            <div class="waveform-message"></div>
            <div class="waveform-loading" style="display: none;">Cargando...</div>
        </div>
    <?php
    }

    /**
     * Renderiza el contenedor de audio del post
     * 
     * @param int $postId ID del post
     * @return string HTML
     */
    public function audioPost(int $postId): string
    {
        $audioIdLite = get_post_meta($postId, 'post_audio_lite', true);

        if (empty($audioIdLite)) {
            return '';
        }

        $postAuthorId = get_post_field('post_author', $postId);
        $urlAudioSegura = $this->renderService->obtenerUrlAudioSegura($audioIdLite);

        ob_start();
    ?>
        <div id="audio-container-<?php echo $postId; ?>" class="audio-container" data-post-id="<?php echo $postId; ?>" artista-id="<?php echo $postAuthorId; ?>">
            <div class="play-pause-sobre-imagen">
                <img src="<?php echo esc_url(site_url('/wp-content/uploads/2024/03/1.svg')); ?>" alt="Play" style="width: 50px; height: 50px;">
            </div>
            <audio id="audio-<?php echo $postId; ?>" src="<?php echo esc_url($urlAudioSegura); ?>"></audio>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza controles del post
     * 
     * @param int $postId ID del post
     * @param mixed $colab Si es colab
     * @param mixed $audioIdLite ID del audio lite
     * @return string HTML
     */
    public function renderPostControls(int $postId, $colab, $audioIdLite = null): string
    {
        $mostrarBotonCompra = get_post_meta($postId, 'tienda', true) === '1';

        ob_start();
    ?>
        <div class="QSORIW">
            <?php echo \Kamples\Views\Components\LikeButtons::mostrar($postId); ?>
            <?php if ($mostrarBotonCompra && function_exists('botonCompra')): ?>
                <?php echo botonCompra($postId); ?>
            <?php endif; ?>
            <?php echo $this->renderBotonComentar($postId); ?>
            <?php if (!empty($audioIdLite)): ?>
                <?php echo $this->renderizarBotonDescarga($postId, get_current_user_id(), get_post_meta($postId, 'paraDescarga', true)); ?>
                <?php echo function_exists('botonColab') ? botonColab($postId, $colab) : ''; ?>
                <?php echo function_exists('botonColeccion') ? botonColeccion($postId) : ''; ?>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza botón de comentar
     * 
     * @param int $postId ID del post
     * @return string HTML
     */
    public function renderBotonComentar(int $postId): string
    {
        ob_start();
    ?>
        <div class="RTAWOD">
            <button class="WNLOFT" data-post-id="<?php echo esc_attr($postId); ?>">
                <?php echo $GLOBALS['iconocomentario'] ?? ''; ?>
            </button>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza estado vacío (no hay posts)
     * 
     * @param string $filtro Filtro
     * @param bool $isAjax Si es petición AJAX
     * @return string HTML
     */
    public function nohayPost(string $filtro, bool $isAjax): string
    {
        if ($filtro === 'notas') {
            return '';
        }

        if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
            $filtro = 'rolastatus';
        }

        ob_start();
    ?>
        <?php if ($filtro === 'momento' || $isAjax): ?>
            <div id="no-more-posts"></div>
            <div id="no-more-posts-two" no-more="<?php echo esc_attr($filtro); ?>"></div>
        <?php else: ?>
            <div class="LNVHED no-<?php echo esc_attr($filtro); ?>">
                <?php echo $GLOBALS['emptystate']; ?>
                <p>Ñoño aqui no han puesto nada aún</p>
                <?php if ($filtro === 'rolastatus'): ?>
                    <p>Cuando publiques tu primera rola, aparecerá aquí</p>
                <?php endif; ?>
                <button class="borde"><a href="<?php echo home_url('/'); ?>">Volver al inicio</a></button>
            </div>
        <?php endif; ?>
<?php
        return ob_get_clean();
    }
}
