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

// Refactor(Exec): Funciones botonDescargaColec y botonSincronizarColec movidas a app/View/Helpers/DownloadHelper.php

