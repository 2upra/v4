<?php

/**
 * Componentes de renderizado de contenido de posts
 * 
 * Contiene métodos para renderizar contenido musical y no musical.
 * Esta clase extiende PostComponents para renderizado especializado.
 *
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

use Kamples\Services\Publicacion\PostRenderService;
use Kamples\Services\Contenido\ImagenService;
use Kamples\Views\Components\LikeButtons;

class PostContentComponents
{
    private PostRenderService $renderService;
    private PostComponents $postComponents;

    public function __construct()
    {
        $this->renderService = new PostRenderService();
        $this->postComponents = new PostComponents();
    }

    /**
     * Renderiza contenido musical
     * 
     * @param string $filtro Filtro
     * @param int $postId ID del post
     * @param string $authorName Nombre del autor
     * @param mixed $block Si es exclusivo
     * @param bool $esSuscriptor Si es suscriptor
     * @param string $postStatus Estado del post
     * @param string $audioUrl URL del audio
     * @return void
     */
    public function renderMusicContent(
        string $filtro,
        int $postId,
        string $authorName,
        $block,
        bool $esSuscriptor,
        string $postStatus,
        string $audioUrl
    ): void {
        $thumbnailUrl = get_the_post_thumbnail_url($postId, 'full');
        $optimizedThumbnailUrl = ImagenService::obtenerInstancia()->optimizar($thumbnailUrl, 40, 'all');
        $momento = get_post_meta($postId, 'momento', true);
        $esColeccion = get_post_meta($postId, 'datosColeccion', true);

        if (!$esColeccion) {
            $esColeccion = get_post_meta($postId, 'ultimaModificacion', true);
        }

        $permalink = get_permalink($postId);
?>
        <?php if (!empty($momento) || !empty($esColeccion)): ?>
            <a href="<?php echo esc_url($permalink); ?>">
            <?php endif; ?>

            <div class="post-content">
                <div class="MFQOYC">
                    <?php echo function_exists('like') ? like($postId) : ''; ?>
                    <?php echo $this->postComponents->opcionesRola($postId, $postStatus, $audioUrl); ?>
                </div>
                <div class="KLYJBY">
                    <?php echo $this->postComponents->audioPost($postId); ?>
                </div>

                <?php if (!empty($momento) || !empty($esColeccion)): ?>
                    <div class="contentMoment">
                        <?php
                        $content = get_the_content();
                        if (!empty($content)) {
                            echo $content;
                        } else {
                            echo '<p>' . get_the_title() . '</p>';
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <div class="LRKHLC">
                        <div class="XOKALG">
                            <?php
                            $rolaMeta = get_post_meta($postId, 'rola', true);
                            $nombreRolaHtml = '';

                            if ($rolaMeta === '1') {
                                $nombreRola = get_post_meta($postId, 'nombreRola', true);
                                if (empty($nombreRola)) {
                                    $nombreRola = get_post_meta($postId, 'nombreRola1', true);
                                }
                                if (!empty($nombreRola)) {
                                    $nombreRolaHtml = '<p class="nameRola">' . esc_html($nombreRola) . '</p>';
                                }
                            }

                            echo '<p>' . esc_html($authorName) . '</p>';
                            echo '<p>-</p>';
                            echo $nombreRolaHtml;
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="CPQBEN" style="display: none;">
                    <div class="CPQBAU"><?php echo esc_html($authorName); ?></div>
                    <div class="CPQBCO">
                        <?php
                        $rolaMeta = get_post_meta($postId, 'rola', true);
                        if ($rolaMeta === '1') {
                            $nombreRola = get_post_meta($postId, 'nombreRola', true);
                            if (empty($nombreRola)) {
                                $nombreRola = get_post_meta($postId, 'nombreRola1', true);
                            }
                            if (!empty($nombreRola)) {
                                echo "<p>" . esc_html($nombreRola) . "</p>";
                            }
                        }
                        ?>
                    </div>
                    <img src="<?php echo esc_url($optimizedThumbnailUrl); ?>" alt="">
                </div>
            </div>

            <?php if (!empty($momento) || !empty($esColeccion)): ?>
            </a>
        <?php endif;
        }

        /**
         * Renderiza contenido no musical
         * 
         * @param string $filtro Filtro
         * @param int $postId ID del post
         * @param int $authorId ID del autor
         * @param string $authorAvatar Avatar del autor
         * @param string $authorName Nombre del autor
         * @param string $postDate Fecha del post
         * @param mixed $block Si es exclusivo
         * @param mixed $colab Si es colab
         * @param bool $esSuscriptor Si es suscriptor
         * @param string $audioUrl URL del audio
         * @param string $scale Escala
         * @param string $key Clave
         * @param string $bpm BPM
         * @param mixed $datosAlgoritmo Datos del algoritmo
         * @param string $postStatus Estado del post
         * @param mixed $audioIdLite ID del audio lite
         * @return void
         */
        public function renderNonMusicContent(
            string $filtro,
            int $postId,
            int $authorId,
            string $authorAvatar,
            string $authorName,
            string $postDate,
            $block,
            $colab,
            bool $esSuscriptor,
            string $audioUrl,
            $scale,
            $key,
            $bpm,
            $datosAlgoritmo,
            string $postStatus,
            $audioIdLite
        ): void {
        ?>
        <div class="post-content">
            <div class="JNUZCN">
                <?php if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])): ?>
                    <?php echo $this->postComponents->infoPost($authorId, $authorAvatar, $authorName, $postDate, $postId, $block, $colab); ?>
                <?php else: ?>
                    <div class="XABLJI">
                        <?php echo esc_html($postStatus); ?>
                        <?php echo $this->postComponents->opcionesRola($postId, $postStatus, $audioUrl); ?>
                        <div class="CPQBEN" style="display: none;">
                            <div class="CPQBAU"><?php echo esc_html($authorName); ?></div>
                            <div class="CPQBCO">
                                <?php
                                $rolaMeta = get_post_meta($postId, 'rola', true);
                                if ($rolaMeta === '1') {
                                    $nombreRola = get_post_meta($postId, 'nombreRola', true);
                                    if (empty($nombreRola)) {
                                        $nombreRola = get_post_meta($postId, 'nombreRola1', true);
                                    }
                                    if (!empty($nombreRola)) {
                                        echo "<p>" . esc_html($nombreRola) . "</p>";
                                    }
                                } else {
                                    the_content();
                                    if (has_post_thumbnail($postId) && empty($audioIdLite)): ?>
                                        <div class="post-thumbnail">
                                            <?php echo get_the_post_thumbnail($postId, 'full'); ?>
                                        </div>
                                <?php endif;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="YGWCKC">
                    <?php if ($block && !$esSuscriptor): ?>
                        <?php $this->renderSubscriptionPrompt($authorName, $authorId); ?>
                    <?php else: ?>
                        <?php $this->renderContentAndMedia($filtro, $postId, $audioUrl, $scale, $key, $bpm, $datosAlgoritmo, $audioIdLite); ?>
                    <?php endif; ?>
                </div>

                <div class="IZXEPH">
                    <?php echo $this->postComponents->renderPostControls($postId, $colab, $audioIdLite); ?>
                </div>
            </div>
        </div>
    <?php
        }

        /**
         * Renderiza prompt de suscripción
         * 
         * @param string $authorName Nombre del autor
         * @param int $authorId ID del autor
         * @return void
         */
        public function renderSubscriptionPrompt(string $authorName, int $authorId): void
        {
    ?>
        <div class="ZHNDDD">
            <p>Suscríbete a <?php echo esc_html($authorName); ?> para ver el contenido de este post</p>
            <?php echo function_exists('botonSuscribir') ? botonSuscribir($authorId, $authorName) : ''; ?>
        </div>
    <?php
        }

        /**
         * Renderiza contenido y media del post
         * 
         * @param string $filtro Filtro
         * @param int $postId ID del post
         * @param string $audioUrl URL del audio
         * @param mixed $scale Escala
         * @param mixed $key Clave
         * @param mixed $bpm BPM
         * @param mixed $datosAlgoritmo Datos del algoritmo
         * @param mixed $audioIdLite ID del audio lite
         * @return void
         */
        public function renderContentAndMedia(
            string $filtro,
            int $postId,
            string $audioUrl,
            $scale,
            $key,
            $bpm,
            $datosAlgoritmo,
            $audioIdLite
        ): void {
    ?>
        <div class="NERWFB">
            <div class="YWBIBG">
                <?php if (!empty($audioIdLite)): ?>
                    <?php
                    $hasPostThumbnail = has_post_thumbnail($postId);
                    $imagenTemporalId = get_post_meta($postId, 'imagenTemporal', true);
                    ?>
                    <?php if ($hasPostThumbnail || $imagenTemporalId): ?>
                        <div class="MRPDOR">
                            <?php if ($hasPostThumbnail): ?>
                                <div class="post-thumbnail">
                                    <?php
                                    $thumbnailUrl = get_the_post_thumbnail_url($postId, 'full');
                                    $optimizedThumbnailUrl = ImagenService::obtenerInstancia()->optimizar($thumbnailUrl, 40, 'all');
                                    ?>
                                    <img src="<?php echo esc_url($optimizedThumbnailUrl); ?>" alt="<?php echo esc_attr(get_the_title($postId)); ?>">
                                </div>
                            <?php elseif ($imagenTemporalId): ?>
                                <div class="temporal-thumbnail">
                                    <?php
                                    $temporalImageUrl = wp_get_attachment_url($imagenTemporalId);
                                    $optimizedTemporalImageUrl = ImagenService::obtenerInstancia()->optimizar($temporalImageUrl, 40, 'all');
                                    ?>
                                    <img src="<?php echo esc_url($optimizedTemporalImageUrl); ?>" alt="Imagen temporal">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="OASDEF">
                    <div class="thePostContet" data-post-id="<?php echo esc_attr($postId); ?>">
                        <?php
                        $rolaMeta = get_post_meta($postId, 'rola', true);
                        if ($rolaMeta === '1') {
                            $nombreRola = get_post_meta($postId, 'nombreRola', true);
                            if (empty($nombreRola)) {
                                $nombreRola = get_post_meta($postId, 'nombreRola1', true);
                            }
                            if (!empty($nombreRola)) {
                                echo "<p>" . esc_html($nombreRola) . "</p>";
                            }
                        } else {
                            the_content();
                            if (has_post_thumbnail($postId) && empty($audioIdLite)): ?>
                                <div class="post-thumbnail">
                                    <?php echo get_the_post_thumbnail($postId, 'full'); ?>
                                </div>
                        <?php endif;
                        }
                        ?>
                    </div>

                    <div>
                        <?php
                        $keyInfo = $key ?: null;
                        $scaleInfo = $scale ?: null;
                        $bpmInfo = $bpm ? round($bpm) : null;

                        $info = array_filter([$keyInfo, $scaleInfo, $bpmInfo]);
                        if (!empty($info)) {
                            echo '<p class="TRZPQD">' . implode(' - ', $info) . '</p>';
                        }
                        ?>
                    </div>

                    <?php if (!in_array($filtro, ['rolastatus', 'rolasEliminadas', 'rolasRechazadas'])): ?>
                        <div class="ZQHOQY">
                            <?php if (!empty($audioIdLite)): ?>
                                <?php $this->postComponents->wave($audioUrl, $audioIdLite, $postId); ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="KLYJBY">
                            <?php echo $this->postComponents->audioPost($postId); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($audioIdLite)): ?>
                <div class="FBKMJD">
                    <div class="UKVPJI">
                        <div class="tags-container" id="tags-<?php echo esc_attr($postId); ?>"></div>
                        <p id-post-algoritmo="<?php echo esc_attr($postId); ?>" style="display:none;">
                            <?php echo esc_html($this->renderService->limpiarJSON($datosAlgoritmo)); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php
        }

        /**
         * Renderiza sample list HTML
         * 
         * @param mixed $block Si es exclusivo
         * @param bool $esSuscriptor Si es suscriptor
         * @param int $postId ID del post
         * @param mixed $datosAlgoritmo Datos del algoritmo
         * @param string $verificado Si está verificado
         * @param string $postAut Si es post automático
         * @param string $urlAudioSegura URL segura del audio
         * @param string $wave URL del wave
         * @param mixed $waveCargada Si el wave está cargado
         * @param mixed $colab Si es colab
         * @param int $authorId ID del autor
         * @param mixed $audioIdLite ID del audio lite
         * @return void
         */
        public function sampleListHtml(
            $block,
            bool $esSuscriptor,
            int $postId,
            $datosAlgoritmo,
            $verificado,
            $postAut,
            string $urlAudioSegura,
            string $wave,
            $waveCargada,
            $colab,
            int $authorId,
            $audioIdLite = null
        ): void {
            $rolaMeta = get_post_meta($postId, 'rola', true);
    ?>
        <div class="LISTSAMPLE">
            <?php if ($rolaMeta === '1'): ?>
                <div class="KLYJBY">
                    <?php echo $this->postComponents->audioPost($postId); ?>
                </div>
                <?php echo $this->postComponents->imagenPostList($block, $esSuscriptor, $postId); ?>
                <div class="INFOLISTSAMPLE">
                    <div class="CONTENTLISTSAMPLE">
                        <a id-post="<?php echo $postId; ?>">
                            <div class="LRKHLC">
                                <div class="XOKALG">
                                    <?php
                                    $nombreRolaHtml = '';
                                    $nombreRola = get_post_meta($postId, 'nombreRola', true);
                                    if (empty($nombreRola)) {
                                        $nombreRola = get_post_meta($postId, 'nombreRola1', true);
                                    }
                                    if (!empty($nombreRola)) {
                                        $nombreRolaHtml = '<p class="nameRola">' . esc_html($nombreRola) . '</p>';
                                    }
                                    echo $nombreRolaHtml;
                                    ?>
                                </div>
                            </div>
                        </a>
                        <div class="CPQBAU"><?php echo get_the_author_meta('display_name', $authorId); ?></div>
                    </div>
                    <div class="MOREINFOLIST">
                        <?php
                        $audioDuration = get_post_meta($postId, 'audio_duration_1', true);
                        $nombreLanzamiento = get_post_meta($postId, 'nombreLanzamiento', true);

                        if (!empty($nombreLanzamiento)) {
                            echo '<p class="lanzamiento"><span>' . esc_html($nombreLanzamiento) . '</span></p>';
                        }
                        if (!empty($audioDuration)) {
                            echo '<p class="duration"><span>' . esc_html($audioDuration) . '</span></p>';
                        }
                        ?>
                    </div>
                    <div class="CPQBEN" style="display: none;">
                        <?php echo function_exists('like') ? like($postId) : ''; ?>
                        <div class="CPQBAU"><?php echo get_the_author_meta('display_name', $authorId); ?></div>
                        <div class="CPQBCO">
                            <?php
                            $nombreRola = get_post_meta($postId, 'nombreRola', true);
                            if (empty($nombreRola)) {
                                $nombreRola = get_post_meta($postId, 'nombreRola1', true);
                            }
                            if (!empty($nombreRola)) {
                                echo "<p>" . esc_html($nombreRola) . "</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <?php echo $this->postComponents->renderPostControls($postId, $colab, $audioIdLite); ?>
                <?php echo $this->postComponents->opcionesPost($postId, $authorId); ?>
            <?php else: ?>
                <?php echo $this->postComponents->imagenPostList($block, $esSuscriptor, $postId); ?>
                <div class="INFOLISTSAMPLE">
                    <div class="CONTENTLISTSAMPLE">
                        <a id-post="<?php echo $postId; ?>">
                            <?php
                            $content = get_post_field('post_content', $postId);
                            $content = wp_trim_words($content, 20, '...');
                            echo wp_kses_post($content);
                            ?>
                        </a>
                    </div>
                    <div class="CPQBEN" style="display: none;">
                        <?php echo function_exists('like') ? like($postId) : ''; ?>
                        <div class="CPQBAU"><?php echo get_the_author_meta('display_name', $authorId); ?></div>
                        <div class="CPQBCO">
                            <?php
                            $nombreRola = get_post_meta($postId, 'nombreRola', true);
                            if (empty($nombreRola)) {
                                $nombreRola = get_post_meta($postId, 'nombreRola1', true);
                            }
                            if (!empty($nombreRola)) {
                                echo "<p>" . esc_html($nombreRola) . "</p>";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="TAGSLISTSAMPLE">
                        <div class="tags-container" id="tags-<?php echo $postId; ?>"></div>
                        <p id-post-algoritmo="<?php echo $postId; ?>" style="display:none;">
                            <?php echo esc_html($this->renderService->limpiarJSON($datosAlgoritmo)); ?>
                        </p>
                    </div>
                </div>
                <div class="INFOTYPELIST">
                    <div class="verificacionPost">
                        <?php if ($verificado == '1'): ?>
                            <?php echo $GLOBALS['check']; ?>
                        <?php elseif ($postAut == '1' && current_user_can('administrator')): ?>
                            <div class="verificarPost" data-post-id="<?php echo $postId; ?>" style="cursor: pointer;">
                                <?php echo $GLOBALS['robot']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ZQHOQY LISTWAVESAMPLE">
                    <div id="waveform-<?php echo $postId; ?>"
                        class="waveform-container without-image"
                        postIDWave="<?php echo $postId; ?>"
                        data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>"
                        data-audio-url="<?php echo esc_url($urlAudioSegura); ?>">
                        <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
                        <div class="waveform-message"></div>
                        <div class="waveform-loading" style="display: none;">Cargando...</div>
                    </div>
                </div>
                <?php echo $this->postComponents->renderPostControls($postId, $colab, $audioIdLite); ?>
                <?php echo $this->postComponents->opcionesPost($postId, $authorId); ?>
            <?php endif; ?>
        </div>
    <?php
        }

        /**
         * Renderiza HTML de artículo
         * 
         * @param string $filtro Filtro
         * @return string HTML
         */
        public function htmlArticulo(string $filtro): string
        {
            $postId = get_the_ID();
            $vars = $this->renderService->obtenerVariablesArticulo($postId);
            extract($vars);
            $imagenUrl = $this->renderService->obtenerImagenArticulo($postId);

            ob_start();
    ?>
        <a href="<?php echo esc_url(get_permalink($postId)); ?>" class="post-link">
            <li class="POST-<?php echo esc_attr($filtro); ?> EDYQHV"
                filtro="<?php echo esc_attr($filtro); ?>"
                id-post="<?php echo esc_attr($postId); ?>"
                autor="<?php echo esc_attr($autorId); ?>"
                style="background-image: url('<?php echo esc_url($imagenUrl); ?>'); background-size: cover; background-position: center; position: relative;">

                <div class="overlay"></div>
                <div class="post-content">
                    <h2 class="post-title" data-post-id="<?php echo esc_attr($postId); ?>"><?php echo get_the_title($postId); ?></h2>
                    <p class="post-author"><?php echo get_the_author_meta('display_name', $autorId); ?></p>
                </div>
            </li>
        </a>
        <li class="comentariosPost"></li>
<?php
            return ob_get_clean();
        }
    }
