<?php  

/* TO DO  

- [ ] ManejarArchivoFallido no es lo suficientemente robusto y no dice claramente como fallo.

*/

# Esta funcion maneja los archivos que fallaron al procesar, moviéndolos a un directorio específico y creando un archivo de texto con el motivo del fallo. Función exclusiva para megan.
function manejarArchivoFallido($rutaArchivo, $motivo)
{
    $directorioVerificar = "/home/asley01/MEGA/Waw/Verificar/";
    if (!file_exists($directorioVerificar)) {
        mkdir($directorioVerificar, 0777, true); 
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

# Esta funcion busca un archivo en subcarpetas de un directorio base y devuelve la ruta del archivo si lo encuentra. Función exclusiva para megan.
function buscarArchivoEnSubcarpetas($directorio_base, $nombre_archivo)
{
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio_base));
    foreach ($iterador as $archivo) {
        $extension = strtolower($archivo->getExtension());
        $nombre = $archivo->getFilename();

        if (!in_array($extension, ['wav', 'mp3']) || strpos($nombre, '2upra') !== 0) {
            continue;
        }

        if ($nombre === $nombre_archivo) {
            return $archivo->getPath();
        }
    }
    return false;
}
