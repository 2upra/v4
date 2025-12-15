<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Social\NotificacionService en su lugar.
 * 
 * Wrapper de compatibilidad que redirige al servicio refactorizado.
 * Este archivo se mantiene temporalmente para evitar errores en codigo
 * que aun no ha sido actualizado.
 *
 * @since 3.0.0
 */
class NotificacionService
{
    private static ?NotificacionService $instancia = null;
    private ?\Kamples\Services\Social\NotificacionService $servicioReal = null;

    private function __construct()
    {
        $this->servicioReal = \Kamples\Services\Social\NotificacionService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function procesarNotificacionesPendientes(): void
    {
        $this->servicioReal->procesarNotificacionesPendientes();
    }

    public function crearNotificacion(
        int $usuarioReceptor,
        string $contenido,
        bool $metaSolicitud = false,
        int $postIdRelacionado = 0,
        string $titulo = 'Nueva notificacion',
        ?string $url = null,
        ?int $emisor = null
    ) {
        return $this->servicioReal->crearNotificacion(
            $usuarioReceptor,
            $contenido,
            $metaSolicitud,
            $postIdRelacionado,
            $titulo,
            $url,
            $emisor
        );
    }

    public function enviarPushNotification(int $userId, string $title, string $message, string $url)
    {
        return $this->servicioReal->enviarPushNotification($userId, $title, $message, $url);
    }

    public function obtenerNotificaciones(int $usuarioId, int $pagina = 1): array
    {
        return $this->servicioReal->obtenerNotificaciones($usuarioId, $pagina);
    }

    public function marcarComoVista(int $notificacionId, int $userId): array
    {
        return $this->servicioReal->marcarComoVista($notificacionId, $userId);
    }

    public function tieneNotificacionesNoVistas(int $userId): bool
    {
        return $this->servicioReal->tieneNotificacionesNoVistas($userId);
    }

    public function obtenerUltimaNotificacion(int $userId): ?array
    {
        return $this->servicioReal->obtenerUltimaNotificacion($userId);
    }

    public function encolarNotificacion(array $datos): void
    {
        $this->servicioReal->encolarNotificacion($datos);
    }
}
