<?php

/**
 * Funciones de seguimiento DEPRECADAS.
 * 
 * Este archivo contiene wrappers temporales para compatibilidad.
 * Todas las funciones aquí están marcadas como @deprecated y serán eliminadas.
 * 
 * USO CORRECTO:
 * - Lógica: Kamples\Services\SeguirService
 * - AJAX: Kamples\Controllers\SeguirController
 *
 * @package Kamples
 * @since 1.0.0
 * @deprecated Este archivo será eliminado una vez se actualicen todas las referencias.
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Services\SeguirService;
use Kamples\Controllers\SeguirController;

/* 
 *
 * Inicialización del controlador AJAX
 *
 */

$seguirController = new SeguirController();
$seguirController->registrar();

/* 
 *
 * Funciones wrapper DEPRECADAS
 *
 */

/**
 * Obtener ID de usuario desde POST.
 *
 * @param string $key Clave del valor en POST.
 * @return int
 * @deprecated Usar método privado en controlador.
 */
function get_user_id_from_post($key): int
{
    return isset($_POST[$key]) ? absint($_POST[$key]) : 0;
}

/**
 * Actualizar relación de seguimiento.
 *
 * @param int    $followerId  ID del seguidor.
 * @param int    $followedId  ID del seguido.
 * @param string $action      'follow' o 'unfollow'.
 * @return bool
 * @deprecated Usar SeguirService::seguir() o SeguirService::dejarDeSeguir().
 */
function update_follow_relationship($followerId, $followedId, $action): bool
{
    $servicio = new SeguirService();

    if ($action === 'follow') {
        return $servicio->seguir((int) $followerId, (int) $followedId);
    } elseif ($action === 'unfollow') {
        return $servicio->dejarDeSeguir((int) $followerId, (int) $followedId);
    }

    return false;
}

/**
 * Handler AJAX para seguir usuario.
 * 
 * @deprecated Manejado por SeguirController.
 */
function seguir_usuario(): void
{
    $controller = new SeguirController();
    $controller->seguirUsuario();
}

/**
 * Handler AJAX para dejar de seguir usuario.
 * 
 * @deprecated Manejado por SeguirController.
 */
function dejar_de_seguir_usuario(): void
{
    $controller = new SeguirController();
    $controller->dejarDeSeguirUsuario();
}

/**
 * Auto-seguir al registrarse.
 *
 * @param int $userId ID del nuevo usuario.
 */
function seguir_usuario_automaticamente($userId): void
{
    $servicio = new SeguirService();
    $servicio->autoSeguir((int) $userId);
}
add_action('user_register', 'seguir_usuario_automaticamente');

/**
 * Auto-seguir para todos los usuarios existentes (utility).
 * 
 * @deprecated Solo para migración inicial.
 */
function seguir_usuarios_automaticamente1(): void
{
    $servicio = new SeguirService();

    foreach (get_users(['fields' => 'ID']) as $userId) {
        $servicio->autoSeguir((int) $userId);
    }
}

/**
 * Shortcode para mostrar contadores de usuario.
 */
add_shortcode('mostrar_contadores', function (): string {
    $userId = get_current_user_id();

    if ($userId === 0) {
        return '';
    }

    $servicio = new SeguirService();
    $contadores = $servicio->obtenerContadores($userId);

    return sprintf(
        '%d seguidores %d seguidos %d posts',
        $contadores['seguidores'],
        $contadores['siguiendo'],
        $contadores['posts']
    );
});
