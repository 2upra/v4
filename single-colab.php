<? get_header(); ?>
<?
// single-social_post.php

$user_id = get_current_user_id();
if (have_posts()) :
    while (have_posts()) : the_post();
        $filtro = 'colab';
        ob_start();
        
        // Obtener el ID del post actual
        $current_post_id = get_the_ID();
?>
        <div id="main">
            <div id="content" class="<? echo esc_attr($nologin_class); ?>">
                <div class="single colabSingle">
                    <? echo htmlColab($filtro); ?>
                </div>
            </div>
        </div>
<?
        $contenido = ob_get_clean(); // Captura el contenido del buffer y lo limpia
        echo $contenido; // Muestra el contenido capturado
    endwhile;
else :
    echo '<p>No se encontró el contenido.</p>';
endif;
?>

<? get_footer(); ?>