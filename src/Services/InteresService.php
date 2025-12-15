<?php

/**
 * @deprecated Usar Kamples\Services\Usuario\InteresService
 * Este wrapper existe por compatibilidad. Migrar a la nueva ubicación.
 */

namespace Kamples\Services;

class InteresService
{
    private static ?InteresService $instancia = null;
    private \Kamples\Services\Usuario\InteresService $servicio;

    private function __construct()
    {
        $this->servicio = \Kamples\Services\Usuario\InteresService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function generarMetaDeIntereses(int $userId): bool
    {
        return $this->servicio->generarMetaDeIntereses($userId);
    }

    public function obtenerLikesDelUsuario(int $userId, int $limit = 500): array
    {
        return $this->servicio->obtenerLikesDelUsuario($userId, $limit);
    }
}
