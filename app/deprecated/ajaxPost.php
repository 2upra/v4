<?php

/**
 * Wrappers deprecados para funciones de Ajax Post/
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Services\PostEdicionService y
 *             Kamples\Controllers\PostEdicionController en su lugar.
 * @package app\deprecated
 */

use Kamples\Services\PostEdicionService;

/**
 * @deprecated Usar PostEdicionController::manejarCambiarDescripcion()
 */
function cambiarDescripcion(): void
{
    $service = PostEdicionService::obtenerInstancia();
    $userId = get_current_user_id();
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

    $resultado = $service->cambiarDescripcion($userId, $postId, $descripcion);
    echo json_encode($resultado);
    wp_die();
}

/**
 * @deprecated Función auxiliar de cambiarDescripcion
 */
function rehacerDescripcionAccion($postId): void
{
    /* Manejado internamente por PostEdicionService */
}

/**
 * @deprecated Función auxiliar de cambiarDescripcion
 */
function rehacerDescripcionAudio($postId, $archivoAudio): void
{
    /* Manejado internamente por PostEdicionService */
}

/**
 * @deprecated Usar PostEdicionController::manejarCambiarTitulo()
 */
function cambiarTitulo(): void
{
    $service = PostEdicionService::obtenerInstancia();
    $userId = get_current_user_id();
    $postId = intval($_POST['post_id'] ?? 0);
    $titulo = sanitize_text_field($_POST['titulo'] ?? '');

    $resultado = $service->cambiarTitulo($userId, $postId, $titulo);
    wp_die(json_encode($resultado));
}

/**
 * @deprecated Usar PostEdicionController::manejarCorregirTags()
 */
function corregirTags(): void
{
    $service = PostEdicionService::obtenerInstancia();
    $userId = get_current_user_id();
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $descripcion = isset($_POST['descripcion']) ? sanitize_text_field($_POST['descripcion']) : '';

    $resultado = $service->corregirTags($userId, $postId, $descripcion);
    echo json_encode($resultado);
    wp_die();
}

/**
 * @deprecated Función auxiliar de corregirTags
 */
function rehacerJsonPost($postId, $descripcion): void
{
    /* Manejado internamente por PostEdicionService */
}

/**
 * @deprecated Función auxiliar de corregirTags
 */
function rehacerJson($postId, $audio, $descripcion): void
{
    /* Manejado internamente por PostEdicionService */
}

/* Registrar hooks AJAX para compatibilidad */
if (!has_action('wp_ajax_cambiarDescripcion')) {
    add_action('wp_ajax_cambiarDescripcion', 'cambiarDescripcion');
}

if (!has_action('wp_ajax_cambiarTitulo')) {
    add_action('wp_ajax_cambiarTitulo', 'cambiarTitulo');
}

if (!has_action('wp_ajax_corregirTags')) {
    add_action('wp_ajax_corregirTags', 'corregirTags');
}
