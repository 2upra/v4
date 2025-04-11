<?

// Función htmlNotas() movida a app/View/Renderers/NoteRenderer.php
// Función formNotasUL() movida a app/View/Renderers/NoteRenderer.php

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

