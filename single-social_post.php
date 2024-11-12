<!DOCTYPE html>
<html <? language_attributes(); ?>>

<head>
    <!-- Ejemplo de meta para pruebas -->
    <meta name="robots" content="index, follow">

    <? wp_head(); ?>
</head>

<body <? body_class(); ?>>

    <?
    // Código  que tienes en tu single.
    $user_id = get_current_user_id();
    $acciones = get_user_meta($user_id, 'acciones', true);
    $nologin_class = !is_user_logged_in() ? ' nologin' : '';

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