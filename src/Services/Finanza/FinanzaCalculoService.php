<?php

namespace Kamples\Services\Finanza;

/**
 * Servicio de calculos de valoracion financiera.
 * 
 * Responsabilidad unica: Calculos de ingresos, valor de empresa y acciones.
 *
 * @since 3.0.0
 */
class FinanzaCalculoService
{
    private static ?FinanzaCalculoService $instancia = null;

    /* 
     * Constantes de configuracion
     */
    public const ACCIONES_TOTALES = 810000;
    private const TASA_DESCUENTO = 0.10;
    private const CRECIMIENTO_GANANCIAS = 0.05;
    private const VOLATILIDAD = 0.01;
    public const FECHA_INICIO_DEFAULT = '2024-01-01';

    private function __construct() {}

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Calcula los ingresos y el valor de las acciones.
     *
     * @param int $meses Numero de meses a proyectar
     * @param array $ingresosReales Array de ingresos reales por mes
     * @param string $fechaInicio Fecha de inicio en formato Y-m-d
     * @return array Datos calculados: valEmp, valAcc, pIng, accionesDisponibles
     */
    public function calcularIngresos(
        int $meses = 48,
        array $ingresosReales = [],
        string $fechaInicio = self::FECHA_INICIO_DEFAULT
    ): array {
        global $wpdb;

        $this->validarEntradas($meses, $ingresosReales, $fechaInicio);

        if (empty($ingresosReales)) {
            $ingresosReales = array_fill(0, 12, 25);
        }

        $resultados = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, meta_value AS acciones
                FROM {$wpdb->usermeta}
                WHERE meta_key = %s AND user_id != %d",
                'acciones',
                1
            )
        );

        $totalAccionesUsuarios = $this->sumarAcciones($resultados);
        $accionesDisponibles = self::ACCIONES_TOTALES - $totalAccionesUsuarios;
        $factorEscasez = $this->calcularFactorEscasez($totalAccionesUsuarios);

        $ingresosMensuales = $this->generarIngresosEstimados($meses);

        if (!empty($ingresosReales)) {
            $ingresosMensuales = $this->ajustarIngresos($ingresosMensuales, $ingresosReales, $fechaInicio);
        }

        $ingresosMensuales = array_map(function ($ing) use ($factorEscasez) {
            return $this->aplicarVolatilidad($ing, $factorEscasez);
        }, $ingresosMensuales);

        $ingresosMensuales = array_slice($ingresosMensuales, 0, $meses);

        $promedioIngresos = $this->calcularPromedioIngresos($ingresosMensuales);
        $aumentoPromedio = $this->calcularAumentoPromedioMensual($ingresosMensuales);
        $ingresosTotalesEstimados = $this->estimarIngresosTotales($promedioIngresos, $aumentoPromedio, $meses);
        $valorEmpresa = $this->calcularValorEmpresa($ingresosTotalesEstimados);
        $valorAccion = $this->calcularValorAccion($valorEmpresa);

        return [
            'valEmp' => $valorEmpresa,
            'valAcc' => $valorAccion,
            'pIng' => $promedioIngresos,
            'accionesDisponibles' => $accionesDisponibles
        ];
    }

    /**
     * Calcula el valor de la empresa basado en ingresos totales.
     */
    public function calcularValorEmpresa(float $ingresosTotales): float
    {
        return $ingresosTotales / (1 + self::TASA_DESCUENTO);
    }

    /**
     * Calcula el valor de una accion basado en el valor de la empresa.
     */
    public function calcularValorAccion(float $valorEmpresa): float
    {
        return $valorEmpresa / self::ACCIONES_TOTALES;
    }

    /* 
     * Metodos privados de calculo
     */

    private function validarEntradas(int $meses, array $ingresosReales, string $fechaInicio): void
    {
        if ($meses <= 0) {
            throw new \InvalidArgumentException('El numero de meses debe ser un entero positivo.');
        }

        if (array_filter($ingresosReales, 'is_numeric') !== $ingresosReales) {
            throw new \InvalidArgumentException('Ingresos reales debe ser un array de numeros.');
        }

        if (\DateTime::createFromFormat('Y-m-d', $fechaInicio) === false) {
            throw new \InvalidArgumentException('La fecha de inicio debe estar en formato YYYY-MM-DD.');
        }
    }

    private function sumarAcciones(array $resultados): int
    {
        return array_sum(array_map(function ($row) {
            return (int) $row->acciones;
        }, $resultados));
    }

    private function calcularFactorEscasez(int $accionesUsuarios): float
    {
        return 1 + ($accionesUsuarios / self::ACCIONES_TOTALES);
    }

    private function generarIngresosEstimados(int $meses): array
    {
        return array_merge(
            array_fill(0, 12, 35.5),
            array_fill(0, 12, 60),
            array_fill(0, 12, 125),
            array_fill(0, max(0, $meses - 36), 250)
        );
    }

    private function ajustarIngresos(array $ingresosMensuales, array $ingresosReales, string $fechaInicio): array
    {
        $fechaInicioObj = new \DateTime($fechaInicio);
        $fechaActualObj = new \DateTime();
        $mesActual = (($fechaActualObj->format('Y') - $fechaInicioObj->format('Y')) * 12) +
            ($fechaActualObj->format('n') - $fechaInicioObj->format('n')) + 1;
        $mesActual = min($mesActual, count($ingresosMensuales));
        $numIngresosReales = min($mesActual, count($ingresosReales));

        for ($i = 0; $i < $numIngresosReales; $i++) {
            $ingresosMensuales[$i] = $ingresosReales[$i];
        }

        $ratios = [];
        for ($i = 0; $i < $numIngresosReales; $i++) {
            $denominador = $ingresosMensuales[$i] * 1.5;
            if ($denominador != 0) {
                $ratios[] = $ingresosReales[$i] / $denominador;
            }
        }

        $ajusteDinamico = !empty($ratios) ? pow(array_product($ratios), 1 / count($ratios)) : 1;
        $ajusteDinamico = min(max($ajusteDinamico, 0.95), 1.05);

        for ($i = $numIngresosReales; $i < count($ingresosMensuales); $i++) {
            $ingresosMensuales[$i] *= $ajusteDinamico;
        }

        return $ingresosMensuales;
    }

    private function aplicarVolatilidad(float $ingreso, float $factorEscasez): float
    {
        $variacion = (mt_rand(-100, 100) / 100) * self::VOLATILIDAD;
        return $ingreso * $factorEscasez * (1 + $variacion);
    }

    private function calcularPromedioIngresos(array $ingresosMensuales): float
    {
        return array_sum($ingresosMensuales) / max(count($ingresosMensuales), 1);
    }

    private function calcularAumentoPromedioMensual(array $ingresosMensuales): float
    {
        $numMeses = count($ingresosMensuales);
        if ($numMeses > 1) {
            return ($ingresosMensuales[$numMeses - 1] - $ingresosMensuales[0]) / ($numMeses - 1);
        }
        return 0;
    }

    private function estimarIngresosTotales(float $promedio, float $aumento, int $meses): float
    {
        $total = 0;
        for ($i = 1; $i <= $meses; $i++) {
            $total += ($promedio + $aumento * $i) * (1 + self::CRECIMIENTO_GANANCIAS);
        }
        return $total;
    }
}
