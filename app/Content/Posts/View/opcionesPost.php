<?php
// Refactor(Org): Función opcionesPost() movida a app/View/Components/PostOptions.php

function renderizarBotonDescarga($postId, $usuarioActual, $paraDescarga)
{
    ob_start();
    if ($paraDescarga == '1') {
        if ($usuarioActual) {
            $descargas_anteriores = get_user_meta($usuarioActual, 'descargas', true);
            $yaDescargado = isset($descargas_anteriores[$postId]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';
    ?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down <? echo esc_attr($claseExtra); ?>"
                    data-post-id="<? echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<? echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<? echo esc_js($postId); ?>', '<? echo esc_js($usuarioActual); ?>', 'false', '1', 'false')">
                    Descargar
                </button>
            </div>
        <?
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    Descargar
                </button>
            </div>
        <?
        }
    }
    return ob_get_clean();
}

function renderizarBotonSincronizar($postId, $usuarioActual, $paraDescarga)
{
    ob_start();
    if ($paraDescarga == '1') {
        if ($usuarioActual) {
            $descargas_anteriores = get_user_meta($usuarioActual, 'descargas', true);
            $yaDescargado = isset($descargas_anteriores[$postId]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';
        ?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down <? echo esc_attr($claseExtra); ?>"
                    data-post-id="<? echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<? echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<? echo esc_js($postId); ?>', '<? echo esc_js($usuarioActual); ?>', 'false', '1', 'true')">
                    Sincronizar
                </button>
            </div>
        <?
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    Sincronizar
                </button>
            </div>
<?
        }
    }
    return ob_get_clean();
}
