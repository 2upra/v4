<?php

/**
 * Autoloader PSR-4 para el namespace Kamples.
 * 
 * Este archivo implementa autoloading PSR-4 para las clases en /src/.
 * Permite usar namespaces y cargar clases automáticamente.
 *
 * @package Kamples
 * @since 1.0.0
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

/**
 * Registrar el autoloader para el namespace Kamples.
 */
spl_autoload_register(function ($clase) {
    // Prefijo del namespace base para el tema
    $prefix = 'Kamples\\';

    // Directorio base para el namespace (usando __DIR__ que es más seguro que get_template_directory)
    $base_dir = __DIR__ . '/';

    // Verificar si la clase usa el prefijo del namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $clase, $len) !== 0) {
        // La clase no pertenece a este namespace
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative_class = substr($clase, $len);

    // Construir la ruta del archivo
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Cargar el archivo si existe
    if (file_exists($file)) {
        require $file;
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
    $claseCompleta = "Kamples\\Services\\{$nombreServicio}";

    if (class_exists($claseCompleta)) {
        if (method_exists($claseCompleta, 'obtenerInstancia')) {
            return $claseCompleta::obtenerInstancia();
        }
        return new $claseCompleta();
    }

    return null;
}
