<?php

/**
 * Sistema de likes/favoritos/dislikes (Archivo Legacy).
 * 
 * Este archivo mantiene funciones wrapper para compatibilidad con código existente.
 * La lógica real está en Theme\V4\Services\LikeService y Theme\V4\Controllers\LikeController.
 *
 * @package Theme_V4
 * @since 1.0.0
 * @deprecated Las funciones serán eliminadas cuando todo el código use las clases.
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Theme\V4\Services\LikeService;
use Theme\V4\Controllers\LikeController;

/* 
 *
 * Inicialización del controlador AJAX
 * Registra las acciones de WordPress para manejar solicitudes AJAX
 *
 */

$likeController = new LikeController();
$likeController->registrar();

/* 
 *
 * Funciones wrapper para compatibilidad
 * Estas funciones existen para no romper código que las use directamente
 *
 */

/**
 * Manejar solicitud AJAX de like.
 * 
 * @deprecated Usar LikeController::manejarLike() directamente.
 */
function manejarLike(): void
{
    $controller = new LikeController();
    $controller->manejarLike();
}

/**
 * Ejecutar acción de like/unlike en base de datos.
 *
 * @param int    $postId   ID del post.
 * @param int    $userId   ID del usuario.
 * @param string $accion   Acción a realizar.
 * @param string $likeType Tipo de like.
 * @deprecated Usar LikeService::ejecutarAccion() directamente.
 */
function likeAccion($postId, $userId, $accion, $likeType = 'like'): void
{
    $servicio = new LikeService();
    $servicio->ejecutarAccion((int) $postId, (int) $userId, $accion, $likeType);
}

/**
 * Contar likes de un post.
 *
 * @param int         $postId   ID del post.
 * @param string|null $likeType Tipo de like (null para likes + favoritos).
 * @return int
 * @deprecated Usar LikeService::contarReacciones() directamente.
 */
function contarLike($postId, $likeType = null): int
{
    $servicio = new LikeService();
    return $servicio->contarReacciones((int) $postId, $likeType);
}

/**
 * Verificar si usuario tiene like en un post.
 *
 * @param int    $postId   ID del post.
 * @param int    $userId   ID del usuario.
 * @param string $likeType Tipo de like.
 * @return bool
 * @deprecated Usar LikeService::usuarioTieneReaccion() directamente.
 */
function chequearLike($postId, $userId, $likeType = 'like'): bool
{
    $servicio = new LikeService();
    return $servicio->usuarioTieneReaccion((int) $postId, (int) $userId, $likeType);
}

/**
 * Renderizar botones de like para un post.
 *
 * @param int $postId ID del post.
 * @return string HTML de los botones.
 */
function like($postId): string
{
    $servicio = new LikeService();
    $userId = get_current_user_id();

    // Obtener contadores
    $contadores = $servicio->obtenerContadores((int) $postId);

    // Obtener estado del usuario
    $estado = $servicio->obtenerEstadoUsuario((int) $postId, $userId);

    // Determinar clases CSS
    $claselike = $estado['like'] ? 'liked' : 'not-liked';
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
    <div class="TJKQGJ botonlike-container">
        <button class="post-like-button <?= esc_attr($claselike) ?>"
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
