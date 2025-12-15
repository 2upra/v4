<?php

namespace Kamples\Services;

use Kamples\Services\Feed\AlgoritmoService as NuevoAlgoritmoService;

/**
 * @deprecated Usar Kamples\Services\Feed\AlgoritmoService
 * 
 * Wrapper de compatibilidad que redirige al nuevo servicio refactorizado.
 * Este archivo se eliminara cuando todas las referencias sean actualizadas.
 */
class AlgoritmoService
{
    private NuevoAlgoritmoService $servicio;
    private static ?AlgoritmoService $instancia = null;

    private function __construct()
    {
        $this->servicio = NuevoAlgoritmoService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function calcularFeedPersonalizado(
        int $userId,
        string $identifier = '',
        ?int $similarTo = null,
        ?string $tipoUsuario = null
    ): array {
        return $this->servicio->calcularFeedPersonalizado($userId, $identifier, $similarTo, $tipoUsuario);
    }

    public function getDecayFactor(int $days, bool $useDecay = false): float
    {
        return $this->servicio->getDecayFactor($days, $useDecay);
    }

    public function recalcularSimilarToFeed(): void
    {
        $this->servicio->recalcularSimilarToFeed();
    }
}
