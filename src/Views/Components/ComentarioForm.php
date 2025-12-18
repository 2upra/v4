<?php

/**
 * Componente de vista para el formulario de comentarios.
 * 
 * Renderiza el formulario para agregar comentarios a un post.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

use Kamples\Services\Usuario\PerfilService;

/* Evitar acceso directo */

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ComentarioForm
{
    /**
     * Renderizar el formulario de comentarios.
     *
     * @return string HTML del formulario.
     */
    public static function render(): string
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $usuario = wp_get_current_user();
        $nombreUsuario = esc_html($usuario->display_name);
        $urlImagenPerfil = PerfilService::obtenerInstancia()->obtenerImagenPerfil($usuario->ID);

        ob_start();
?>
        <div class="bloque anadircomentario" id="rsComentario" style="display: none;">
            <div class="W8DK25">
                <img id="perfil-imagen"
                    src="<?php echo esc_url($urlImagenPerfil); ?>"
                    alt="Perfil"
                    style="max-width: 35px; max-height: 35px; border-radius: 50%;">
                <p><?php echo $nombreUsuario; ?></p>
            </div>

            <div>
                <textarea id="comentContent"
                    name="comentContent"
                    rows="1"
                    required
                    placeholder="Escribe tu comentario"></textarea>
            </div>

            <div class="previevsComent" id="previevsComent" style="display: none;">
                <div class="previewAreaArchivos pimagen" id="pcomentImagen" style="display: none;">
                    <label></label>
                </div>
                <div class="previewAreaArchivos paudio" id="pcomentAudio" style="display: none;">
                    <label></label>
                </div>
            </div>

            <div class="botonesForm R0A915">
                <?php if (isset($GLOBALS['subiraudio'])): ?>
                    <button class="botonicono borde" id="audioComent">
                        <?php echo $GLOBALS['subiraudio']; ?>
                    </button>
                <?php endif; ?>

                <?php if (isset($GLOBALS['subirimagen'])): ?>
                    <button class="botonicono borde" id="imagenComent">
                        <?php echo $GLOBALS['subirimagen']; ?>
                    </button>
                <?php endif; ?>

                <?php if (isset($GLOBALS['subirarchivo'])): ?>
                    <button class="botonicono borde" id="ArchivoComent" style="display: none;">
                        <?php echo $GLOBALS['subirarchivo']; ?>
                    </button>
                <?php endif; ?>

                <button class="borde" id="enviarComent">Publicar</button>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
