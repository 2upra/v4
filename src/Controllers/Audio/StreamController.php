<?php

/**
 * Controlador de streaming de audio
 * 
 * Endpoints REST para streaming de archivos de audio
 *
 * @package Kamples\Controllers\Audio
 * @since 1.0.0
 */

namespace Kamples\Controllers\Audio;

use Kamples\Services\Audio\StreamService;

class StreamController
{
    private StreamService $streamService;

    public function __construct()
    {
        $this->streamService = StreamService::obtenerInstancia();
        $this->registrarRutas();
        $this->registrarCronJobs();
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
        /* Ruta para usuarios pro */
        register_rest_route('1/v1', '/audio-pro/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'manejarStreamPro'],
            'permission_callback' => function () {
                return $this->streamService->usuarioEsAdminOPro(get_current_user_id());
            },
            'args' => [
                'id' => [
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        /* Ruta para usuarios normales */
        register_rest_route('1/v1', '/2', [
            'methods' => 'GET',
            'callback' => [$this, 'manejarStream'],
            'args' => [
                'token' => [
                    'required' => true
                ]
            ],
            'permission_callback' => function ($request) {
                return $this->streamService->verificarToken($request->get_param('token') ?? '');
            }
        ]);
    }

    /**
     * Maneja el streaming para usuarios normales
     *
     * @param \WP_REST_Request $request
     * @return \WP_Error|void
     */
    public function manejarStream(\WP_REST_Request $request)
    {
        $token = $request->get_param('token');
        return $this->streamService->streamAudio($token);
    }

    /**
     * Maneja el streaming para usuarios pro
     *
     * @param \WP_REST_Request $request
     * @return \WP_Error|void
     */
    public function manejarStreamPro(\WP_REST_Request $request)
    {
        $audioId = $request->get_param('id');
        $token = $this->streamService->generarToken($audioId);
        return $this->streamService->streamAudio($token);
    }

    /**
     * Registra los cron jobs para limpieza de cache
     */
    private function registrarCronJobs(): void
    {
        add_action('wp', function () {
            $this->streamService->programarLimpiezaCache();
        });

        add_action('audio_cache_cleanup', function () {
            $this->streamService->limpiarCache();
        });
    }
}
