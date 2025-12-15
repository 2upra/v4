<?php

namespace Kamples\Services\Social;

/**
 * Fachada para el servicio de notificaciones.
 * 
 * Orquesta los servicios especializados de notificaciones:
 * - NotificacionPushService: Envio de notificaciones push via Firebase
 * - NotificacionCrudService: Creacion de notificaciones en WordPress
 * - NotificacionQueryService: Consultas y marcado de notificaciones
 * - NotificacionColaService: Gestion de cola y procesamiento asincrono
 *
 * @since 3.0.0
 */
class NotificacionService
{
    private static ?NotificacionService $instancia = null;

    private ?NotificacionPushService $pushService = null;
    private ?NotificacionCrudService $crudService = null;
    private ?NotificacionQueryService $queryService = null;
    private ?NotificacionColaService $colaService = null;

    private function __construct()
    {
        $this->pushService = NotificacionPushService::obtenerInstancia();
        $this->crudService = NotificacionCrudService::obtenerInstancia();
        $this->queryService = NotificacionQueryService::obtenerInstancia();
        $this->colaService = NotificacionColaService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /* 
     * Metodos delegados a NotificacionCrudService
     */

    public function crearNotificacion(
        int $usuarioReceptor,
        string $contenido,
        bool $metaSolicitud = false,
        int $postIdRelacionado = 0,
        string $titulo = 'Nueva notificacion',
        ?string $url = null,
        ?int $emisor = null
    ) {
        return $this->crudService->crearNotificacion(
            $usuarioReceptor,
            $contenido,
            $metaSolicitud,
            $postIdRelacionado,
            $titulo,
            $url,
            $emisor
        );
    }

    /* 
     * Metodos delegados a NotificacionPushService
     */

    public function enviarPushNotification(int $userId, string $title, string $message, string $url)
    {
        return $this->pushService->enviarPushNotification($userId, $title, $message, $url);
    }

    /* 
     * Metodos delegados a NotificacionQueryService
     */

    public function obtenerNotificaciones(int $usuarioId, int $pagina = 1): array
    {
        return $this->queryService->obtenerNotificaciones($usuarioId, $pagina);
    }

    public function marcarComoVista(int $notificacionId, int $userId): array
    {
        return $this->queryService->marcarComoVista($notificacionId, $userId);
    }

    public function tieneNotificacionesNoVistas(int $userId): bool
    {
        return $this->queryService->tieneNotificacionesNoVistas($userId);
    }

    public function obtenerUltimaNotificacion(int $userId): ?array
    {
        return $this->queryService->obtenerUltimaNotificacion($userId);
    }

    /* 
     * Metodos delegados a NotificacionColaService
     */

    public function procesarNotificacionesPendientes(): void
    {
        $this->colaService->procesarNotificacionesPendientes();
    }

    public function encolarNotificacion(array $datos): void
    {
        $this->colaService->encolarNotificacion($datos);
    }
}
