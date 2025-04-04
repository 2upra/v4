<?

function imagenArticulo($postId)
{
    $imagenSize = 'large';
    $quality = 60;
    $imagenUrl = imagenPost($postId, $imagenSize, $quality, 'all', false, true);
    $imagenProcesada = img($imagenUrl, $quality, 'all');

    return esc_url($imagenProcesada);
}

function htmlArticulo($filtro)
{
    $postId = get_the_ID();
    $vars = variablesArticulo($postId);
    extract($vars);
    $imagenUrl = imagenArticulo($postId);

    ob_start();
?>
    <a href="<? echo esc_url(get_permalink($postId)); ?>" class="post-link">
        <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
            filtro="<? echo esc_attr($filtro); ?> "
            id-post="<? echo esc_attr($postId); ?> "
            autor="<? echo esc_attr($autorId); ?>"
            style="background-image: url('<? echo esc_url($imagenUrl); ?>'); background-size: cover; background-position: center; position: relative;">
            
            <div class="overlay"></div>
            <div class="post-content">
                <h2 class="post-title" data-post-id="<? echo esc_attr($postId); ?>"><? echo get_the_title($postId); ?></h2>
                <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>
            </div>

        </li>
    </a>

    <li class="comentariosPost"></li>
<?
    return ob_get_clean();
}
add_theme_support('post-thumbnails');
