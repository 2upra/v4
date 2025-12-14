<?php

/**
 * Wrappers deprecados para funciones de autenticación.
 * 
 * @deprecated Usar Kamples\Services\AuthService y componentes en su lugar.
 * @see \Kamples\Services\AuthService
 * @see \Kamples\Controllers\AuthController
 * @see \Kamples\Views\Components\AuthComponents
 */

use Kamples\Services\AuthService;
use Kamples\Controllers\AuthController;
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
    $authService = new AuthService();
    return $authService->esAppElectron();
}

/**
 * @deprecated Usar AuthService::generarTokenSeguro()
 */
function generate_secure_token($user_id)
{
    $authService = new AuthService();
    return $authService->generarTokenSeguro($user_id);
}

/**
 * @deprecated Usar AuthService::verificarToken()
 */
function verify_secure_token($token)
{
    $authService = new AuthService();
    return $authService->verificarToken($token);
}

/**
 * @deprecated El controlador maneja esto automáticamente vía REST.
 */
function log_user_agent_callback($request)
{
    $controller = new \Kamples\Controllers\AuthController();
    return $controller->logUserAgent($request);
}

/**
 * @deprecated El controlador maneja esto automáticamente vía REST.
 */
function verify_token_endpoint($request)
{
    $controller = new \Kamples\Controllers\AuthController();
    return $controller->verificarTokenEndpoint($request);
}

/**
 * @deprecated El controlador maneja esto automáticamente vía REST.
 */
function save_firebase_token($request)
{
    $controller = new \Kamples\Controllers\AuthController();
    return $controller->guardarTokenFirebase($request);
}

/**
 * @deprecated Usar AuthService::guardarVersionApp()
 */
function save_version_meta($user_id, $request)
{
    $authService = new AuthService();
    $versionName = sanitize_text_field($request->get_param('appVersionName') ?? '');
    $versionCode = intval($request->get_param('appVersionCode') ?? 0);
    $authService->guardarVersionApp($user_id, $versionName, $versionCode);
}
