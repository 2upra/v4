<?php

// Refactor(Org): Función procesarColeccion() movida a app/Services/DownloadService.php

// Refactor(Org): Función generarEnlaceDescargaColeccion() movida a app/Services/DownloadService.php

// Refactor(Org): Función descargaAudioColeccion() y su hook movidos a app/Services/DownloadService.php

// Refactor(Org): Función agregarArchivosAlZip() movida a app/Services/DownloadService.php

// Refactor(Org): Función clasificarSamples() movida a app/Services/DownloadService.php

function actualizarDescargas(int $userId, array $samplesNoDescargados, array $samplesDescargados): void
{
    $functionName = __FUNCTION__;

    foreach ($samplesNoDescargados as $sampleId) {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];

        if (!is_array($descargasAnteriores)) {
            error_log("[{$functionName}] Error: El valor de 'descargas' para el usuario {$userId} no es un array.");
            $descargasAnteriores = [];
        }

        $descargasAnteriores[$sampleId] = 1;
        update_user_meta($userId, 'descargas', $descargasAnteriores);
        error_log("[{$functionName}] Sample no descargado ({$sampleId}) agregado a descargas para el usuario {$userId}.");
    }

    foreach ($samplesDescargados as $sampleId) {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];

        if (!is_array($descargasAnteriores)) {
            error_log("[{$functionName}] Error: El valor de 'descargas' para el usuario {$userId} no es un array.");
            $descargasAnteriores = [];
        }

        if (isset($descargasAnteriores[$sampleId])) {
            $descargasAnteriores[$sampleId]++;
            update_user_meta($userId, 'descargas', $descargasAnteriores);
            //error_log("[{$functionName}] Contador de descargas incrementado para sample {$sampleId} (usuario {$userId}). Nuevo valor: {$descargasAnteriores[$sampleId]} ");
        }
    }
}

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
