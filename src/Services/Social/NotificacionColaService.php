<?php

namespace Kamples\Services\Social;

/**
 * Servicio para gestion de cola de notificaciones.
 * 
 * Responsabilidad unica: Encolar y procesar notificaciones pendientes via cron.
 *
 * @since 3.0.0
 */
class NotificacionColaService
{
    private static ?NotificacionColaService $instancia = null;
    private ?\Logger $logger = null;
    private ?NotificacionCrudService $crudService = null;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->crudService = NotificacionCrudService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Procesa las notificaciones pendientes en cola.
     * 
     * Se ejecuta mediante cron para procesar lotes de 5 notificaciones.
     */
    public function procesarNotificacionesPendientes(): void
    {
        $notificacionesPendientes = get_option('notificaciones_pendientes', []);

        if (empty($notificacionesPendientes)) {
            $this->logger->debug('notificacion', 'No hay notificaciones pendientes.');
            return;
        }

        $lote = array_splice($notificacionesPendientes, 0, 5);

        foreach ($lote as $notificacion) {
            $this->enviarNotificacion($notificacion);
        }

        update_option('notificaciones_pendientes', $notificacionesPendientes);

        if (empty($notificacionesPendientes)) {
            $this->logger->info('notificacion', 'No quedan notificaciones pendientes. Desactivando el cron.');
            wp_clear_scheduled_hook('wp_enqueue_notifications');
        }
    }

    /**
     * Envia una notificacion individual desde la cola.
     * 
     * @param array $notificacion Datos de la notificacion
     */
    private function enviarNotificacion(array $notificacion): void
    {
        $autorId = intval($notificacion['autor_id'] ?? 0);

        if ($autorId === 10000) {
            $this->crudService->enviarNotificacionMasiva($notificacion, $autorId);
        } else {
            $this->crudService->enviarNotificacionIndividual($notificacion, $autorId);
        }
    }

    /**
     * Encola una notificacion para procesamiento asincrono.
     * 
     * @param array $datos Datos de la notificacion
     */
    public function encolarNotificacion(array $datos): void
    {
        $pendientes = get_option('notificaciones_pendientes', []);
        $pendientes[] = $datos;
        update_option('notificaciones_pendientes', $pendientes);

        if (!wp_next_scheduled('wp_enqueue_notifications')) {
            wp_schedule_event(time(), 'every_minute', 'wp_enqueue_notifications');
        }
    }

    /**
     * Obtiene el numero de notificaciones pendientes en cola.
     * 
     * @return int Numero de notificaciones pendientes
     */
    public function contarPendientes(): int
    {
        $pendientes = get_option('notificaciones_pendientes', []);
        return count($pendientes);
    }

    /**
     * Limpia todas las notificaciones pendientes de la cola.
     */
    public function limpiarCola(): void
    {
        update_option('notificaciones_pendientes', []);
        wp_clear_scheduled_hook('wp_enqueue_notifications');
        $this->logger->info('notificacion', 'Cola de notificaciones limpiada manualmente.');
    }
}
