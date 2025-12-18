<?php

/**
 * Wrappers deprecados para funciones de autenticación.
 * 
 * @deprecated Usar Kamples\Services\AuthService y componentes en su lugar.
 * @see \Kamples\Services\AuthService
 * @see \Kamples\Controllers\AuthController
 * @see \Kamples\Views\Components\AuthComponents
 */

use Kamples\Services\Usuario\AuthService;
use Kamples\Controllers\Usuario\AuthController;
use Kamples\Views\Components\AuthComponents;

/* Inicializar controlador de autenticación */

AuthController::inicializar();

/**
 * @deprecated Usar AuthComponents::renderFormularioLogin()
 */
function iniciar_sesion()
{
    $authComponents = new AuthComponents();
    return $authComponents->renderFormularioLogin();
}

/**
 * @deprecated Usar AuthComponents::renderFormularioRegistro()
 */
function registrar_usuario()
{
    $authComponents = new AuthComponents();
    return $authComponents->renderFormularioRegistro();
}

/**
 * @deprecated El controlador maneja esto automáticamente.
 */
function handle_google_callback()
{
    /* El AuthController maneja esto en init hook */
}

/**
 * @deprecated Usar AuthService::esAppElectron()
 */
function is_electron_app()
{
    $authService = AuthService::obtenerInstancia();
    return $authService->esAppElectron();
}

/**
 * @deprecated Usar AuthService::generarTokenSeguro()
 */
function generate_secure_token($user_id)
{
    $authService = AuthService::obtenerInstancia();
    return $authService->generarTokenSeguro($user_id);
}

/**
 * @deprecated Usar AuthService::verificarToken()
 */
function verify_secure_token($token)
{
    $authService = AuthService::obtenerInstancia();
    return $authService->verificarToken($token);
}

/**
 * @deprecated El controlador maneja esto automáticamente vía REST.
 */
function log_user_agent_callback($request)
{
    $controller = new \Kamples\Controllers\Usuario\AuthController();
    return $controller->logUserAgent($request);
}

/**
 * @deprecated El controlador maneja esto automáticamente vía REST.
 */
function verify_token_endpoint($request)
{
    $controller = new \Kamples\Controllers\Usuario\AuthController();
    return $controller->verificarTokenEndpoint($request);
}

/**
 * @deprecated El controlador maneja esto automáticamente vía REST.
 */
function save_firebase_token($request)
{
    $controller = new \Kamples\Controllers\Usuario\AuthController();
    return $controller->guardarTokenFirebase($request);
}

/**
 * @deprecated Usar AuthService::guardarVersionApp()
 */
function save_version_meta($user_id, $request)
{
    $authService = AuthService::obtenerInstancia();
    $versionName = sanitize_text_field($request->get_param('appVersionName') ?? '');
    $versionCode = intval($request->get_param('appVersionCode') ?? 0);
    $authService->guardarVersionApp($user_id, $versionName, $versionCode);
}
