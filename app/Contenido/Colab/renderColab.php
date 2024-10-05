<?

function htmlColab($filtro)
{
    $post_id = get_the_ID();
    $var = variablesColab($post_id);
    extract($var); // extrae variables de $var como $colabColaborador, etc.
    ob_start();
?>

    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($colabColaborador); ?>">

        <div class="colab-content">
            <? if ($filtro === 'colabPendiente'): ?>
                <? echo opcionesColab($post_id, $colabColaborador, $colabColaboradorAvatar, $colabColaboradorName, $colab_date); ?>
                <? echo contenidoColab($post_id, $colabMensaje, $post_audio_lite, $colabFileUrl); ?>
            <? else: ?>
                <div>
                    <div><? //echo tituloColab(); ?></div>
                    <div><? //echo participantesColab() ?></div>
                    <div><? //echo opcionesColaActivo($post_id, $colabColaborador, $colabColaboradorAvatar, $colabColaboradorName, $colab_date); ?> </div>
                </div>
                <div><? //echo pestanasColab(); ?></div>
                <div><? //echo chatColab(); ?></div>
                <div><? //echo archivosColab(); ?></div>
                <div><? //echo historialColab(); ?></div>
                <div><? //echo comandosColab(); ?></div>
                <div><? //echo enviarColab();?></div>
            <? endif; ?>

        </div>
    </li>

<?
    return ob_get_clean();
}

function colab()
{
    ob_start() ?>

    <div class="FLXVTQ">
        <a href="https://2upra.com/">
            <p>La funcionalidad de colaboración aún no esta disponible</p>
            <button class="borde">Volver</button>
        </a>
    </div>


<? return ob_get_clean();
}

function colabTest()
{
    ob_start();
?>
    <div class="IBPDFF">
        <div>
            <div>Colab pendientes</div>
            <? echo publicaciones(['post_type' => 'colab', 'filtro' => 'colabPendiente', 'posts' => 20]); ?>
        </div>
        <div>
            <? echo publicaciones(['post_type' => 'colab', 'filtro' => 'colab', 'posts' => 20]); ?>
        </div>
    </div>
<?
    return ob_get_clean();
}
