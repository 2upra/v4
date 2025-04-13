<?



function verificarCargaArchivoPorHash($file_hash)
{

    $archivo = obtenerHash($file_hash);

    if (!$archivo) {
        return false;
    }

    $file_id = $archivo['id'];
    $file_url = $archivo['file_url'];

    // Inicializar cURL para verificar la carga del archivo
    $ch = curl_init($file_url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verificación de código de respuesta
    if ($http_code >= 200 && $http_code < 300) {
        return true;
    } else {
        actualizarEstadoArchivo($file_id, 'loss');
        return false;
    }
}

// Refactor(Org): Moved function sonHashesSimilaresAut to app/Services/FileHashService.php

// Refactor(Org): Moved function obtenerHash to app/Services/FileHashService.php

