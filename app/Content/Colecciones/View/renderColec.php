<?

function htmlColec($filtro)
{
    ob_start();
    $postId = get_the_ID();
    $vars = variablesColec($postId);
    extract($vars);
    ?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo esc_attr($postId); ?>"
        autor="<? echo esc_attr($autorId); ?>">

        <div class="post-content">
            <? echo imagenColeccion($postId); ?>
            <h2 class="post-title"><? echo get_the_title($postId); ?></h2>
            <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>

        </div>
    </li>
    <?
    return ob_get_clean();
}


function variablesColec($postId)
{

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

function imagenColeccion($postId)
{
    $imagenSize = 'medium';
    $quality = 60;
    $image_url = imagenPost($postId, $imagenSize, $quality, 'all');
    $processed_image_url = img($image_url, $quality, 'all');
    ob_start();
?>
    <div class="post-image-container">
        <a href="<? esc_url(get_permalink($postId)); ?>">
            <img src="<? esc_url($processed_image_url); ?>" alt="Post Image" />
        </a>
    </div>
<?

    $output = ob_get_clean();

    return $output;
}
