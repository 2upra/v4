<?php

namespace Kamples\Controllers;

/**
 * Inicializador de controladores.
 * 
 * Se encarga de instanciar y registrar los controladores del tema.
 */

$controllers = [
    ArchivoController::class,
    AuthController::class,
    BusquedaController::class,
    ChatController::class,
    ColabController::class,
    ColeccionController::class,
    ComentarioController::class,
    ContadorController::class,
    DescargaController::class,
    FiltroController::class,
    FormularioController::class,
    IAController::class,
    LikeController::class,
    OnboardingController::class,
    PerfilController::class,
    PostController::class,
    PostEdicionController::class,
    PostEstadoController::class,
    PublicacionController::class,
    ReporteController::class,
    ReproductorController::class,
    SeguirController::class,
    StreamController::class,
    UsuarioController::class,
    UtilController::class,
    VistaController::class,
    WaveformController::class,
    NotificacionController::class,
];

foreach ($controllers as $controllerClass) {
    if (class_exists($controllerClass)) {
        // Verificar si tiene método estático inicializar (como PostController)
        if (method_exists($controllerClass, 'inicializar')) {
            $controllerClass::inicializar();
        } else {
            // Instanciar
            $instance = new $controllerClass();
            // Si tiene método registrar, llamarlo
            if (method_exists($instance, 'registrar')) {
                $instance->registrar();
            }
        }
    }
}
