<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Feed\FeedService en su lugar.
 * 
 * Wrapper de compatibilidad que redirige al servicio refactorizado.
 * Este archivo se mantiene temporalmente para evitar errores en codigo
 * que aun no ha sido actualizado.
 *
 * @since 3.0.0
 */
class FeedService
{
    private static ?FeedService $instancia = null;
    private ?\Kamples\Services\Feed\FeedService $servicioReal = null;

    public function __construct()
    {
        $this->servicioReal = \Kamples\Services\Feed\FeedService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function obtenerFeedPersonalizado(
        int $idUsuario,
        string $identificador = '',
        ?int $similar = null,
        int $pagina = 1,
        bool $esAdmin = false,
        int $postsPagina = 12,
        ?string $tipoUsuario = null,
        ?array $filtrosUsuario = null
    ): array {
        return $this->servicioReal->obtenerFeedPersonalizado(
            $idUsuario,
            $identificador,
            $similar,
            $pagina,
            $esAdmin,
            $postsPagina,
            $tipoUsuario,
            $filtrosUsuario
        );
    }

    public function obtenerPostsSimilares(int $userId, int $similarTo): array
    {
        return $this->servicioReal->obtenerPostsSimilares($userId, $similarTo);
    }

    public function reiniciarFeed(int $userId): int
    {
        return $this->servicioReal->reiniciarFeed($userId);
    }

    public function obtenerDatosFeedConCache(int $userId): array
    {
        return $this->servicioReal->obtenerDatosFeedConCache($userId);
    }

    public function obtenerDatosFeed(int $userId): array
    {
        return $this->servicioReal->obtenerDatosFeed($userId);
    }

    public function obtenerUsuariosSeguidos(int $userId): array
    {
        return $this->servicioReal->obtenerUsuariosSeguidos($userId);
    }

    public function obtenerInteresesUsuario(int $userId)
    {
        return $this->servicioReal->obtenerInteresesUsuario($userId);
    }

    public function vistasDatos(int $userId): mixed
    {
        return $this->servicioReal->vistasDatos($userId);
    }

    public function obtenerIdsPostsRecientes(): array
    {
        return $this->servicioReal->obtenerIdsPostsRecientes();
    }

    public function obtenerMetadatosPosts(array $postsIds): array
    {
        return $this->servicioReal->obtenerMetadatosPosts($postsIds);
    }

    public function procesarMetadatosRoles(array $metaData): array
    {
        return $this->servicioReal->procesarMetadatosRoles($metaData);
    }

    public function obtenerLikesPorPost(array $postsIds): array
    {
        return $this->servicioReal->obtenerLikesPorPost($postsIds);
    }

    public function obtenerDatosBasicosPosts(array $postsIds)
    {
        return $this->servicioReal->obtenerDatosBasicosPosts($postsIds);
    }

    public function procesarContenidoPosts($postsResultados): array
    {
        return $this->servicioReal->procesarContenidoPosts($postsResultados);
    }

    public function calcularFeed(
        int $userId,
        string $identificador = '',
        string $similar = '',
        ?string $tipoUsuario = null,
        ?array $filtrosUsuario = null
    ): array {
        return $this->servicioReal->calcularFeed(
            $userId,
            $identificador,
            $similar,
            $tipoUsuario,
            $filtrosUsuario
        );
    }
}
