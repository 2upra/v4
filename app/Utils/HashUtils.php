<?php
// Refactor(Org): Funciones de hashing y estado de archivos movidas desde app/Form/Hash.php

define('HASH_SCRIPT_PATH', '/var/www/wordpress/wp-content/themes/2upra3v/app/python/hashAudio.py');
define('PROCESO_DELAY', 500000); // 0.5 segundos en microsegundos
define('MAX_EXECUTION_TIME', 30); // 30 segundos por archivo
define('BATCH_SIZEHASH', 50);
set_time_limit(0);

if (!defined('HASH_SIMILARITY_THRESHOLD')) {
    define('HASH_SIMILARITY_THRESHOLD', 0.7);
}
define('WRAPPER_SCRIPT_PATH', '/var/www/wordpress/wp-content/themes/2upra3v/app/Commands/process_audio.sh');


function sonHashesSimilares($hash1, $hash2, $umbral = HASH_SIMILARITY_THRESHOLD)
{
    if (empty($hash1) || empty($hash2)) {
        return false;
    }

    // Convertir hashes a valores binarios
    $bin1 = hex2bin($hash1);
    $bin2 = hex2bin($hash2);

    if ($bin1 === false || $bin2 === false) {
        return false;
    }

    // Calcular similitud usando distancia de Hamming
    $similitud = 1 - (count(array_diff_assoc(str_split($bin1), str_split($bin2))) / strlen($bin1));

    return $similitud >= $umbral;
}

// Refactor(Org): Moved function handle_recalcular_hash() and its hook to app/Services/FileHashService.php

function recalcularHash($audio_file_path)
{
    try {
        // Verificaciones iniciales
        if (!is_string($audio_file_path) || empty($audio_file_path)) {
            throw new Exception("Ruta de archivo inválida: " . $audio_file_path);
        }

        $file_path = $audio_file_path;

        // Verificaciones de archivo
        if (!file_exists($file_path)) {
            throw new Exception("Archivo no encontrado: " . $file_path);
        }

        if (!is_readable($file_path)) {
            $output = shell_exec('sudo /var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh 2>&1');
            throw new Exception("No hay permisos de lectura para el archivo: " . $file_path);
        }

        if (!file_exists(WRAPPER_SCRIPT_PATH)) {
            throw new Exception("Script wrapper no encontrado en: " . WRAPPER_SCRIPT_PATH);
        }

        if (!is_executable(WRAPPER_SCRIPT_PATH)) {
            throw new Exception("Script wrapper no tiene permisos de ejecución: " . WRAPPER_SCRIPT_PATH);
        }

        $command = escapeshellarg(WRAPPER_SCRIPT_PATH) . ' ' . escapeshellarg($file_path);


        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            throw new Exception("No se pudo iniciar el proceso");
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $return_value = proc_close($process);

        if ($return_value !== 0) {
            throw new Exception("Error en el proceso Python: " . $error);
        }

        $hash = trim($output);
        if (!preg_match('/^[a-f0-9]{64}$/', $hash)) {
            throw new Exception("Hash inválido generado: " . $output);
        }

        return $hash;
    } catch (Exception $e) {
        $output = shell_exec('sudo /var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh 2>&1');
        return false;
    }
}

// Refactor(Org): Moved function actualizarEstadoArchivo to app/Services/FileHashService.php

// Refactor(Org): Moved function subidaArchivo() and its hook to app/Services/FileHashService.php

// Refactor(Org): Moved function guardarHash to app/Services/FileHashService.php


// Refactor(Org): Moved function actualizarUrlArchivo to app/Services/FileHashService.php

// Refactor(Org): Moved function nombreUnicoFile to FileUtils.php


// Refactor(Org): Moved function confirmarHashId to app/Services/FileHashService.php


// Refactor(Org): Moved function eliminarHash to app/Services/FileHashService.php

// Refactor(Org): Moved function eliminarPorHash to app/Services/FileHashService.php

// Refactor(Org): Moved function obtenerFileIDPorURL to app/Services/FileHashService.php

// Refactor(Org): Moved function limpiarArchivosPendientes() and its hook/schedule to app/Services/FileHashService.php


?>