<?php

function manejarArchivoFallido($rutaArchivo, $motivo)
{
    $directorioVerificar = "/home/asley01/MEGA/Waw/Verificar/";
    if (!file_exists($directorioVerificar)) {
        mkdir($directorioVerificar, 0777, true); // Crear el directorio si no existe
    }

    $nombreArchivo = basename($rutaArchivo);
    $nuevoDestino = $directorioVerificar . $nombreArchivo;

    if (rename($rutaArchivo, $nuevoDestino)) {

        $archivoTexto = $directorioVerificar . $nombreArchivo . ".txt";
        file_put_contents($archivoTexto, "Fallo al procesar el archivo: $nombreArchivo\nMotivo: $motivo");
    } else {
        autLog("Error al mover el archivo a $directorioVerificar");
    }
}
