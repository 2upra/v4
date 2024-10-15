<?php get_header(); ?>
<?php
// single-social_post.php

$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : ''; 

if (have_posts()) :
    while (have_posts()) : the_post();
        $filtro = 'single';
        ob_start();
        
        // Obtener el ID del post actual
        $current_post_id = get_the_ID();
?>
        <div id="main">
            <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
                <div class="single">
                    <?php echo htmlPost($filtro); ?>
                    
                    <!-- Publicaciones similares -->
                    <div class="publicaciones-similares">
                        <h3>Publicaciones Similares</h3>
                        <?php
                        // Llamar a la función 'publicaciones' pasando el ID del post actual
                        echo publicaciones([
                            'filtro' => 'nada',
                            'posts' => 10,
                            'similar_to' => $current_post_id
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        $contenido = ob_get_clean(); // Captura el contenido del buffer y lo limpia
        echo $contenido; // Muestra el contenido capturado
    endwhile;
else :
    echo '<p>No se encontró el contenido.</p>';
endif;
?>

<?php get_footer(); ?>