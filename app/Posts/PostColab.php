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
        autor="<?php echo esc_attr($author_id); ?>">

        <div class="colab-content">
            <div class="BMGCLT">
                
                <div>

                </div>

                <div>
                    <p></p>
                    <p></p>
                </div>
                
            </div>
        </div>
    </li>

<?php

    return ob_get_clean();
}
