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

                <div class="flex gap-3 justify-end ml-auto">
                    <button class="botonsecundario">Rechazar</button>
                    <button class="botonprincipal">Aceptar</button>
                </div>

            </div>

            <div class="XZAKCB">
                <p><?php echo esc_html($colabMensaje); ?></p>
                <?php if (!empty($post_audio_lite)) : ?>
                    <div class="DNPHZG">
                        <?php echo audioColab($post_id, $post_audio_lite); ?>
                    </div>
                <?php else : ?>
                    <div class="AIWZKN">
                        <?php if (!empty($colabFileUrl)) : ?>
                            <?php
                            $file_name = basename($colabFileUrl);
                            ?>
                            <a href="<?php echo esc_url($colabFileUrl); ?>" download class="file-download no-ajax">
                                <div class="XQGSAN">
                                    <?php echo $GLOBALS['fileGrande']; ?>
                                    <?php echo esc_html($file_name); ?>
                                </div>
                            </a>
                            <p class="textoMuyPequeno">
                                El archivo ha sido analizado y no se encontraron virus. Sin embargo, si no confías en la persona que realizó la solicitud, no descargues archivos. Asegúrate de mantener siempre tu sistema operativo actualizado y reporta cualquier abuso.
                            </p>
                        <?php else : ?>
                            <p>No hay archivo adjunto.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </li>

<?php

    return ob_get_clean();
}
