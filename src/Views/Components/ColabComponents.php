<?php

/**
 * Componentes de vista para el sistema de colaboraciones.
 * 
 * Contiene todos los métodos de renderizado HTML para las
 * colaboraciones (modales, listas, chat, opciones, etc.).
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

use Kamples\Services\Social\ColabService;

/* Evitar acceso directo */

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColabComponents
{
    /**
     * Servicio de colaboraciones.
     */
    private static ?ColabService $colabService = null;

    /**
     * Obtener instancia del servicio.
     */
    private static function getService(): ColabService
    {
        if (self::$colabService === null) {
            self::$colabService = new ColabService();
        }
        return self::$colabService;
    }

    /**
     * Renderizar HTML completo de una colaboración.
     * 
     * @param string $filtro Tipo de filtro (colabPendiente, etc).
     * @return string HTML de la colaboración.
     */
    public static function renderHtmlColab(string $filtro): string
    {
        $postId = get_the_ID();
        $var    = self::getService()->obtenerVariablesColab($postId);

        ob_start();
?>
        <li class="modal POST-<?php echo esc_attr($filtro); ?> EDYQHV"
            filtro="<?php echo esc_attr($filtro); ?>"
            id-post="<?php echo esc_attr($postId); ?>"
            autor="<?php echo esc_attr($var['colabColaborador']); ?>">

            <div class="colab-content">
                <?php if ($filtro === 'colabPendiente'): ?>
                    <?php echo self::renderOpcionesColab($var); ?>
                    <?php echo self::renderContenidoColab($var); ?>
                <?php else: ?>
                    <div class="UICMCG">
                        <?php echo self::renderTituloColab($var); ?>
                        <?php echo self::renderParticipantesColab($var); ?>
                        <button class="cerrarColab" id-post="<?php echo esc_attr($postId); ?>">
                            <?php echo $GLOBALS['cancelicon'] ?? ''; ?>
                        </button>
                    </div>
                    <div class="MXPLYN">
                        <?php echo self::renderChatColab($var); ?>
                    </div>
                <?php endif; ?>
            </div>
        </li>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar mensaje de funcionalidad no disponible.
     * 
     * @return string HTML del mensaje.
     */
    public static function renderMensajeNoDisponible(): string
    {
        ob_start();
    ?>
        <div class="FLXVTQ">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <p>La funcionalidad de colaboración aún no esta disponible</p>
                <button class="borde">Volver</button>
            </a>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar vista de test de colaboraciones.
     * 
     * @return string HTML de la vista de test.
     */
    public static function renderColabTest(): string
    {
        ob_start();
    ?>
        <div class="IBPDFF">
            <div>
                <div>Colab pendientes</div>
                <?php
                if (function_exists('publicaciones')) {
                    echo publicaciones([
                        'post_type' => 'colab',
                        'filtro'    => 'colabPendiente',
                        'posts'     => 20
                    ]);
                }
                ?>
            </div>
            <div></div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar opciones de colaboración pendiente.
     * 
     * @param array $var Variables de la colaboración.
     * @return string HTML de las opciones.
     */
    public static function renderOpcionesColab(array $var): string
    {
        $postId                = $var['post_id'];
        $colabColaborador      = $var['colabColaborador'];
        $colabColaboradorAvatar = $var['colabColaboradorAvatar'];
        $colabColaboradorName  = $var['colabColaboradorName'];
        $colabFecha            = $var['colabFecha'];
        $iconoTresPuntos       = $GLOBALS['iconotrespuntos'] ?? '';

        ob_start();
    ?>
        <div class="GFOPNU">
            <div class="CBZNGK">
                <a href="<?php echo esc_url(get_author_posts_url($colabColaborador)); ?>"></a>
                <img src="<?php echo esc_url($colabColaboradorAvatar); ?>" alt="">
            </div>

            <div class="ZVJVZA">
                <div class="JHVSFW">
                    <a href="<?php echo esc_url(get_author_posts_url($colabColaborador)); ?>" class="profile-link">
                        <?php echo esc_html($colabColaboradorName); ?>
                    </a>
                </div>
                <div class="HQLXWD">
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="post-link">
                        <?php echo esc_html($colabFecha); ?>
                    </a>
                </div>
            </div>

            <div class="flex gap-3 justify-end ml-auto">
                <button data-post-id="<?php echo esc_attr($postId); ?>" class="botonsecundario rechazarcolab">Rechazar</button>
                <button data-post-id="<?php echo esc_attr($postId); ?>" class="botonprincipal aceptarcolab">Aceptar</button>
                <button data-post-id="<?php echo esc_attr($postId); ?>" class="botonsecundario submenucolab">
                    <?php echo $iconoTresPuntos; ?>
                </button>
            </div>

            <div class="A1806241" id="opcionescolab-<?php echo esc_attr($postId); ?>">
                <div class="A1806242">
                    <button class="reporte" data-post-id="<?php echo esc_attr($postId); ?>" tipoContenido="colab">Reportar</button>
                    <button class="bloquear" data-post-id="<?php echo esc_attr($postId); ?>">Bloquear</button>
                    <button class="mensajeBoton" data-receptor="<?php echo esc_attr($colabColaborador); ?>">Enviar mensaje</button>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar contenido de colaboración (archivos adjuntos).
     * 
     * @param array $var Variables de la colaboración.
     * @return string HTML del contenido.
     */
    public static function renderContenidoColab(array $var): string
    {
        $postId        = $var['post_id'];
        $colabMensaje  = $var['colabMensaje'];
        $postAudioLite = $var['post_audio_lite'];
        $colabFileUrl  = $var['colabFileUrl'];
        $fileGrande    = $GLOBALS['fileGrande'] ?? '';

        ob_start();
    ?>
        <div class="XZAKCB">
            <div class="BCGWEY">
                <span class="badge ver-contenido" data-post-id="<?php echo esc_attr($postId); ?>">Ver contenido</span>
            </div>
            <div class="colabfiles" id="colabfiles-<?php echo esc_attr($postId); ?>" style="display: none;">
                <?php if (!empty($postAudioLite)): ?>
                    <div class="DNPHZG">
                        <?php echo self::renderAudioColab($postId, $postAudioLite); ?>
                    </div>
                <?php else: ?>
                    <div class="AIWZKN">
                        <?php if (!empty($colabFileUrl)): ?>
                            <?php $fileName = basename($colabFileUrl); ?>
                            <a href="<?php echo esc_url($colabFileUrl); ?>" download class="file-download no-ajax">
                                <div class="XQGSAN">
                                    <?php echo $fileGrande; ?>
                                    <?php echo esc_html($fileName); ?>
                                </div>
                            </a>
                            <p class="textoMuyPequeno">
                                El archivo ha sido analizado y no se encontraron virus. Sin embargo, si no confías en la persona que realizó la solicitud, no descargues archivos.
                            </p>
                            <p class="mensajeColab">Mensaje de solicitud: <?php echo esc_html($colabMensaje); ?></p>
                        <?php else: ?>
                            <p>No hay archivo adjunto.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar reproductor de audio para colaboración.
     * 
     * @param int    $postId      ID del post.
     * @param string $audioIdLite ID del audio.
     * @return string HTML del audio.
     */
    public static function renderAudioColab(int $postId, string $audioIdLite): string
    {
        $wave        = get_post_meta($postId, 'waveform_image_url', true);
        $waveCargada = get_post_meta($postId, 'waveCargada', true);
        $urlAudioSegura = function_exists('audioUrlSegura') ? audioUrlSegura($audioIdLite) : '';

        ob_start();
    ?>
        <div id="waveform-<?php echo esc_attr($postId); ?>"
            class="waveform-container without-image"
            postIDWave="<?php echo esc_attr($postId); ?>"
            data-wave-cargada="<?php echo $waveCargada ? 'true' : 'false'; ?>"
            data-audio-url="<?php echo esc_url($urlAudioSegura); ?>">
            <div class="waveform-background" style="background-image: url('<?php echo esc_url($wave); ?>');"></div>
            <div class="waveform-message"></div>
            <div class="waveform-loading" style="display: none;">Cargando...</div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar título de colaboración.
     * 
     * @param array $var Variables de la colaboración.
     * @return string HTML del título.
     */
    public static function renderTituloColab(array $var): string
    {
        $imagenPostOp = $var['imagenPostOp'];
        $postTitulo   = $var['postTitulo'];
        $colabFecha   = $var['colabFecha'];

        ob_start();
    ?>
        <div class="MJYQLF">
            <div class="YXJIKK">
                <img src="<?php echo esc_url($imagenPostOp); ?>" alt="">
            </div>
            <div class="SNVKQC">
                <p><?php echo esc_html($postTitulo); ?></p>
                <a href="<?php echo esc_url(get_permalink()); ?>" class="post-link">
                    <?php echo esc_html($colabFecha); ?>
                </a>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar avatares de participantes.
     * 
     * @param array $var Variables de la colaboración.
     * @return string HTML de los participantes.
     */
    public static function renderParticipantesColab(array $var): string
    {
        $colabColaboradorAvatar = $var['colabColaboradorAvatar'];
        $colabAutorAvatar       = $var['colabAutorAvatar'];

        ob_start();
    ?>
        <div class="LIFVXC">
            <img src="<?php echo esc_url($colabColaboradorAvatar); ?>" alt="">
            <img src="<?php echo esc_url($colabAutorAvatar); ?>" alt="">
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar opciones para colaboración activa.
     * 
     * @param array $var Variables de la colaboración.
     * @return string HTML de las opciones.
     */
    public static function renderOpcionesColabActivo(array $var): string
    {
        $postId          = $var['post_id'];
        $iconoTresPuntos = $GLOBALS['iconotrespuntos'] ?? '';

        ob_start();
    ?>
        <button data-post-id="<?php echo esc_attr($postId); ?>" class="botonsecundario submenucolab">
            <?php echo $iconoTresPuntos; ?>
        </button>

        <div class="A1806241" id="opcionescolab-<?php echo esc_attr($postId); ?>">
            <div class="A1806242">
                <button class="reporte" data-post-id="<?php echo esc_attr($postId); ?>" tipoContenido="colab">Reportar</button>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar chat de colaboración.
     * 
     * @param array $var Variables de la colaboración.
     * @return string HTML del chat.
     */
    public static function renderChatColab(array $var): string
    {
        $postId          = (int) $var['post_id'];
        $conversacionId  = (int) $var['conversacion_id'];
        $participantes   = $var['participantes'];
        $enviarMensaje   = $GLOBALS['enviarMensaje'] ?? '';
        $enviarAdjunto   = $GLOBALS['enviarAdjunto'] ?? '';

        if (is_array($participantes)) {
            $participantesJson = json_encode($participantes);
        } else {
            $participantesJson = $participantes;
        }
        $participantesEscaped = htmlspecialchars($participantesJson, ENT_QUOTES, 'UTF-8');

        ob_start();
    ?>
        <div class="borde bloqueChatColab"
            id="chatcolab-<?php echo esc_attr($postId); ?>"
            data-post-id="<?php echo esc_attr($postId); ?>"
            data-participantes="<?php echo $participantesEscaped; ?>"
            data-conversacion-id="<?php echo esc_attr($conversacionId); ?>">

            <ul class="listaMensajes"></ul>

            <div class="previewsForm NGEESM previewsChat" style="position: relative;">
                <div class="previewAreaArchivos previewChatImagen" id="previewChatImagen" style="display: none;">
                    <label>Imagen</label>
                </div>
                <div class="previewAreaArchivos previewChatAudio" id="previewChatAudio" style="display: none;">
                    <label>Audio</label>
                </div>
                <div class="previewAreaArchivos previewChatArchivo" id="previewChatArchivo" style="display: none;">
                    <label>Archivo</label>
                </div>
                <button class="cancelButton borde cancelUploadButton" id="cancelUploadButton" style="display: none;">Cancelar</button>
            </div>

            <div class="chatEnvio">
                <textarea class="mensajeContenidoColab borde" rows="1"></textarea>
                <button class="enviarMensajeColab borde"
                    data-post-id="<?php echo esc_attr($postId); ?>"
                    data-conversacion-id="<?php echo esc_attr($conversacionId); ?>">
                    <?php echo $enviarMensaje; ?>
                </button>
                <button class="enviarAdjunto" id="enviarAdjunto">
                    <?php echo $enviarAdjunto; ?>
                </button>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar resumen de colaboraciones del usuario.
     * 
     * @return string HTML del resumen.
     */
    public static function renderColabsResumen(): string
    {
        $colabs = self::getService()->obtenerColabsResumen();

        if (empty($colabs)) {
            return '';
        }

        ob_start();
    ?>
        <ol class="listaDeColabresumen">
            <?php foreach ($colabs as $colab): ?>
                <li class="colabResumen"
                    data-conversacion_id="<?php echo esc_attr($colab['conversacion_id']); ?>"
                    data-post_id="<?php echo esc_attr($colab['post_id']); ?>">
                    <img src="<?php echo esc_url($colab['imagen']); ?>"
                        class="colabResumenImagen"
                        alt="<?php echo esc_attr($colab['titulo']); ?>"
                        width="40">
                </li>
            <?php endforeach; ?>
        </ol>
<?php
        return ob_get_clean();
    }
}
