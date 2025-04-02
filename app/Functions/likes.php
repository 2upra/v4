<?php
// Funciones de manejo de likes movidas a app/Services/LikeService.php

function like($postId)
{
    $userId = get_current_user_id();

    // Usa las funciones movidas (asumiendo que están disponibles globalmente o a través de un servicio)
    $contadorLike = contarLike($postId);
    $user_has_liked = chequearLike($postId, $userId, 'like');
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    $contadorFavorito = contarLike($postId, 'favorito');
    $user_has_favorited = chequearLike($postId, $userId, 'favorito');
    $favorited_class = $user_has_favorited ? 'liked' : 'not-liked';

    $contadorNoMeGusta = contarLike($postId, 'no_me_gusta');
    $user_has_disliked = chequearLike($postId, $userId, 'no_me_gusta');
    $disliked_class = $user_has_disliked ? 'liked' : 'not-liked';

    ob_start();
?>
    <div class="TJKQGJ botonlike-container">
        <button class="post-like-button <?= esc_attr($liked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="like" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            <? echo $GLOBALS['iconoCorazon']; ?> <span class="like-count"><?= esc_html($contadorLike) ?></span>
        </button>
        <div class="botones-extras">
            <button class="post-favorite-button <?= esc_attr($favorited_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="favorito" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
                <? echo $GLOBALS['estrella']; ?> <span class="favorite-count"><?= esc_html($contadorFavorito) ?></span>
            </button>
            <button class="post-dislike-button <?= esc_attr($disliked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="no_me_gusta" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
                <? echo $GLOBALS['dislike']; ?> <span class="dislike-count"><?= esc_html($contadorNoMeGusta) ?></span>
            </button>
        </div>
    </div>
<?
    $output = ob_get_clean();
    return $output;
}
?>