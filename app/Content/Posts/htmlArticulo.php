<?


function variablesArticulo($postId)
{
    // Si no se proporciona un postId, usa el ID del post global.
    if ($postId === null) {
        global $post;
        $postId = $post->ID;
    }

    $usuarioActual = get_current_user_id();
    $autorId = get_post_field('post_author', $postId);


    return [
        'fecha' => get_the_date('', $postId),
        'colecStatus' => get_post_status($postId),
        'autorId' => $autorId,
    ];
}

function htmlArticulo($filtro)
{
    $post_id = get_the_ID();
    $vars = variablesArticulo($post_id);
    extract($vars);
    ob_start();
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($autorId); ?>">

        <div class="post-content">
            <? echo imagenColeccion($postId); ?>
            <h2 class="post-title" data-post-id="<? echo $postId; ?>"><? echo get_the_title($postId); ?></h2>
            <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>
        </div>

    </li>

    <li class="comentariosPost">

    </li>
<?
    return ob_get_clean();
}
