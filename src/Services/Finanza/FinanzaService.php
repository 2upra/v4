<?php

namespace Kamples\Services\Finanza;

/**
 * Fachada para el servicio de finanzas.
 * 
 * Orquesta los servicios especializados de finanzas:
 * - FinanzaCalculoService: Calculos de ingresos, valoracion empresa/acciones
 * - FinanzaAccionesService: Gestion de acciones de usuarios
 * - FinanzaHistorialService: Consultas e historial financiero
 *
 * @since 3.0.0
 */
class FinanzaService
{
    private static ?FinanzaService $instancia = null;

    private ?FinanzaCalculoService $calculoService = null;
    private ?FinanzaAccionesService $accionesService = null;
    private ?FinanzaHistorialService $historialService = null;

    private function __construct()
    {
        $this->calculoService = FinanzaCalculoService::obtenerInstancia();
        $this->accionesService = FinanzaAccionesService::obtenerInstancia();
        $this->historialService = FinanzaHistorialService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /* 
     * Metodos delegados a FinanzaCalculoService
     */

    public function calcularIngresos(
        int $meses = 48,
        array $ingresosReales = [],
        string $fechaInicio = FinanzaCalculoService::FECHA_INICIO_DEFAULT
    ): array {
        return $this->calculoService->calcularIngresos($meses, $ingresosReales, $fechaInicio);
    }

    public function calcularValorEmpresa(float $ingresosTotales): float
    {
        return $this->calculoService->calcularValorEmpresa($ingresosTotales);
    }

    public function calcularValorAccion(float $valorEmpresa): float
    {
        return $this->calculoService->calcularValorAccion($valorEmpresa);
    }

    /* 
     * Metodos delegados a FinanzaAccionesService
     */

    public function agregarAccionesUnicaVez(
        int $userId,
        float $montoPagado,
        int $meses = 48,
        array $ingresosReales = [],
        string $fechaInicio = FinanzaCalculoService::FECHA_INICIO_DEFAULT
    ): array {
        return $this->accionesService->agregarAccionesUnicaVez(
            $userId,
            $montoPagado,
            $meses,
            $ingresosReales,
            $fechaInicio
        );
    }

    public function definirAccionesUsuario(array $usuariosAcciones, bool $actualizarSiExiste = false): void
    {
        $this->accionesService->definirAccionesUsuario($usuariosAcciones, $actualizarSiExiste);
    }

    public function sumarAccionesMensual(bool $mostrarTodos = false)
    {
        return $this->accionesService->sumarAccionesMensual($mostrarTodos);
    }

    public function actualizarAccionesUsuario(
        int $userId,
        float $acciones,
        float $accionesExtra,
        \DateTime $fechaActual
    ): float {
        return $this->accionesService->actualizarAccionesUsuario(
            $userId,
            $acciones,
            $accionesExtra,
            $fechaActual
        );
    }

    /* 
     * Metodos delegados a FinanzaHistorialService
     */

    public function obtenerHistorialAccionesUsuario(): array
    {
        return $this->historialService->obtenerHistorialAccionesUsuario();
    }

    public function registrarHistorialAcciones(): void
    {
        $this->historialService->registrarHistorialAcciones();
    }

    public function calcularAccionPorUsuario(bool $mostrarTodos = true)
    {
        return $this->historialService->calcularAccionPorUsuario($mostrarTodos);
    }

    public function obtenerTodasLasTransacciones(): array
    {
        return $this->historialService->obtenerTodasLasTransacciones();
    }
}
