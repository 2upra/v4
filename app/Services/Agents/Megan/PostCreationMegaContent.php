<?php


#ESTA FUNCION ES PARTE DEL AGENTE MEGAN
function autProcesarAudio($rutaOriginalOne)
{
    autLog("autProcesarAudio start");
    $file_id = obtenerFileIDPorURL($rutaOriginalOne);
    if ($file_id === false) {
        eliminarHash($file_id);
        autLog("File ID no encontrado: $rutaOriginalOne");
    }

    if (!file_exists($rutaOriginalOne)) {
        eliminarHash($file_id);
        autLog("Archivo original no encontrado: $rutaOriginalOne");
        return;
    }

    $fileSizeMB = filesize($rutaOriginalOne) / 1048576;
    if ($fileSizeMB < 0.01) {
        $motivoFallo = "Archivo demasiado pequeño o inválido (menos de 0.01 MB)";
        autLog($motivoFallo . ": $rutaOriginalOne");
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
        return;
    }

    $path_parts = pathinfo($rutaOriginalOne);
    $directory = realpath($path_parts['dirname']);
    if ($directory === false) {
        $motivoFallo = "Directorio inválido: {$path_parts['dirname']}";
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
        return;
    }

    $extension = strtolower($path_parts['extension']);
    $basename = $path_parts['filename'];
    $temp_path = "$directory/{$basename}_temp.$extension";
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginalOne) . " -map_metadata -1 -map 0:a -c:a copy " . escapeshellarg($temp_path) . " -y";
    exec($comando_strip_metadata, $output_strip, $return_strip);

    if ($return_strip !== 0) {
        $motivoFallo = "Error al eliminar metadatos: " . implode(" | ", $output_strip);
        eliminarHash($file_id);
        autLog($motivoFallo);
        $temp_path = $rutaOriginalOne;
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
    }

    if (!rename($temp_path, $rutaOriginalOne)) {
        $motivoFallo = "Error al reemplazar archivo original";
        eliminarHash($file_id);
        autLog($motivoFallo);
        if (!copy($temp_path, $rutaOriginalOne)) {
            autLog("Error al copiar archivo temporal, no se pudo reemplazar el original");
            manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
        } else {
            unlink($temp_path);
        }
    }

    $rutaWpLiteDos = "$directory/{$basename}_lite.mp3";
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginalOne) . " -b:a 128k " . escapeshellarg($rutaWpLiteDos) . " -y";
    exec($comando_lite, $output_lite, $return_lite);

    if ($return_lite !== 0) {
        $motivoFallo = "Error al crear versión lite: " . implode(" | ", $output_lite);
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
    }

    if (!file_exists($rutaWpLiteDos)) {
        $motivoFallo = "El archivo lite no se creó: $rutaWpLiteDos";
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Nota: Esta función ahora está en SystemUtils.php // Manejar si no se crea el lite
    }

    $uploads_dir = wp_upload_dir();
    $target_dir_audio = trailingslashit($uploads_dir['basedir']) . "audio/";

    if (!file_exists($target_dir_audio)) {
        if (!wp_mkdir_p($target_dir_audio)) {
            $motivoFallo = "No se pudo crear directorio audio/";
            eliminarHash($file_id);
            autLog($motivoFallo);
            manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
        }
    }

    if (!is_writable($target_dir_audio)) {
        $motivoFallo = "Directorio audio/ sin permisos de escritura";
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
    }

    $rutaWpLiteOne = $target_dir_audio . "{$basename}_lite.mp3";

    if (!copy($rutaWpLiteDos, $rutaWpLiteOne)) {
        $motivoFallo = "Error al copiar archivo lite: " . error_get_last()['message'];
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
    }

    unlink($rutaWpLiteDos);

    if (!file_exists($rutaWpLiteOne)) {
        $motivoFallo = "Archivo lite no existe después de copiar: $rutaWpLiteOne";
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
    }

    chmod($rutaWpLiteOne, 0644);

    autLog("autProcesarAudio end");
    crearAutPost($rutaOriginalOne, $rutaWpLiteOne, $file_id);
}
