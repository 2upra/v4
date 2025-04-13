<?php

// Refactor(Org): Función ejecutarScriptPermisos() movida desde app/Content/Posts/View/componentPost.php
/**
 * Ejecuta un script de shell para corregir permisos.
 */
function ejecutarScriptPermisos()
{
    // Ejecutar el script de permisos y capturar la salida
    $output = shell_exec('sudo /var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh 2>&1');

    // Opcional: Puedes registrar el output para depuración
    error_log('Script de permisos ejecutado: ' . $output);
}

// Refactor(Org): Función buscar_archivo_recursivo() movida desde app/Auto/automaticPost.php
/**
 * Busca un archivo de forma recursiva en un directorio.
 *
 * @param string $dir Directorio inicial de búsqueda.
 * @param string $filename Nombre del archivo a buscar.
 * @return string|false La ruta completa al archivo si se encuentra, false en caso contrario.
 */
function buscar_archivo_recursivo($dir, $filename)
{
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            $result = buscar_archivo_recursivo($path, $filename);
            if ($result !== false) {
                return $result;
            }
        } elseif ($file === $filename) {
            return $path;
        }
    }
    return false;
}

// Refactor(Org): Mueve función manejarArchivoFallido() de app/Auto/automaticPost.php a app/Utils/SystemUtils.php
function manejarArchivoFallido($rutaArchivo, $motivo)
{
    $directorioVerificar = "/home/asley01/MEGA/Waw/Verificar/";
    if (!file_exists($directorioVerificar)) {
        mkdir($directorioVerificar, 0777, true); // Crear el directorio si no existe
    }

    $nombreArchivo = basename($rutaArchivo);
    $nuevoDestino = $directorioVerificar . $nombreArchivo;

    if (rename($rutaArchivo, $nuevoDestino)) {
        // Crear un archivo de texto explicando el fallo
        $archivoTexto = $directorioVerificar . $nombreArchivo . ".txt";
        file_put_contents($archivoTexto, "Fallo al procesar el archivo: $nombreArchivo\nMotivo: $motivo");
    } else {
        // Asumiendo que autLog está disponible globalmente o será incluida donde se use SystemUtils
        if (function_exists('autLog')) {
             autLog("Error al mover el archivo a $directorioVerificar");
        } else {
             error_log("Error al mover el archivo a $directorioVerificar (autLog no disponible)");
        }
    }
}

// Refactor(Org): Mueve función renombrar_archivo_adjunto() de app/Auto/reEditarPost.php a app/Utils/SystemUtils.php
function renombrar_archivo_adjunto($attachment_id, $nuevo_nombre, $es_lite = false)
{
    // Obtener el path completo del archivo adjunto
    $ruta_archivo = get_attached_file($attachment_id);
    if (!file_exists($ruta_archivo)) {
        //error_log("El archivo adjunto con ID {$attachment_id} no existe en la ruta: {$ruta_archivo}");
        return false;
    }

    // Obtener la carpeta y la extensión del archivo
    $carpeta = pathinfo($ruta_archivo, PATHINFO_DIRNAME);
    $extension = pathinfo($ruta_archivo, PATHINFO_EXTENSION);
    if ($es_lite) {
        $nuevo_nombre .= '_lite';
    }
    $nueva_ruta = $carpeta . '/' . $nuevo_nombre . '.' . $extension;

    // Renombrar el archivo
    if (!rename($ruta_archivo, $nueva_ruta)) {
        //error_log("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        guardarLog("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        return false;
    }

    //error_log("Archivo renombrado de {$ruta_archivo} a {$nueva_ruta}");
    guardarLog("Archivo renombrado en el servidor de {$ruta_archivo} a {$nueva_ruta}");

    // Actualizar la ruta del adjunto en la base de datos
    $wp_filetype = wp_check_filetype(basename($nueva_ruta), null);
    $attachment_data = array(
        'ID' => $attachment_id,
        'post_name' => sanitize_title($nuevo_nombre),
        'guid' => home_url('/') . str_replace(ABSPATH, '', $nueva_ruta),
    );

    // Actualizar el post del adjunto
    if (function_exists('wp_update_post')) {
        wp_update_post($attachment_data);
    }

    update_attached_file($attachment_id, $nueva_ruta);

    return true;
}
