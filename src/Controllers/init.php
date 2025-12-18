<?php

/**
 * Inicializador de controladores.
 * 
 * Se encarga de instanciar y registrar los controladores del tema.
 * Los archivos se cargan automáticamente via Composer autoload.
 * Organizados por dominio/módulo.
 * 
 * @package Kamples\Controllers
 * @since 2.0.0
 */

use Kamples\Controllers\Audio;
use Kamples\Controllers\Social;
use Kamples\Controllers\Core;
use Kamples\Controllers\Publicacion;
use Kamples\Controllers\Usuario;
use Kamples\Controllers\Feed;
use Kamples\Controllers\Finanza;
use Kamples\Controllers\Coleccion;
use Kamples\Controllers\Moderacion;
use Kamples\Controllers\Contenido;

/* 
 * Controladores que se auto-inicializan en su constructor
 * (ya crean instancia con new al final del archivo)
 */

$autoInicializados = [
    /* Audio */
    Audio\StreamController::class,
    Audio\ReproductorController::class,

    /* Social */
    Social\ChatController::class,
    Social\NotificacionController::class,
    Social\ComentarioController::class,

    /* Core */
    Core\SyncController::class,
    Core\FormularioController::class,
    Core\ContadorController::class,
    Core\BusquedaController::class,

    /* Publicacion */
    Publicacion\PostEstadoController::class,

    /* Usuario */
    Usuario\OnboardingController::class,

    /* Feed */
    Feed\FiltroController::class,

    /* Finanza */
    Finanza\FinanzaController::class,

    /* Coleccion */
    Coleccion\ColeccionController::class,

    /* Moderacion */
    Moderacion\ModeracionController::class,
    Moderacion\ReporteController::class,
];

/* 
 * Controladores que requieren llamar registrar() o registrarHooks()
 */
$controladoresConRegistrar = [
    Audio\ArchivoController::class,
    Audio\WaveformController::class,
    Social\LikeController::class,
    Social\SeguirController::class,
    Social\ColabController::class,
    Core\VistaController::class,
    Core\UtilController::class,
    Core\DescargaController::class,
    Contenido\IAController::class,
    Publicacion\PostEdicionController::class,
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
 * Controladores con método estático inicializar() o registrar()
 */
$controladoresEstaticos = [
    Publicacion\PostController::class,
    Publicacion\PublicacionController::class,
    Usuario\AuthController::class,
    Usuario\PerfilController::class,
    Usuario\UsuarioController::class,
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
