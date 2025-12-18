<?php

/**
 * Controlador del reproductor de audio
 * 
 * Maneja endpoints REST para reproducciones y oyentes
 *
 * @package Kamples\Controllers\Audio
 * @since 1.0.0
 */

namespace Kamples\Controllers\Audio;

use Kamples\Services\Audio\ReproductorService;

class ReproductorController
{
    private ReproductorService $reproductorService;

    public function __construct()
    {
        $this->reproductorService = ReproductorService::obtenerInstancia();
        $this->registrarRutas();
    }

    /**
     * Registra las rutas REST API
     */
    private function registrarRutas(): void
    {
        add_action('rest_api_init', [$this, 'registrarEndpoints']);
    }

    /**
     * Registra los endpoints REST
     */
    public function registrarEndpoints(): void
    {
        register_rest_route('miplugin/v1', '/reproducciones-y-oyentes/', [
            'methods' => 'POST',
            'callback' => [$this, 'manejarReproduccion'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Maneja la solicitud de registro de reproduccion
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error Respuesta
     */
    public function manejarReproduccion(\WP_REST_Request $request)
    {
        $postId = absint($request->get_param('post_id'));
        $artistId = absint($request->get_param('artist'));
        $ipAddress = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');

        $resultado = $this->reproductorService->registrarReproduccion(
            $postId,
            $artistId,
            $ipAddress
        );

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

        return new \WP_REST_Response(
            ['message' => $resultado['message']],
            200
        );
    }
}
