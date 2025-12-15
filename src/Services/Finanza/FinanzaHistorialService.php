<?php

namespace Kamples\Services\Finanza;

/**
 * Servicio de consultas e historial financiero.
 * 
 * Responsabilidad unica: Obtener historial, transacciones y estadisticas de acciones.
 *
 * @since 3.0.0
 */
class FinanzaHistorialService
{
    private static ?FinanzaHistorialService $instancia = null;
    private ?FinanzaCalculoService $calculoService = null;

    private function __construct()
    {
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

        $valores = $this->calculoService->calcularIngresos(48, []);
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
        $valores = $this->calculoService->calcularIngresos(48, []);
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
                'participacion' => ($acciones / FinanzaCalculoService::ACCIONES_TOTALES) * 100
            ];
        }

        return $resultado;
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
}
