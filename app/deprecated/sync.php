<?php

/**
 * Wrapper deprecado para funciones de sincronización.
 * 
 * Mantiene compatibilidad con código legacy que usaba las funciones
 * originales de app/Sync/api.php.
 * 
 * @deprecated Usar Kamples\Services\SyncService y Kamples\Controllers\SyncController
 * @package Kamples\Deprecated
 */

use Kamples\Services\Core\SyncService;

/**
 * Verifica si la cabecera X-Electron-App está presente.
 * 
 * @deprecated Usar SyncController::verificarElectron()
 * @return bool|WP_Error
 */
if (!function_exists('chequearElectron')) {
    function chequearElectron()
    {
        if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
            return true;
        }
        return new WP_Error('forbidden', 'Acceso no autorizado', ['status' => 403]);
    }
}

/**
 * Verifica cambios en audios del usuario.
 * 
 * @deprecated Usar SyncService::verificarCambios()
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
if (!function_exists('verificarCambiosAudios')) {
    function verificarCambiosAudios(WP_REST_Request $request)
    {
        $syncService = SyncService::obtenerInstancia();
        $userId = (int) $request->get_param('user_id');
        $lastSync = isset($_GET['last_sync']) ? (int) $_GET['last_sync'] : 0;
        $forceSync = $request->get_param('force') === 'true';

        $resultado = $syncService->verificarCambios($userId, $lastSync, $forceSync);
        return rest_ensure_response($resultado);
    }
}

/**
 * Obtiene los audios de un usuario para sincronización.
 * 
 * @deprecated Usar SyncService::obtenerAudiosUsuario()
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
if (!function_exists('obtenerAudiosUsuario')) {
    function obtenerAudiosUsuario(WP_REST_Request $request)
    {
        $syncService = SyncService::obtenerInstancia();
        $userId = (int) $request->get_param('user_id');
        $postId = $request->get_param('post_id') ? (int) $request->get_param('post_id') : null;

        $audios = $syncService->obtenerAudiosUsuario($userId, $postId);
        return rest_ensure_response($audios);
    }
}

/**
 * Descarga audios para sincronización.
 * 
 * @deprecated Usar SyncService::descargarAudio()
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
if (!function_exists('descargarAudiosSync')) {
    function descargarAudiosSync(WP_REST_Request $request)
    {
        $syncService = SyncService::obtenerInstancia();
        $token = $request->get_param('token');
        $nonce = $request->get_param('nonce');

        $resultado = $syncService->descargarAudio($token, $nonce);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        $syncService->enviarArchivo(
            $resultado['filePath'],
            $resultado['mimeType'],
            $resultado['fileName']
        );

        return rest_ensure_response(['success' => true]);
    }
}

/**
 * Obtiene imagen optimizada de un post.
 * 
 * @deprecated Usar SyncService (método privado)
 * @param int $postId ID del post.
 * @return string|null URL de la imagen.
 */
if (!function_exists('obtenerImagenOptimizada')) {
    function obtenerImagenOptimizada($postId)
    {
        $portadaId = get_post_thumbnail_id($postId);
        if ($portadaId) {
            $portadaUrl = wp_get_attachment_url($portadaId);
            if ($portadaUrl && function_exists('img')) {
                return img($portadaUrl);
            }
            return $portadaUrl;
        }

        $imagenTemporalId = get_post_meta($postId, 'imagenTemporal', true);
        if ($imagenTemporalId) {
            $imagenTemporalUrl = wp_get_attachment_url($imagenTemporalId);
            if ($imagenTemporalUrl && function_exists('img')) {
                return img($imagenTemporalUrl);
            }
            return $imagenTemporalUrl;
        }

        return null;
    }
}

/**
 * Obtiene información de un usuario.
 * 
 * @deprecated Usar SyncService::obtenerInfoUsuario()
 * @param WP_REST_Request $request
 * @return array|WP_Error
 */
if (!function_exists('handle_info_usuario')) {
    function handle_info_usuario(WP_REST_Request $request)
    {
        $syncService = SyncService::obtenerInstancia();
        $receptor = (int) $request->get_param('receptor');

        if ($receptor <= 0) {
            return new WP_Error('invalid_receptor', 'ID del receptor inválido.', ['status' => 400]);
        }

        return $syncService->obtenerInfoUsuario($receptor);
    }
}

/**
 * Actualiza el timestamp de descargas.
 * 
 * @deprecated Usar SyncService::actualizarTimestampDescargas()
 * @param int $userId ID del usuario.
 */
if (!function_exists('actualizarTimestampDescargas')) {
    function actualizarTimestampDescargas($userId)
    {
        $syncService = SyncService::obtenerInstancia();
        $syncService->actualizarTimestampDescargas((int) $userId);
    }
}

/**
 * Actualiza el timestamp de samples guardados.
 * 
 * @deprecated Usar SyncService::actualizarTimestampSamplesGuardados()
 * @param int $userId ID del usuario.
 */
if (!function_exists('actualizarTimestampSamplesGuardados')) {
    function actualizarTimestampSamplesGuardados($userId)
    {
        $syncService = SyncService::obtenerInstancia();
        $syncService->actualizarTimestampSamplesGuardados((int) $userId);
    }
}
