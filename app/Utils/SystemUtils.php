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
