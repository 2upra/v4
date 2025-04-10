<?

// Función htmlNotas() movida a app/View/Renderers/NoteRenderer.php

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

