<?php

/**
 * Componente de vista para botones de like.
 * 
 * Renderiza los botones de like, favorito y dislike para posts.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

use Kamples\Services\Social\LikeService;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class LikeButtons
{
    /**
     * Servicio de likes.
     * 
     * @var LikeService
     */
    private LikeService $likeService;

    /**
     * Constructor.
     *
     * @param LikeService|null $likeService Servicio de likes.
     */
    public function __construct(?LikeService $likeService = null)
    {
        $this->likeService = $likeService ?? new LikeService();
    }

    /**
     * Renderizar botones de like para un post.
     *
     * @param int $postId ID del post.
     * @return string HTML de los botones.
     */
    public function render(int $postId): string
    {
        $userId = get_current_user_id();

        // Obtener contadores
        $contadores = $this->likeService->obtenerContadores($postId);

        // Obtener estado del usuario
        $estado = $this->likeService->obtenerEstadoUsuario($postId, $userId);

        // Determinar clases CSS
        $claseLike = $estado['like'] ? 'liked' : 'not-liked';
        $claseFavorito = $estado['favorito'] ? 'liked' : 'not-liked';
        $claseDislike = $estado['no_me_gusta'] ? 'liked' : 'not-liked';

        // Nonce de seguridad
        $nonce = wp_create_nonce('like_post_nonce');

        // Iconos globales
        $iconoCorazon = $GLOBALS['iconoCorazon'] ?? '';
        $iconoEstrella = $GLOBALS['estrella'] ?? '';
        $iconoDislike = $GLOBALS['dislike'] ?? '';

        ob_start();
?>
        <div class="TJKQGJ botonlike-container" id="botonlike-<?= esc_attr($postId) ?>">
            <button class="post-like-button <?= esc_attr($claseLike) ?>"
                data-post_id="<?= esc_attr($postId) ?>"
                data-like_type="like"
                data-nonce="<?= esc_attr($nonce) ?>">
                <?= $iconoCorazon ?> <span class="like-count"><?= esc_html($contadores['like']) ?></span>
            </button>
            <div class="botones-extras">
                <button class="post-favorite-button <?= esc_attr($claseFavorito) ?>"
                    data-post_id="<?= esc_attr($postId) ?>"
                    data-like_type="favorito"
                    data-nonce="<?= esc_attr($nonce) ?>">
                    <?= $iconoEstrella ?> <span class="favorite-count"><?= esc_html($contadores['favorito']) ?></span>
                </button>
                <button class="post-dislike-button <?= esc_attr($claseDislike) ?>"
                    data-post_id="<?= esc_attr($postId) ?>"
                    data-like_type="no_me_gusta"
                    data-nonce="<?= esc_attr($nonce) ?>">
                    <?= $iconoDislike ?> <span class="dislike-count"><?= esc_html($contadores['no_me_gusta']) ?></span>
                </button>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Método estático para uso rápido.
     *
     * @param int $postId ID del post.
     * @return string HTML de los botones.
     */
    public static function mostrar(int $postId): string
    {
        $instance = new self();
        return $instance->render($postId);
    }
}

/**
 * Función global para compatibilidad con código existente.
 * 
 * @param int $postId ID del post.
 * @return string HTML de los botones.
 */
function like(int $postId): string
{
    return LikeButtons::mostrar($postId);
}
