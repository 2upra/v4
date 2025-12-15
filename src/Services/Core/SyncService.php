<?php

/**
 * Servicio de sincronización para la aplicación Electron (Fachada).
 * 
 * Orquesta la sincronización de audios entre la aplicación de escritorio
 * y el servidor. Delega a servicios especializados.
 *
 * @package Kamples\Services\Core
 * @since 1.0.0
 */

namespace Kamples\Services\Core;

class SyncService
{
    private static ?SyncService $instancia = null;
    private SyncVerificacionService $verificacionService;
    private SyncAudioService $audioService;
    private SyncDescargaService $descargaService;

    private function __construct()
    {
        $this->verificacionService = SyncVerificacionService::obtenerInstancia();
        $this->audioService = SyncAudioService::obtenerInstancia();
        $this->descargaService = SyncDescargaService::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): SyncService
    {
        if (self::$instancia === null) {
            self::$instancia = new SyncService();
        }
        return self::$instancia;
    }

    /**
     * Verifica si hay cambios en los audios del usuario desde la última sincronización.
     *
     * @param int $userId ID del usuario.
     * @param int $lastSyncTimestamp Timestamp de la última sincronización.
     * @param bool $forceSync Si es true, fuerza la sincronización.
     * @return array Datos de respuesta con timestamps y estado.
     */
    public function verificarCambios(int $userId, int $lastSyncTimestamp, bool $forceSync = false): array
    {
        return $this->verificacionService->verificarCambios($userId, $lastSyncTimestamp, $forceSync);
    }

    /**
     * Obtiene la lista de audios del usuario para sincronización.
     *
     * @param int $userId ID del usuario.
     * @param int|null $postId ID de post específico (opcional).
     * @return array Lista de audios con URLs de descarga.
     */
    public function obtenerAudiosUsuario(int $userId, ?int $postId = null): array
    {
        return $this->audioService->obtenerAudiosUsuario($userId, $postId);
    }

    /**
     * Descarga un audio mediante token de sincronización.
     *
     * @param string $token Token de descarga.
     * @param string $nonce Nonce de seguridad.
     * @return array|\WP_Error Resultado de la operación.
     */
    public function descargarAudio(string $token, string $nonce)
    {
        return $this->descargaService->descargarAudio($token, $nonce);
    }

    /**
     * Envía el archivo de audio al cliente.
     *
     * @param string $filePath Ruta del archivo.
     * @param string $mimeType Tipo MIME.
     * @param string $fileName Nombre del archivo.
     */
    public function enviarArchivo(string $filePath, string $mimeType, string $fileName): void
    {
        $this->descargaService->enviarArchivo($filePath, $mimeType, $fileName);
    }

    /**
     * Obtiene información de un usuario.
     *
     * @param int $receptorId ID del usuario receptor.
     * @return array Datos del usuario (imagen, nombre).
     */
    public function obtenerInfoUsuario(int $receptorId): array
    {
        return $this->audioService->obtenerInfoUsuario($receptorId);
    }

    /**
     * Actualiza el timestamp de descargas de un usuario.
     *
     * @param int $userId ID del usuario.
     */
    public function actualizarTimestampDescargas(int $userId): void
    {
        $this->verificacionService->actualizarTimestampDescargas($userId);
    }

    /**
     * Actualiza el timestamp de samples guardados de un usuario.
     *
     * @param int $userId ID del usuario.
     */
    public function actualizarTimestampSamplesGuardados(int $userId): void
    {
        $this->verificacionService->actualizarTimestampSamplesGuardados($userId);
    }
}
