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

// Refactor(Exec): Función tituloColab() movida desde app/Content/Colab/partColab.php
function tituloColab($var)
{
    $post_id = $var['post_id'];
    $imagenPostOp = $var['imagenPostOp'];
    $postTitulo = $var['postTitulo'];
    $colabFecha = $var['colabFecha'];

    ob_start(); ?>

    <div class="MJYQLF">
        <div class="YXJIKK">
            <img src="<? echo esc_url($imagenPostOp) ?>">
        </div>
        <div class="SNVKQC">
            <p><? echo esc_html($postTitulo) ?></p>
            <a href="<? echo esc_url(get_permalink()); ?>" class="post-link">
                <? echo esc_html($colabFecha); ?>
            </a>
        </div>
    </div>

<?php return ob_get_clean();
}

// Refactor(Org): Función colab() movida desde app/Content/Colab/renderColab.php
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
