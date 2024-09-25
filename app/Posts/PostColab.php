<?php

function htmlColab($filtro)
{
    $post_id = get_the_ID();
    $var = variablesColab($post_id);
    extract($var);
    ob_start();
?>


    <li class="POST-<?php echo esc_attr($filtro); ?> EDYQHV"
        filtro="<?php echo esc_attr($filtro); ?>"
        id-post="<?php echo get_the_ID(); ?>"
        autor="<?php echo esc_attr($colabAutor); ?>">

        <div class="colab-content">

            <div class="BMGCLT">

                <div class="CBZNGK">
                    <a href="<?php echo esc_url(get_author_posts_url($colabAutor)); ?>"></a>
                    <img src="<?php echo esc_url($author_avatar); ?>">
                </div>

                <div class="ZVJVZA">
                    <div class="JHVSFW">
                        <a href="<?php echo esc_url(get_author_posts_url($colabAutor)); ?>" class="profile-link">
                            <?php echo esc_html($colabAutorName); ?></a>
                    </div>
                    <div class="HQLXWD">
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="post-link"><?php echo esc_html($post_date); ?></a>
                    </div>
                </div>

                <button>Test</button>

            </div>
        </div>
    </li>

<?php

    return ob_get_clean();
}
