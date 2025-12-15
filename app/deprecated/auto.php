<?php

use Kamples\Services\AutoPostService;
use Kamples\Services\AutoContentService;
use Kamples\Services\PythonService;

/**
 * Wrappers deprecados para funciones de automaización y IA.
 * 
 * @deprecated 1.0.0 Usar Services\AutoPostService y Services\AutoContentService
 */

function autProcesarAudio($rutaOriginal)
{
    AutoPostService::obtenerInstancia()->procesarAudio($rutaOriginal);
}

function manejarArchivoFallido($rutaArchivo, $motivo)
{
    // Este método es privado en el nuevo servicio, pero si se llama desde fuera en legacy,
    // no podemos acceder. Sin embargo, en legacy solo se llamaba desde automaticPost.php.
    // Si algún otro código lo llama, fallará. 
    // Lo "emulamos" o loggeamos error.
    \Logger::obtenerInstancia()->warning('deprecated', "Llamada a manejarArchivoFallido deprecado. No accesible públicamente.");
}

function adjuntarArchivoAut($archivo, $postId, $fileId = null)
{
    return AutoPostService::obtenerInstancia()->adjuntarArchivoAut($archivo, $postId, $fileId);
}

function crearAutPost($rutaOriginal = null, $rutaWpLite = null, $fileId = null, $autorId = null, $postOriginal = null)
{
    return AutoPostService::obtenerInstancia()->crearAutPost($rutaOriginal, $rutaWpLite, $fileId, $autorId, $postOriginal);
}

function multiplesPost($postIdOriginal)
{
    AutoPostService::obtenerInstancia()->procesarMultiples($postIdOriginal);
}

function procesarAudios()
{
    AutoPostService::obtenerInstancia()->procesarAudiosScan();
}

/**
 * Wrapper para búsqueda de archivos (era global en automaticPost.php)
 */
function buscar_archivo_recursivo($dir, $filename)
{
    // Implementación simple para compatibilidad si alguien lo usa
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->getFilename() === $filename) {
            return $file->getPathname();
        }
    }
    return false;
}

function procesarArchivoAudioPython($rutaArchivo)
{
    return (new PythonService())->procesarAudio($rutaArchivo);
}

function rehacerNombreAudio($post_id, $archivo_audio)
{
    return AutoContentService::obtenerInstancia()->rehacerNombreAudio($post_id, $archivo_audio);
}

function procesarUnAudio()
{
    AutoContentService::obtenerInstancia()->procesarUnAudio();
}

function mejorarDescripcionAudioPro($post_id, $archivo_audio)
{
    AutoContentService::obtenerInstancia()->mejorarDescripcionAudioPro($post_id, $archivo_audio);
}

function recalcularSimilarToFeed()
{
    \Kamples\Services\AlgoritmoService::obtenerInstancia()->recalcularSimilarToFeed();
}
