<?php

/**
 * Autoloader PSR-4 para el namespace Theme\V4.
 * 
 * Este archivo implementa autoloading PSR-4 para las clases en /src/.
 * Permite usar namespaces y cargar clases automáticamente.
 *
 * @package Theme_V4
 * @since 1.0.0
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

/**
 * Registrar el autoloader para el namespace Theme\V4.
 */
spl_autoload_register(function ($clase) {
    // Namespace base del tema
    $prefijo = 'Theme\\V4\\';

    // Directorio base para el namespace
    $directorioBase = get_template_directory() . '/src/';

    // Verificar si la clase usa el prefijo del namespace
    $longitudPrefijo = strlen($prefijo);
    if (strncmp($prefijo, $clase, $longitudPrefijo) !== 0) {
        // La clase no pertenece a este namespace
        return;
    }

    // Obtener el nombre relativo de la clase
    $claseRelativa = substr($clase, $longitudPrefijo);

    // Construir la ruta del archivo
    // Reemplazar separadores de namespace con separadores de directorio
    $archivo = $directorioBase . str_replace('\\', '/', $claseRelativa) . '.php';

    // Cargar el archivo si existe
    if (file_exists($archivo)) {
        require $archivo;
    }
});

/**
 * Función helper para obtener una instancia de un servicio.
 * 
 * Uso: $logger = servicio('Logger');
 *
 * @param string $nombreServicio Nombre del servicio.
 * @return object|null
 */
function servicio(string $nombreServicio)
{
    $claseCompleta = "Theme\\V4\\Services\\{$nombreServicio}";

    if (class_exists($claseCompleta)) {
        if (method_exists($claseCompleta, 'obtenerInstancia')) {
            return $claseCompleta::obtenerInstancia();
        }
        return new $claseCompleta();
    }

    return null;
}
