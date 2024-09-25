<?php

function htmlColab($filtro)
{
    $post_id = get_the_ID();
    $var = variablesColab($post_id);
    extract($var);
    ob_start();
    // DNPHZG JMLZBN JWTUQY QKEUKJ
?>


    <li class="POST-<?php echo esc_attr($filtro); ?> EDYQHV"
        filtro="<?php echo esc_attr($filtro); ?>"
        id-post="<?php echo get_the_ID(); ?>"
        autor="<?php echo esc_attr($colabAutor); ?>">

        <div class="colab-content">

            <div class="GFOPNU">

                <div class="CBZNGK">
                    <a href="<?php echo esc_url(get_author_posts_url($colabColaborador)); ?>"></a>
                    <img src="<?php echo esc_url($colabColaboradorAvatar); ?>">
                </div>

                <div class="ZVJVZA">
                    <div class="JHVSFW">
                        <a href="<?php echo esc_url(get_author_posts_url($colabColaborador)); ?>" class="profile-link">
                            <?php echo esc_html($colabColaboradorName); ?></a>
                    </div>
                    <div class="HQLXWD">
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="post-link"><?php echo esc_html($colab_date); ?></a>
                    </div>
                </div>

                <div class="flex gap-3 justify-end">
                    <button class="botonsecundario">Test</button>
                    <button class="botonprincipal">Test</button>
                </div>

            </div>
        </div>
    </li>

<?php

    return ob_get_clean();
}
