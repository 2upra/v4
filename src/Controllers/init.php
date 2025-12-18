<?php

/**
 * Inicializador de controladores.
 * 
 * Los controladores se cargan automaticamente via el autoloader PSR-4
 * y la funcion incluirArchivos() en functions.php.
 * 
 * Este archivo solo inicializa controladores que requieren 
 * llamadas explicitas a registrar() o inicializar().
 * 
 * Los controladores que tienen "new ControllerClass();" al final
 * ya se auto-inicializan cuando se cargan.
 * 
 * @package Kamples\Controllers
 * @since 2.0.0
 */

use Kamples\Controllers\Audio;
use Kamples\Controllers\Social;
use Kamples\Controllers\Core;
use Kamples\Controllers\Publicacion;
use Kamples\Controllers\Usuario;
use Kamples\Controllers\Contenido;

/* 
 * Controladores que requieren llamar registrar() o registrarHooks() manualmente
 * (no tienen "new" al final del archivo)
 */

$controladoresConRegistrar = [
    Audio\WaveformController::class,
    Social\LikeController::class,
    Social\SeguirController::class,
    Social\ColabController::class,
    Core\VistaController::class,
    Core\UtilController::class,
    Contenido\IAController::class,
];

foreach ($controladoresConRegistrar as $controllerClass) {
    if (class_exists($controllerClass)) {
        $instance = new $controllerClass();
        if (method_exists($instance, 'registrar')) {
            $instance->registrar();
        } elseif (method_exists($instance, 'registrarHooks')) {
            $instance->registrarHooks();
        }
    }
}

/* 
 * Controladores con metodo estatico inicializar() o registrar()
 */
$controladoresEstaticos = [
    Publicacion\PostController::class,
    Publicacion\PublicacionController::class,
    Usuario\AuthController::class,
];

foreach ($controladoresEstaticos as $controllerClass) {
    if (class_exists($controllerClass)) {
        if (method_exists($controllerClass, 'inicializar')) {
            $controllerClass::inicializar();
        } elseif (method_exists($controllerClass, 'registrar')) {
            $controllerClass::registrar();
        }
    }
}

/* 
 * Controladores que solo requieren instanciacion (constructor se encarga del resto)
 */
$controladoresSimples = [
    Audio\ReproductorController::class,
    Audio\StreamController::class,
];

foreach ($controladoresSimples as $controllerClass) {
    if (class_exists($controllerClass)) {
        new $controllerClass();
    }
}
