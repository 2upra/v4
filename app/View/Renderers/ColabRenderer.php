<?php

// Refactor(Org): Función htmlColab movida desde app/Content/Colab/renderColab.php

function htmlColab($filtro)
{
    $post_id = get_the_ID();
    $var = variablesColab($post_id);
    extract($var);
    ob_start();

?>

    <li class="modal POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($colabColaborador); ?>">

        <div class="colab-content">
            <? if ($filtro === 'colabPendiente'): ?>
                <? echo opcionesColab($var); ?>
                <? echo contenidoColab($var); ?>
            <? else: ?>
                <div class="UICMCG">
                    <? echo tituloColab($var); ?>
                    <? echo participantesColab($var) ?>
                    <button class="cerrarColab" id-post="<? echo get_the_ID(); ?>"><? echo $GLOBALS['cancelicon']; ?></button>
                    <? // echo opcionesColabActivo($var); ?>
                </div>
                <div class="MXPLYN">
                    <? echo chatColab($var); ?>
                    <? //echo archivosColab($var); ?>
                    <? //echo historialColab($var); ?>
                    <? //echo comandosColab($var); ?>
                    <? //echo enviarColab($var);?>
                </div>
            <? endif; ?>

        </div>
    </li>

<?
    return ob_get_clean();
}
