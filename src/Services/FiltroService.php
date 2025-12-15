<?php

namespace Kamples\Services;

use Kamples\Services\Feed\FiltroService as NuevoFiltroService;

/**
 * @deprecated Usar Kamples\Services\Feed\FiltroService
 * 
 * Wrapper de compatibilidad. Redirige todas las llamadas al nuevo servicio.
 */
class FiltroService
{
    private static ?FiltroService $instancia = null;
    private NuevoFiltroService $nuevoServicio;

    public function __construct()
    {
        $this->nuevoServicio = NuevoFiltroService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function aplicarFiltroGlobal(
        array $queryArgs,
        array $args,
        int $usuarioActual,
        ?int $userId = null,
        ?string $tipoUsuario = null
    ): array {
        return $this->nuevoServicio->aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario);
    }

    public function aplicarFiltroPorAutor(array $queryArgs, int $userId, string $filtro): array
    {
        return $this->nuevoServicio->aplicarFiltroPorAutor($queryArgs, $userId, $filtro);
    }

    public function aplicarFiltrosDeUsuario(array $queryArgs, int $usuarioId, string $filtro): array
    {
        return $this->nuevoServicio->aplicarFiltrosDeUsuario($queryArgs, $usuarioId, $filtro);
    }

    public function aplicarCondicionesDeMetaQuery(
        array $queryArgs,
        string $filtro,
        int $usuarioActual,
        ?string $tipoUsuario = null
    ): array {
        return $this->nuevoServicio->aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario);
    }

    public function obtenerCondicionesMetaQuery(int $usuarioActual, ?string $tipoUsuario = null): array
    {
        return $this->nuevoServicio->obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario);
    }

    public function obtenerFiltroActual(int $userId): array
    {
        return $this->nuevoServicio->obtenerFiltroActual($userId);
    }

    public function guardarFiltroTiempo(int $userId, int $filtroTiempo): bool
    {
        return $this->nuevoServicio->guardarFiltroTiempo($userId, $filtroTiempo);
    }

    public function guardarFiltroPost(int $userId, array $filtros): bool
    {
        return $this->nuevoServicio->guardarFiltroPost($userId, $filtros);
    }

    public function obtenerFiltros(int $userId): array
    {
        return $this->nuevoServicio->obtenerFiltros($userId);
    }

    public function obtenerFiltrosTotal(int $userId): array
    {
        return $this->nuevoServicio->obtenerFiltrosTotal($userId);
    }

    public function restablecerFiltros(int $userId, bool $restablecerPost = false, bool $restablecerColeccion = false): bool
    {
        return $this->nuevoServicio->restablecerFiltros($userId, $restablecerPost, $restablecerColeccion);
    }
}
