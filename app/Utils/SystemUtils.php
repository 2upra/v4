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
