<?php

/**
 * Wrappers deprecados para funciones de reproductor.php
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Views\Components\ReproductorComponents y
 *             Kamples\Controllers\ReproductorController en su lugar.
 * @package app\deprecated
 */

use Kamples\Views\Components\ReproductorComponents;
use Kamples\Services\ReproductorService;

/**
 * @deprecated Usar ReproductorComponents::renderReproductor()
 */
function reproductor(): void
{
    echo ReproductorComponents::renderReproductor();
}

/* Registrar en footer para compatibilidad */
add_action('wp_footer', 'reproductor');

/**
 * @deprecated Usar ReproductorController::manejarReproduccion()
 */
function reproducciones(\WP_REST_Request $request)
{
    $service = ReproductorService::obtenerInstancia();

    $postId = absint($request->get_param('post_id'));
    $artistId = absint($request->get_param('artist'));
    $ipAddress = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');

    $resultado = $service->registrarReproduccion($postId, $artistId, $ipAddress);

    if (!$resultado['success']) {
        $statusCode = match ($resultado['error']) {
            'rate_limit_exceeded' => 429,
            'invalid_post', 'invalid_artist' => 400,
            default => 500
        };

        return new \WP_Error(
            $resultado['error'],
            $resultado['message'],
            ['status' => $statusCode]
        );
    }

    return new \WP_REST_Response(['message' => $resultado['message']], 200);
}

/**
 * @deprecated Usar ReproductorController
 */
function reproduccionesAPI(): void
{
    register_rest_route('miplugin/v1', '/reproducciones-y-oyentes/', [
        'methods' => 'POST',
        'callback' => 'reproducciones',
        'permission_callback' => '__return_true'
    ]);
}

/**
 * @deprecated Usar ReproductorService::verificarRateLimiting()
 */
function limitador(): bool
{
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $transientName = 'rate_limit_' . $ipAddress;
    $rateLimit = get_transient($transientName);

    if (false === $rateLimit) {
        set_transient($transientName, 1, 5);
        return true;
    }

    if ($rateLimit >= 15) {
        return false;
    }

    set_transient($transientName, $rateLimit + 1, 60);
    return true;
}
