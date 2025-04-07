<?

# Programa la tarea diaria de limpieza de caché si no está ya programada.
add_action('wp', 'schedule_audio_cache_cleanup');
function schedule_audio_cache_cleanup()
{
    guardarLog("[schedule_audio_cache_cleanup] inicio Verificando programacion de limpieza");
    if (!wp_next_scheduled('audio_cache_cleanup')) {
         guardarLog("[schedule_audio_cache_cleanup] info Programando tarea audio_cache_cleanup");
        wp_schedule_event(time(), 'daily', 'audio_cache_cleanup');
    } else {
        guardarLog("[schedule_audio_cache_cleanup] info Tarea audio_cache_cleanup ya programada");
    }
}

# Ejecuta la limpieza de archivos de caché de audio antiguos.
add_action('audio_cache_cleanup', 'clean_audio_cache');
function clean_audio_cache()
{
    guardarLog("[clean_audio_cache] inicio Ejecutando limpieza de cache de audio");
    $directorioUploads = wp_upload_dir();
    $directorioCache = $directorioUploads['basedir'] . '/audio_cache';
    guardarLog("[clean_audio_cache] info Verificando directorio: $directorioCache");

    if (is_dir($directorioCache)) {
        guardarLog("[clean_audio_cache] info Directorio de cache encontrado buscando archivos antiguos");
        $listaArchivos = glob($directorioCache . '/*');
        $tiempoActual = time();
        $tiempoLimite = 7 * 24 * 60 * 60; // 7 dias

        foreach ($listaArchivos as $rutaArchivo) {
             guardarLog("[clean_audio_cache] info Procesando archivo: $rutaArchivo");
             // Comprueba si es un archivo y si su última modificación fue hace más de tiempoLimite
            if (is_file($rutaArchivo) && ($tiempoActual - filemtime($rutaArchivo) > $tiempoLimite)) {
                 // Corrige la sintaxis de la llamada a date() y la concatenación
                 guardarLog("[clean_audio_cache] info Archivo antiguo (modificado: " . date('Y-m-d H:i:s', filemtime($rutaArchivo)) . ") eliminando: $rutaArchivo");
                unlink($rutaArchivo);
            } else if (is_file($rutaArchivo)){
                 // Corrige la sintaxis de la llamada a date() y la concatenación
                 guardarLog("[clean_audio_cache] info Archivo conservado (modificado: " . date('Y-m-d H:i:s', filemtime($rutaArchivo)) . "): $rutaArchivo");
            } else {
                 guardarLog("[clean_audio_cache] info Elemento omitido (no es archivo): $rutaArchivo");
            }
        }
         guardarLog("[clean_audio_cache] exito Limpieza de cache finalizada");
    } else {
        guardarLog("[clean_audio_cache] info Directorio de cache no existe omitiendo limpieza");
    }
}

# Desprograma la tarea de limpieza de caché al desactivar.
register_deactivation_hook(__FILE__, 'unschedule_audio_cache_cleanup');
function unschedule_audio_cache_cleanup()
{
    guardarLog("[unschedule_audio_cache_cleanup] inicio Intentando desprogramar tarea de limpieza");
    $proximaEjecucion = wp_next_scheduled('audio_cache_cleanup');
    if ($proximaEjecucion) {
        guardarLog("[unschedule_audio_cache_cleanup] info Tarea encontrada en $proximaEjecucion desprogramando");
        wp_unschedule_event($proximaEjecucion, 'audio_cache_cleanup');
    } else {
        guardarLog("[unschedule_audio_cache_cleanup] info Tarea no encontrada no es necesario desprogramar");
    }
}