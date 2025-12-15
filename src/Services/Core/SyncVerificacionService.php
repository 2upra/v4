<?php

/**
 * Servicio de verificación de cambios para sincronización.
 * 
 * Maneja la verificación de timestamps y detección de cambios
 * entre la aplicación Electron y el servidor.
 *
 * @package Kamples\Services\Core
 * @since 1.0.0
 */

namespace Kamples\Services\Core;

class SyncVerificacionService
{
    private static ?SyncVerificacionService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): SyncVerificacionService
    {
        if (self::$instancia === null) {
            self::$instancia = new SyncVerificacionService();
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
        $this->logger->debug('sync', '[verificarCambios] Inicio', [
            'userId' => $userId,
            'lastSync' => $lastSyncTimestamp,
            'force' => $forceSync
        ]);

        /* Transformar ID especial (compatibilidad legacy) */
        $userId = $this->normalizarUserId($userId);

        global $wpdb;

        $descargasTimestamp = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'descargas_modificado'",
            $userId
        ));

        $samplesTimestamp = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'samplesGuardados_modificado'",
            $userId
        ));

        $respuesta = [
            'descargas_modificado' => $descargasTimestamp,
            'samplesGuardados_modificado' => $samplesTimestamp,
            'force_sync' => false,
        ];

        if ($forceSync) {
            $this->logger->info('sync', 'Forzando sincronización', ['userId' => $userId]);
            $ahora = time();
            $respuesta['descargas_modificado'] = $ahora;
            $respuesta['samplesGuardados_modificado'] = $ahora;
            $respuesta['force_sync'] = true;
        } else {
            $hayCambios = $descargasTimestamp > $lastSyncTimestamp || $samplesTimestamp > $lastSyncTimestamp;
            $this->logger->debug('sync', $hayCambios ? 'Cambios detectados' : 'Sin cambios', ['userId' => $userId]);
        }

        return $respuesta;
    }

    /**
     * Actualiza el timestamp de descargas de un usuario.
     *
     * @param int $userId ID del usuario.
     */
    public function actualizarTimestampDescargas(int $userId): void
    {
        $tiempo = time();
        update_user_meta($userId, 'descargas_modificado', $tiempo);
        $this->logger->debug('sync', 'Timestamp descargas actualizado', [
            'userId' => $userId,
            'timestamp' => $tiempo
        ]);
    }

    /**
     * Actualiza el timestamp de samples guardados de un usuario.
     *
     * @param int $userId ID del usuario.
     */
    public function actualizarTimestampSamplesGuardados(int $userId): void
    {
        update_user_meta($userId, 'samplesGuardados_modificado', time());
    }

    /**
     * Normaliza el ID del usuario (compatibilidad legacy).
     *
     * @param int $userId ID del usuario.
     * @return int ID normalizado.
     */
    public function normalizarUserId(int $userId): int
    {
        return ($userId === 355) ? 1 : $userId;
    }
}
