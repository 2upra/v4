<?php

/**
 * Wrappers deprecados para funciones de descargas.php
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Services\DescargaService,
 *             Kamples\Controllers\DescargaController y
 *             Kamples\Views\Components\DescargaComponents en su lugar.
 * @package app\deprecated
 */

use Kamples\Services\Core\DescargaService;
use Kamples\Views\Components\DescargaComponents;

/**
 * @deprecated Usar DescargaController::manejarDescargaAjax()
 */
function procesarDescarga(): void
{
    $service = DescargaService::obtenerInstancia();

    $userId = get_current_user_id();
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $esColeccion = isset($_POST['coleccion']) && $_POST['coleccion'] === 'true';
    $sync = isset($_POST['sync']) && $_POST['sync'] === 'true';

    $resultado = $service->procesarDescarga($userId, $postId, $esColeccion, $sync);

    if ($resultado['success']) {
        wp_send_json_success($resultado);
    } else {
        wp_send_json_error($resultado);
    }
}

/**
 * @deprecated Usar DescargaController::manejarDescargaDirecta()
 */
function descargaAudio(): void
{
    if (isset($_GET['descarga_token'])) {
        $service = DescargaService::obtenerInstancia();
        $token = sanitize_text_field($_GET['descarga_token']);
        $service->manejarDescarga($token);
    }
}

/**
 * @deprecated Usar DescargaService::generarEnlaceDescarga()
 */
function generarEnlaceDescarga($userID, $audioID): string
{
    $service = DescargaService::obtenerInstancia();
    return $service->generarEnlaceDescarga((int)$userID, (int)$audioID);
}

/**
 * @deprecated Usar DescargaComponents::botonDescarga()
 */
function botonDescarga($postId): string
{
    return DescargaComponents::botonDescarga((int)$postId);
}

/**
 * @deprecated Usar DescargaComponents::botonSincronizar()
 */
function botonSincronizar($postId): string
{
    return DescargaComponents::botonSincronizar((int)$postId);
}

/* Los hooks se registran en el controlador, pero añadimos compatibilidad */
if (!has_action('wp_ajax_descargar_audio', 'procesarDescarga')) {
    add_action('wp_ajax_descargar_audio', 'procesarDescarga');
}
if (!has_action('template_redirect', 'descargaAudio')) {
    add_action('template_redirect', 'descargaAudio');
}
