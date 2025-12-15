<?php

namespace Kamples\Services;

use Kamples\Services\Publicacion\PublicacionService as NuevoPublicacionService;

/**
 * @deprecated Usar Kamples\Services\Publicacion\PublicacionService
 * 
 * Wrapper de compatibilidad que redirige al nuevo servicio refactorizado.
 * Este archivo se eliminara cuando todas las referencias sean actualizadas.
 */
class PublicacionService
{
    private NuevoPublicacionService $servicio;
    private static ?PublicacionService $instancia = null;

    public function __construct()
    {
        $this->servicio = NuevoPublicacionService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function obtener(array $args = [], bool $isAjax = false, int $paged = 1): string|false
    {
        return $this->servicio->obtener($args, $isAjax, $paged);
    }

    public function construirQueryArgs(
        array $args,
        int $paged,
        mixed $userId,
        int $usuarioActual,
        string $tipoUsuario
    ): array|false {
        return $this->servicio->construirQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario);
    }

    public function procesarPublicaciones(array $queryArgs, array $args, bool $isAjax): string
    {
        return $this->servicio->procesarPublicaciones($queryArgs, $args, $isAjax);
    }

    public function obtenerColeccionesParaMomento(array $args, int $usuarioActual): string
    {
        return $this->servicio->obtenerColeccionesParaMomento($args, $usuarioActual);
    }

    public function obtenerUserId(bool $isAjax): mixed
    {
        return $this->servicio->obtenerUserId($isAjax);
    }

    public function obtenerDefaults(): array
    {
        return $this->servicio->obtenerDefaults();
    }
}
