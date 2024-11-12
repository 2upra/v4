<?
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';

?>

<head>
    <meta charset="<? bloginfo('charset'); ?>">
    <title><? echo get_the_title(); ?> | <? bloginfo('name'); ?></title>
    <meta name="description" content="<? echo strip_tags(get_the_excerpt()); ?>">
    <p>test</p>
    <!-- Agrega el resto de las etiquetas del head necesarias -->
    <? wp_head(); ?>
</head>

<?
if (have_posts()) :
    while (have_posts()) : the_post();
        ob_start();
?>

        <div id="main">
            <div id="content" class="<? echo esc_attr($nologin_class); ?>">
                <div class="single">
                    <div class="fullH">
                        <? echo htmlPost($filtro); ?>
                    </div>
                    <!-- Publicaciones Similares -->
                    <div class="publicaciones-similares" nosnippet>
                        <h3>Publicaciones Similares</h3>
                        <?
                        echo publicaciones([
                            'filtro' => 'nada',
                            'posts' => 10,
                            'similar_to' => $current_post_id,
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>


<?
        $content = ob_get_clean();
        echo $content;
        get_footer();
    endwhile;
endif;
?>