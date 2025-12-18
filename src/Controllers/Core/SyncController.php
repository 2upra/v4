<?php

/**
 * Controlador REST API para sincronización con la aplicación Electron.
 * 
 * Registra los endpoints REST y maneja las peticiones de sincronización
 * de audios, verificación de cambios y descarga de archivos.
 *
 * @package Kamples\Controllers\Core
 * @since 1.0.0
 */

namespace Kamples\Controllers\Core;

use Kamples\Services\Core\SyncService;

class SyncController
{
    private SyncService $syncService;

    public function __construct()
    {
        $this->syncService = SyncService::obtenerInstancia();
        $this->registrarEndpoints();
        $this->registrarHooks();
    }

    /**
     * Registra los endpoints REST API.
     */
    private function registrarEndpoints(): void
    {
        add_action('rest_api_init', function () {
            /* Obtener audios del usuario */
            register_rest_route('1/v1', '/syncpre/(?P<user_id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this, 'handleObtenerAudios'],
                'permission_callback' => [$this, 'verificarElectron'],
            ]);

            /* Verificar cambios desde última sincronización */
            register_rest_route('1/v1', '/syncpre/(?P<user_id>\d+)/check', [
                'methods' => 'GET',
                'callback' => [$this, 'handleVerificarCambios'],
                'permission_callback' => [$this, 'verificarElectron'],
            ]);

            /* Descargar audio con token */
            register_rest_route('sync/v1', '/download/', [
                'methods' => 'GET',
                'callback' => [$this, 'handleDescargarAudio'],
                'args' => [
                    'token' => ['required' => true, 'type' => 'string'],
                    'nonce' => ['required' => true, 'type' => 'string'],
                ],
            ]);

            /* Obtener información de usuario */
            register_rest_route('1/v1', '/infoUsuario', [
                'methods' => 'POST',
                'callback' => [$this, 'handleInfoUsuario'],
                'permission_callback' => [$this, 'verificarElectron'],
            ]);
        });
    }

    /**
     * Registra los hooks de WordPress.
     */
    private function registrarHooks(): void
    {
        add_action('nueva_descarga_realizada', function ($userId) {
            $this->syncService->actualizarTimestampDescargas((int) $userId);
        }, 10, 1);

        add_action('samples_guardados_actualizados', function ($userId) {
            $this->syncService->actualizarTimestampSamplesGuardados((int) $userId);
        }, 10, 1);
    }

    /**
     * Verifica que la petición proviene de la aplicación Electron.
     *
     * @return bool|\WP_Error
     */
    public function verificarElectron()
    {
        if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
            return true;
        }
        return new \WP_Error('forbidden', 'Acceso no autorizado', ['status' => 403]);
    }

    /**
     * Handler: Obtener audios del usuario para sincronización.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handleObtenerAudios(\WP_REST_Request $request): \WP_REST_Response
    {
        $userId = (int) $request->get_param('user_id');
        $postId = $request->get_param('post_id') ? (int) $request->get_param('post_id') : null;

        $audios = $this->syncService->obtenerAudiosUsuario($userId, $postId);

        return rest_ensure_response($audios);
    }

    /**
     * Handler: Verificar cambios desde la última sincronización.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handleVerificarCambios(\WP_REST_Request $request): \WP_REST_Response
    {
        $userId = (int) $request->get_param('user_id');
        $lastSync = isset($_GET['last_sync']) ? (int) $_GET['last_sync'] : 0;
        $forceSync = $request->get_param('force') === 'true';

        $resultado = $this->syncService->verificarCambios($userId, $lastSync, $forceSync);

        return rest_ensure_response($resultado);
    }

    /**
     * Handler: Descargar audio con token de seguridad.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handleDescargarAudio(\WP_REST_Request $request)
    {
        $token = $request->get_param('token');
        $nonce = $request->get_param('nonce');

        $resultado = $this->syncService->descargarAudio($token, $nonce);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        /* Enviar archivo directamente */
        $this->syncService->enviarArchivo(
            $resultado['filePath'],
            $resultado['mimeType'],
            $resultado['fileName']
        );

        /* No se alcanza este punto ya que enviarArchivo hace exit */
        return rest_ensure_response(['success' => true]);
    }

    /**
     * Handler: Obtener información de un usuario.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handleInfoUsuario(\WP_REST_Request $request)
    {
        $receptor = (int) $request->get_param('receptor');

        if ($receptor <= 0) {
            return new \WP_Error('invalid_receptor', 'ID del receptor inválido.', ['status' => 400]);
        }

        $info = $this->syncService->obtenerInfoUsuario($receptor);

        return rest_ensure_response($info);
    }
}

/* Inicializar controlador */
new SyncController();
