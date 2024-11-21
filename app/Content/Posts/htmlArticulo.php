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
    $post_id = get_the_ID();
    $vars = variablesArticulo($post_id);
    extract($vars);
    $imagenUrl = imagenArticulo($postId);

    ob_start();
?>
    <a href="<? echo esc_url(get_permalink($post_id)); ?>" class="post-link">
        <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
            filtro="<? echo esc_attr($filtro); ?> "
            id-post="<? echo esc_attr($post_id); ?> "
            autor="<? echo esc_attr($autorId); ?>"
            style="background-image: url('<? echo esc_url($imagenUrl); ?>'); background-size: cover; background-position: center; position: relative;">
            
            <!-- Capa superpuesta para resaltar el texto -->
            <div class="overlay"></div>

            <!-- Contenido del post -->
            <div class="post-content">
                <h2 class="post-title" data-post-id="<? echo esc_attr($postId); ?>"><? echo get_the_title($postId); ?></h2>
                <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>
            </div>

        </li>
    </a>

    <li class="comentariosPost"></li>
<?php
    return ob_get_clean();
}

