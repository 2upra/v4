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
        autor="<?php echo esc_attr($colabColaborador); ?>">

        <div class="colab-content">
            <?php echo opcionesColab($post_id, $colabColaborador, $colabColaboradorAvatar, $colabColaboradorName, $colab_date); ?>
            <?php echo contenidoColab($post_id, $colabMensaje, $post_audio_lite, $colabFileUrl); ?>
        </div>
    </li>

<?php
    return ob_get_clean();
}

function colab()
{
    ob_start()
?>
    <div class="IBPDFF">
        <div>
            <div>Colab pendientes</div>
            <?php echo publicaciones(['post_type' => 'colab', 'filtro' => 'colabPendiente', 'posts' => 20]); ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}
