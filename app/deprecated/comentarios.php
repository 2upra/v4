<?php

/**
 * Wrappers de compatibilidad para el sistema de comentarios.
 * 
 * Este archivo contiene funciones wrapper deprecadas que mantienen
 * la compatibilidad con código legacy mientras se completa la migración.
 * 
 * @deprecated Usar las clases en Kamples\Services y Kamples\Controllers
 * @package Kamples
 * @since 1.0.0
 */

/* Evitar acceso directo */
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Services\ComentarioService;
use Kamples\Views\Components\ComentarioForm;

/**
 * Renderizar formulario de comentarios.
 * 
 * @deprecated Usar ComentarioForm::render() en su lugar.
 * @return string HTML del formulario.
 */
function comentariosForm(): string
{
    return ComentarioForm::render();
}

/**
 * Redirige los accesos directos a las entradas de tipo 'comentarios'
 * a la entrada original a la que pertenecen.
 * 
 * @deprecated Esta función se mantiene por compatibilidad con el sistema de rutas.
 */
function redirigir_comentarios(): void
{
    if (!is_singular('comentarios')) {
        return;
    }

    $comentarioId = get_the_ID();
    $postId = get_post_meta($comentarioId, 'postId', true);

    if ($postId) {
        $postUrl = get_permalink($postId);
        wp_redirect($postUrl, 301);
        exit;
    }

    /* Si no se encuentra el post original, redirigir a inicio */
    wp_redirect(home_url());
    exit;
}

/* Registrar la redirección de comentarios */
add_action('template_redirect', 'redirigir_comentarios');

/**
 * Inicializar el controlador de comentarios.
 * Registra las acciones AJAX.
 */
function inicializarComentariosController(): void
{
    $controller = new \Kamples\Controllers\ComentarioController();
    $controller->registrar();
}

add_action('init', 'inicializarComentariosController');
