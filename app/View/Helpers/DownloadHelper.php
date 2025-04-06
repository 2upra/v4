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

// Refactor(Exec): Funcion botonSincronizar() movida desde app/View/Helpers/UIHelper.php
function botonSincronizar($postId)
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
                <button class="icon-arrow-down <?php echo esc_attr($claseExtra); ?>"
                    data-post-id="<?php echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<?php echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<?php echo esc_js($postId); ?>', '<?php echo esc_js($userId); ?>', '<?php echo $esColeccion; ?>')">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
        <?php
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
<?php
        }
    }
    return ob_get_clean();
}

// Refactor(Exec): Funciones botonDescargaColec y botonSincronizarColec movidas desde app/Functions/descargarColeccion.php
function botonDescargaColec($postId, $sampleCount)
{
    ob_start();

    $userID = get_current_user_id();

    if ($userID) {
        $descargas_anteriores = get_user_meta($userID, 'descargas', true);
        $yaDescargado = isset($descargas_anteriores[$postId]);
        $claseExtra = $yaDescargado ? 'yaDescargado' : '';

?>
        <div class="ZAQIBB">
            <button class="icon-arrow-down botonprincipal <? echo esc_attr($claseExtra); ?>"
                data-post-id="<? echo esc_attr($postId); ?>"
                aria-label="Boton Descarga"
                id="download-button-<? echo esc_attr($postId); ?>"
                onclick="return procesarDescarga('<? echo esc_js($postId); ?>', '<? echo esc_js($userID); ?>', 'true', '<? echo esc_js($sampleCount); ?>')">
                <? echo $GLOBALS['descargaicono']; ?> Descargar
            </button>
        </div>
    <?php
    } else {
    ?>
        <div class="ZAQIBB">
            <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                <? echo $GLOBALS['descargaicono']; ?>
            </button>
        </div>
    <?php
    }

    return ob_get_clean();
}

function botonSincronizarColec($postId, $sampleCount)
{
    ob_start();

    $userID = get_current_user_id();

    if ($userID) {
        $descargas_anteriores = get_user_meta($userID, 'descargas', true);
        $yaDescargado = isset($descargas_anteriores[$postId]);
        $claseExtra = $yaDescargado ? 'yaDescargado' : '';

    ?>
        <div class="ZAQIBB">
            <button class="icon-arrow-down botonsecundario <? echo esc_attr($claseExtra); ?>"
                data-post-id="<? echo esc_attr($postId); ?>"
                aria-label="Boton Descarga"
                id="download-button-<? echo esc_attr($postId); ?>"
                aca necesito que envie otro valor en true de forma de segura, ahora procesarDescarga recibira otro valor
                onclick="return procesarDescarga('<? echo esc_js($postId); ?>', '<? echo esc_js($userID); ?>', 'true', '<? echo esc_js($sampleCount); ?>', 'true')">
                Sincronizar
            </button>
        </div>
    <?php
    } else {
    ?>
        <div class="ZAQIBB">
            <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                Sincronizar
            </button>
        </div>
<?php
    }

    return ob_get_clean();
}
