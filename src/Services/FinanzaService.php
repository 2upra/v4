<?php

namespace Kamples\Services;

/**
 * Servicio de gestión financiera.
 * 
 * Maneja el sistema de acciones, cálculos de valoración y gestión
 * de inversores/sponsors del proyecto.
 *
 * @since 1.0.0
 */
class FinanzaService
{
    private static ?FinanzaService $instancia = null;
    private \Logger $logger;

    /* 
     * Constantes de configuración
     */
    private const ACCIONES_TOTALES = 810000;
    private const TASA_DESCUENTO = 0.10;
    private const CRECIMIENTO_GANANCIAS = 0.05;
    private const VOLATILIDAD = 0.01;
    private const FECHA_INICIO_DEFAULT = '2024-01-01';

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

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
     * @param int $meses Número de meses a proyectar
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
     * Agrega acciones a un usuario (una sola vez por transacción).
     *
     * @param int $userId ID del usuario
     * @param float $montoPagado Monto pagado en USD
     * @param int $meses Meses para el cálculo
     * @param array $ingresosReales Ingresos reales
     * @param string $fechaInicio Fecha de inicio
     * @return array Resultado de la operación
     */
    public function agregarAccionesUnicaVez(
        int $userId,
        float $montoPagado,
        int $meses = 48,
        array $ingresosReales = [],
        string $fechaInicio = self::FECHA_INICIO_DEFAULT
    ): array {
        $transaccionKey = 'transaccion_' . md5((string) $montoPagado);
        $transaccionRealizada = get_user_meta($userId, $transaccionKey, true);

        if ($transaccionRealizada) {
            return [
                'status' => 'error',
                'message' => 'Esta transacción ya se ha realizado anteriormente.'
            ];
        }

        $valores = $this->calcularIngresos($meses, $ingresosReales, $fechaInicio);
        $valorAccion = $valores['valAcc'];
        $numAcciones = $montoPagado / $valorAccion;
        $accionesActuales = (int) get_user_meta($userId, 'acciones', true);
        $nuevasAcciones = $accionesActuales + $numAcciones;

        update_user_meta($userId, 'acciones', $nuevasAcciones);
        update_user_meta($userId, $transaccionKey, true);

        $this->logger->info('stripe', "Acciones agregadas para usuario $userId: $numAcciones");

        return [
            'status' => 'success',
            'user_id' => $userId,
            'acciones_compradas' => $numAcciones,
            'acciones_totales' => $nuevasAcciones,
            'valor_accion' => $valorAccion
        ];
    }

    /**
     * Define las acciones de usuarios específicos.
     *
     * @param array $usuariosAcciones Array [user_id => cantidad_acciones]
     * @param bool $actualizarSiExiste Si actualizar cuando ya existe
     */
    public function definirAccionesUsuario(array $usuariosAcciones, bool $actualizarSiExiste = false): void
    {
        foreach ($usuariosAcciones as $userId => $cantidadAcciones) {
            if (get_user_meta($userId, 'acciones', true) && $actualizarSiExiste) {
                update_user_meta($userId, 'acciones', $cantidadAcciones);
            } else {
                add_user_meta($userId, 'acciones', $cantidadAcciones, true);
            }
        }
    }

    /**
     * Obtiene el historial de acciones del usuario actual.
     *
     * @return array Historial de acciones
     */
    public function obtenerHistorialAccionesUsuario(): array
    {
        global $wpdb;
        $tablaHistorial = $wpdb->prefix . 'historial_acciones';
        $userId = get_current_user_id();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT fecha, acciones FROM $tablaHistorial WHERE user_id = %d ORDER BY fecha ASC",
            $userId
        ));
    }

    /**
     * Registra el historial de acciones de todos los usuarios.
     */
    public function registrarHistorialAcciones(): void
    {
        global $wpdb;
        $tablaHistorial = $wpdb->prefix . 'historial_acciones';
        $usuarios = get_users();

        $valores = $this->calcularIngresos(48, []);
        $valorAccion = $valores['valAcc'];
        $fecha = date('Y-m-d');

        foreach ($usuarios as $user) {
            $acciones = get_user_meta($user->ID, 'acciones', true);

            if ($acciones) {
                $wpdb->delete(
                    $tablaHistorial,
                    ['user_id' => $user->ID, 'fecha' => $fecha],
                    ['%d', '%s']
                );

                $wpdb->insert($tablaHistorial, [
                    'user_id' => $user->ID,
                    'fecha' => $fecha,
                    'acciones' => $acciones,
                    'valor' => $acciones * $valorAccion
                ], ['%d', '%s', '%d', '%f']);
            }
        }
    }

    /**
     * Calcula las acciones por usuario.
     *
     * @param bool $mostrarTodos Si mostrar todos los usuarios o solo el actual
     * @return array|string Datos de usuarios o mensaje de error
     */
    public function calcularAccionPorUsuario(bool $mostrarTodos = true)
    {
        $valores = $this->calcularIngresos(48, []);
        $valorAccion = $valores['valAcc'];

        if ($mostrarTodos) {
            $usuarios = array_filter(get_users(), function ($user) {
                return get_user_meta($user->ID, 'acciones', true);
            });

            usort($usuarios, function ($a, $b) {
                return get_user_meta($b->ID, 'acciones', true) - get_user_meta($a->ID, 'acciones', true);
            });

            array_shift($usuarios);
        } else {
            $usuarios = [wp_get_current_user()];
            $acciones = get_user_meta($usuarios[0]->ID, 'acciones', true);
            if (!$acciones) {
                return 'No tienes acciones.';
            }
        }

        $resultado = [];
        foreach ($usuarios as $user) {
            $acciones = get_user_meta($user->ID, 'acciones', true);
            $valorTotal = $acciones * $valorAccion;

            $resultado[] = [
                'user_id' => $user->ID,
                'usuario' => $user->user_login,
                'acciones' => $acciones,
                'valor_total' => $valorTotal,
                'participacion' => ($acciones / self::ACCIONES_TOTALES) * 100
            ];
        }

        return $resultado;
    }

    /**
     * Suma acciones mensuales para usuarios PRO.
     *
     * @param bool $mostrarTodos Si procesar todos los usuarios
     * @return array|string Resultado del proceso
     */
    public function sumarAccionesMensual(bool $mostrarTodos = false)
    {
        $valores = $this->calcularIngresos(48, []);
        $valorAccion = $valores['valAcc'];
        $accionesExtra = 2.5 / $valorAccion;
        $fechaActual = new \DateTime();

        if ($mostrarTodos) {
            $usuarios = get_users();
            $resultados = [];

            foreach ($usuarios as $user) {
                $acciones = get_user_meta($user->ID, 'acciones', true);
                if (!$acciones) continue;

                if (get_user_meta($user->ID, 'user_pro', true)) {
                    $acciones = $this->actualizarAccionesUsuario(
                        $user->ID,
                        $acciones,
                        $accionesExtra,
                        $fechaActual
                    );
                }

                $resultados[] = [
                    'usuario' => $user->user_login,
                    'acciones' => $acciones,
                    'valor' => $acciones * $valorAccion,
                    'participacion' => ($acciones / self::ACCIONES_TOTALES) * 100
                ];
            }

            return $resultados;
        }

        $usuarioActual = wp_get_current_user();
        $acciones = get_user_meta($usuarioActual->ID, 'acciones', true);

        if (!$acciones) {
            return 'No tienes acciones.';
        }

        if (get_user_meta($usuarioActual->ID, 'user_pro', true)) {
            $acciones = $this->actualizarAccionesUsuario(
                $usuarioActual->ID,
                $acciones,
                $accionesExtra,
                $fechaActual
            );
        }

        return [
            'usuario' => $usuarioActual->user_login,
            'acciones' => $acciones,
            'valor' => $acciones * $valorAccion,
            'participacion' => ($acciones / self::ACCIONES_TOTALES) * 100
        ];
    }

    /**
     * Obtiene todas las transacciones de compra de acciones.
     *
     * @return array Lista de transacciones
     */
    public function obtenerTodasLasTransacciones(): array
    {
        $allTransactions = [];

        foreach (get_users() as $user) {
            $compras = get_user_meta($user->ID, 'compras_acciones', true);

            if (is_array($compras)) {
                foreach ($compras as $compra) {
                    $allTransactions[] = [
                        'user_id' => $user->ID,
                        'user_email' => $user->user_email,
                        'cantidad' => $compra['cantidad'],
                        'fecha' => $compra['fecha']
                    ];
                }
            }
        }

        return $allTransactions;
    }

    /* 
     * Métodos privados de cálculo
     */

    private function validarEntradas(int $meses, array $ingresosReales, string $fechaInicio): void
    {
        if ($meses <= 0) {
            throw new \InvalidArgumentException('El número de meses debe ser un entero positivo.');
        }

        if (array_filter($ingresosReales, 'is_numeric') !== $ingresosReales) {
            throw new \InvalidArgumentException('Ingresos reales debe ser un array de números.');
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

    private function calcularValorEmpresa(float $ingresosTotales): float
    {
        return $ingresosTotales / (1 + self::TASA_DESCUENTO);
    }

    private function calcularValorAccion(float $valorEmpresa): float
    {
        return $valorEmpresa / self::ACCIONES_TOTALES;
    }

    private function actualizarAccionesUsuario(
        int $userId,
        float $acciones,
        float $accionesExtra,
        \DateTime $fechaActual
    ): float {
        $ultimaActualizacion = get_user_meta($userId, 'ultima_actualizacion_acciones', true);
        $actualizar = false;

        if ($ultimaActualizacion) {
            $fechaUltima = new \DateTime($ultimaActualizacion);
            $intervalo = $fechaUltima->diff($fechaActual);

            if ($intervalo->m >= 1 || $intervalo->y > 0) {
                $actualizar = true;
            }
        } else {
            $actualizar = true;
        }

        if ($actualizar) {
            $acciones += $accionesExtra;
            update_user_meta($userId, 'acciones', $acciones);
            update_user_meta($userId, 'ultima_actualizacion_acciones', $fechaActual->format('Y-m-d'));
            $this->logger->info('stripe', "Acciones actualizadas para usuario $userId: $acciones");
        }

        return $acciones;
    }
}
