<?php

namespace Kamples\Services\Finanza;

/**
 * Servicio de gestion de acciones de usuarios.
 * 
 * Responsabilidad unica: Agregar, definir y actualizar acciones de usuarios.
 *
 * @since 3.0.0
 */
class FinanzaAccionesService
{
    private static ?FinanzaAccionesService $instancia = null;
    private ?\Logger $logger = null;
    private ?FinanzaCalculoService $calculoService = null;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->calculoService = FinanzaCalculoService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Agrega acciones a un usuario (una sola vez por transaccion).
     *
     * @param int $userId ID del usuario
     * @param float $montoPagado Monto pagado en USD
     * @param int $meses Meses para el calculo
     * @param array $ingresosReales Ingresos reales
     * @param string $fechaInicio Fecha de inicio
     * @return array Resultado de la operacion
     */
    public function agregarAccionesUnicaVez(
        int $userId,
        float $montoPagado,
        int $meses = 48,
        array $ingresosReales = [],
        string $fechaInicio = FinanzaCalculoService::FECHA_INICIO_DEFAULT
    ): array {
        $transaccionKey = 'transaccion_' . md5((string) $montoPagado);
        $transaccionRealizada = get_user_meta($userId, $transaccionKey, true);

        if ($transaccionRealizada) {
            return [
                'status' => 'error',
                'message' => 'Esta transaccion ya se ha realizado anteriormente.'
            ];
        }

        $valores = $this->calculoService->calcularIngresos($meses, $ingresosReales, $fechaInicio);
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
     * Define las acciones de usuarios especificos.
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
     * Suma acciones mensuales para usuarios PRO.
     *
     * @param bool $mostrarTodos Si procesar todos los usuarios
     * @return array|string Resultado del proceso
     */
    public function sumarAccionesMensual(bool $mostrarTodos = false)
    {
        $valores = $this->calculoService->calcularIngresos(48, []);
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
                    'participacion' => ($acciones / FinanzaCalculoService::ACCIONES_TOTALES) * 100
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
            'participacion' => ($acciones / FinanzaCalculoService::ACCIONES_TOTALES) * 100
        ];
    }

    /**
     * Actualiza las acciones de un usuario (mensualmente para PRO).
     */
    public function actualizarAccionesUsuario(
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
