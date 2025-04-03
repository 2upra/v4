<?php

// Refactor(Exec): Funcion botonDescarga() movida desde app/View/Helpers/UIHelper.php
// Refactor(Org): Funcion botonDescarga movida desde app/Functions/descargas.php
function botonDescarga($postId)
{
    ob_start();
    $paraDescarga = get_post_meta($postId, 'paraDescarga', true);
    $userId = get_current_user_id();

    if ($paraDescarga == '1') {
        if ($userId) {
            $descargasAnteriores = get_user_meta($userId, 'descargas', true);
            $yaDescargado = isset($descargasAnteriores[$postId]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';
            $esColeccion = get_post_type($postId) === 'colecciones' ? 'true' : 'false';

?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down <? echo esc_attr($claseExtra); ?>"
                    data-post-id="<? echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<? echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<? echo esc_js($postId); ?>', '<? echo esc_js($userId); ?>', '<? echo $esColeccion; ?>')">
                    <? echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
        <?
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    <? echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
<?
        }
    }
    return ob_get_clean();
}
