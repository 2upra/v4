<?php

/**
 * @deprecated Usar Kamples\Services\Publicacion\PostEdicionService
 * Este wrapper existe por compatibilidad. Migrar a la nueva ubicación.
 */

namespace Kamples\Services;

class PostEdicionService
{
    private static ?PostEdicionService $instancia = null;
    private \Kamples\Services\Publicacion\PostEdicionService $servicio;

    private function __construct()
    {
        $this->servicio = \Kamples\Services\Publicacion\PostEdicionService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function cambiarDescripcion(int $userId, int $postId, string $descripcion): array
    {
        return $this->servicio->cambiarDescripcion($userId, $postId, $descripcion);
    }

    public function cambiarTitulo(int $userId, int $postId, string $titulo): array
    {
        return $this->servicio->cambiarTitulo($userId, $postId, $titulo);
    }

    public function corregirTags(int $userId, int $postId, string $descripcion): array
    {
        return $this->servicio->corregirTags($userId, $postId, $descripcion);
    }
}
