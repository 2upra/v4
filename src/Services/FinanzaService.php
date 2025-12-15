<?php

namespace Kamples\Services;

/**
 * @deprecated Usar Kamples\Services\Finanza\FinanzaService en su lugar.
 * 
 * Wrapper de compatibilidad que redirige al servicio refactorizado.
 * Este archivo se mantiene temporalmente para evitar errores en codigo
 * que aun no ha sido actualizado.
 *
 * @since 3.0.0
 */
class FinanzaService
{
    private static ?FinanzaService $instancia = null;
    private ?\Kamples\Services\Finanza\FinanzaService $servicioReal = null;

    private function __construct()
    {
        $this->servicioReal = \Kamples\Services\Finanza\FinanzaService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function calcularIngresos(
        int $meses = 48,
        array $ingresosReales = [],
        string $fechaInicio = '2024-01-01'
    ): array {
        return $this->servicioReal->calcularIngresos($meses, $ingresosReales, $fechaInicio);
    }

    public function agregarAccionesUnicaVez(
        int $userId,
        float $montoPagado,
        int $meses = 48,
        array $ingresosReales = [],
        string $fechaInicio = '2024-01-01'
    ): array {
        return $this->servicioReal->agregarAccionesUnicaVez(
            $userId,
            $montoPagado,
            $meses,
            $ingresosReales,
            $fechaInicio
        );
    }

    public function definirAccionesUsuario(array $usuariosAcciones, bool $actualizarSiExiste = false): void
    {
        $this->servicioReal->definirAccionesUsuario($usuariosAcciones, $actualizarSiExiste);
    }

    public function obtenerHistorialAccionesUsuario(): array
    {
        return $this->servicioReal->obtenerHistorialAccionesUsuario();
    }

    public function registrarHistorialAcciones(): void
    {
        $this->servicioReal->registrarHistorialAcciones();
    }

    public function calcularAccionPorUsuario(bool $mostrarTodos = true)
    {
        return $this->servicioReal->calcularAccionPorUsuario($mostrarTodos);
    }

    public function sumarAccionesMensual(bool $mostrarTodos = false)
    {
        return $this->servicioReal->sumarAccionesMensual($mostrarTodos);
    }

    public function obtenerTodasLasTransacciones(): array
    {
        return $this->servicioReal->obtenerTodasLasTransacciones();
    }

    public function calcularValorEmpresa(float $ingresosTotales): float
    {
        return $this->servicioReal->calcularValorEmpresa($ingresosTotales);
    }

    public function calcularValorAccion(float $valorEmpresa): float
    {
        return $this->servicioReal->calcularValorAccion($valorEmpresa);
    }

    public function actualizarAccionesUsuario(
        int $userId,
        float $acciones,
        float $accionesExtra,
        \DateTime $fechaActual
    ): float {
        return $this->servicioReal->actualizarAccionesUsuario(
            $userId,
            $acciones,
            $accionesExtra,
            $fechaActual
        );
    }
}
