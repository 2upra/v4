<?php

/**
 * @deprecated Usar Kamples\Services\Usuario\PerfilService
 * Este wrapper existe por compatibilidad. Migrar a la nueva ubicación.
 */

namespace Kamples\Services;

class PerfilService
{
    private static ?PerfilService $instancia = null;
    private \Kamples\Services\Usuario\PerfilService $servicio;

    private function __construct()
    {
        $this->servicio = \Kamples\Services\Usuario\PerfilService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function obtenerImagenPerfil(int $userId, int $size = 96): string
    {
        return $this->servicio->obtenerImagenPerfil($userId, $size);
    }

    public function obtenerSeguidoresOSiguiendo(int $userId, string $tipo): array
    {
        return $this->servicio->obtenerSeguidoresOSiguiendo($userId, $tipo);
    }

    public function actualizarDescripcion(int $userId, string $descripcion): bool
    {
        return $this->servicio->actualizarDescripcion($userId, $descripcion);
    }

    public function actualizarPresentacion(int $userId, string $tipo, string $url): bool
    {
        return $this->servicio->actualizarPresentacion($userId, $tipo, $url);
    }

    public function obtenerPresentacion(int $userId): array
    {
        return $this->servicio->obtenerPresentacion($userId);
    }

    public function cambiarImagenPerfil(int $userId, array $file)
    {
        return $this->servicio->cambiarImagenPerfil($userId, $file);
    }

    public function cambiarNombre(int $userId, string $nuevoNombre)
    {
        return $this->servicio->cambiarNombre($userId, $nuevoNombre);
    }

    public function cambiarEnlace(int $userId, string $nuevoEnlace)
    {
        return $this->servicio->cambiarEnlace($userId, $nuevoEnlace);
    }

    public function contarOyentesUnicos(int $userId): int
    {
        return $this->servicio->contarOyentesUnicos($userId);
    }
}
