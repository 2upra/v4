<?php

// Refactor(Exec): Mueve función like() desde UIHelper.php
function like($postId)
{
    $userId = get_current_user_id();

    // Usa las funciones movidas (asumiendo que están disponibles globalmente o a través de un servicio)
    // Asegúrate de que las funciones contarLike() y chequearLike() estén definidas o incluidas.
    // Si estas funciones estaban en app/Functions/likes.php, asegúrate de que ese archivo se cargue.
    $contadorLike = function_exists('contarLike') ? contarLike($postId) : 0;
    $user_has_liked = function_exists('chequearLike') ? chequearLike($postId, $userId, 'like') : false;
    $liked_class = $user_has_liked ? 'liked' : 'not-liked';

    $contadorFavorito = function_exists('contarLike') ? contarLike($postId, 'favorito') : 0;
    $user_has_favorited = function_exists('chequearLike') ? chequearLike($postId, $userId, 'favorito') : false;
    $favorited_class = $user_has_favorited ? 'liked' : 'not-liked';

    $contadorNoMeGusta = function_exists('contarLike') ? contarLike($postId, 'no_me_gusta') : 0;
    $user_has_disliked = function_exists('chequearLike') ? chequearLike($postId, $userId, 'no_me_gusta') : false;
    $disliked_class = $user_has_disliked ? 'liked' : 'not-liked';

    ob_start();
?>
    <div class="TJKQGJ botonlike-container">
        <button class="post-like-button <?= esc_attr($liked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="like" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
            <?php echo isset($GLOBALS['iconoCorazon']) ? $GLOBALS['iconoCorazon'] : 'Like'; ?> <span class="like-count"><?= esc_html($contadorLike) ?></span>
        </button>
        <div class="botones-extras">
            <button class="post-favorite-button <?= esc_attr($favorited_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="favorito" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
                <?php echo isset($GLOBALS['estrella']) ? $GLOBALS['estrella'] : 'Fav'; ?> <span class="favorite-count"><?= esc_html($contadorFavorito) ?></span>
            </button>
            <button class="post-dislike-button <?= esc_attr($disliked_class) ?>" data-post_id="<?= esc_attr($postId) ?>" data-like_type="no_me_gusta" data-nonce="<?= wp_create_nonce('like_post_nonce') ?>">
                <?php echo isset($GLOBALS['dislike']) ? $GLOBALS['dislike'] : 'Dislike'; ?> <span class="dislike-count"><?= esc_html($contadorNoMeGusta) ?></span>
            </button>
        </div>
    </div>
<?php
    $output = ob_get_clean();
    return $output;
}

// Nota: Las funciones contarLike() y chequearLike() se asumen disponibles globalmente.
// Originalmente estaban en app/Functions/likes.php. Asegúrate de que ese archivo se cargue
// o que estas funciones se muevan a un lugar accesible (p.ej., este mismo archivo o un helper de lógica de likes).
// También se asume que las variables globales $GLOBALS['iconoCorazon'], $GLOBALS['estrella'], $GLOBALS['dislike'] están definidas.

