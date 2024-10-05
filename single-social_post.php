<? get_header(); ?>
<?
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : ''; 


if (have_posts()) :
    while (have_posts()) : the_post();
        $filtro = 'single';
        ob_start();
?>
        <div id="main">
            <div id="content" class="<? echo esc_attr($nologin_class); ?>">
                <div class="single">
                    <? echo htmlPost($filtro); ?>
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