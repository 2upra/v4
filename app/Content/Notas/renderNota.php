<?

function formNotasUL()
{
    $filtro = 'notas';

    ob_start();
?>
    <ul class="social-post-list clase-notas">
        <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
            filtro="<? echo esc_attr($filtro); ?>">

            <div class="contenidoNota agregarNuevaNota" id="agregarNuevaNota">
                <p class="contenidoNotaP">Escribir una nueva nota</p>
            </div>
            <div class="botonesNotasGenerales">
                <button><? echo $GLOBALS['borradorIcon']; ?></button>
            </div>
        </li>
    </ul>
<?
    return ob_get_clean();
}

function formNotas()
{
    $filtro = 'notas';

    ob_start();
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>">

        <div class="contenidoNota agregarNuevaNota" id="agregarNuevaNota">
            <p class="contenidoNotaP">Escribir una nueva nota</p>
        </div>
        <div class="botonesNotasGenerales">
            <button class="borrarLasNotas"><? echo $GLOBALS['borradorIcon']; ?></button>
        </div>
    </li>
<?
    return ob_get_clean();
}

function htmlNotas($filtro)
{
    $notaId = get_the_id();
    $contenido = get_the_content();
    $autorId = get_post_field('post_author', $notaId);

    ob_start();
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV <? echo $notaId; ?> "
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo $notaId; ?>"
        autor="<? echo esc_attr($autorId); ?>"
        draggable="true">

        <div class="contenidoNota notaPublicada" id-post="<? echo $notaId; ?>">
            <p class="contenidoNotaP"><? echo $contenido; ?></p>
        </div>
        <div class="botonesNotas">
            <button class="editarNota" style="display: none;" data-post-id="<? echo esc_attr($notaId); ?>">
                <? echo $GLOBALS['lapizIcon']; ?>
            </button>
            <button class="eliminarPost" data-post-id="<? echo esc_attr($notaId); ?>">
                <? echo $GLOBALS['papeleraV2']; ?>
            </button>
        </div>
    </li>
<?
    return ob_get_clean();
}
