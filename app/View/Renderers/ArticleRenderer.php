<?php
// Funciones de renderizado para artículos (tipo 'post') movidas desde app/Content/Posts/htmlArticulo.php

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

// Refactor(Org): Función imagenArticulo movida a app/View/Helpers/ImageHelper.php

function htmlArticulo($filtro)
{
    $postId = get_the_ID();
    $vars = variablesArticulo($postId);
    extract($vars);
    // La función imagenArticulo ahora se encuentra en ImageHelper.php y se asume disponible globalmente
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
// Nota: add_theme_support('post-thumbnails') estaba en el archivo original y no se movió aquí.
// El archivo original app/Content/Posts/htmlArticulo.php fue eliminado según las instrucciones.
// Si add_theme_support es necesario globalmente, debería estar en functions.php o un archivo de configuración del tema.
