<?php

namespace Kamples\Services\Usuario;

/**
 * Servicio de gestión de Pinkys (moneda virtual).
 * 
 * Maneja operaciones de la moneda virtual del sistema.
 *
 * @since 2.0.0
 */
class UsuarioPinkyService
{
    private static ?UsuarioPinkyService $instancia = null;

    private function __construct() {}

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Agrega pinkys a un usuario.
     *
     * @param int $userId ID del usuario
     * @param int $cantidad Cantidad a agregar
     * @return int Nuevo balance
     */
    public function agregarPinkys(int $userId, int $cantidad): int
    {
        $monedasActuales = (int)get_user_meta($userId, 'pinky', true);
        $nuevasMonedas = $monedasActuales + $cantidad;
        update_user_meta($userId, 'pinky', $nuevasMonedas);
        return $nuevasMonedas;
    }

    /**
     * Resta pinkys a un usuario.
     *
     * @param int $userId ID del usuario
     * @param int $cantidad Cantidad a restar
     * @return int Nuevo balance
     */
    public function restarPinkys(int $userId, int $cantidad): int
    {
        $monedasActuales = (int)get_user_meta($userId, 'pinky', true);
        $nuevasMonedas = max(0, $monedasActuales - $cantidad);
        update_user_meta($userId, 'pinky', $nuevasMonedas);
        return $nuevasMonedas;
    }

    /**
     * Resta pinkys al autor de un post (usado en eliminación).
     *
     * @param int $postId ID del post
     * @return bool True si se restaron
     */
    public function restarPinkysPorEliminacion(int $postId): bool
    {
        $post = get_post($postId);
        if (!$post) {
            return false;
        }

        $userId = (int)$post->post_author;
        if ($userId) {
            $this->restarPinkys($userId, 1);
            return true;
        }
        return false;
    }

    /**
     * Asigna pinkys iniciales a un nuevo usuario.
     *
     * @param int $userId ID del usuario
     * @param int $cantidad Cantidad inicial (default: 10)
     */
    public function asignarPinkysRegistro(int $userId, int $cantidad = 10): void
    {
        update_user_meta($userId, 'pinky', $cantidad);
    }

    /**
     * Restablece los pinkys de usuarios que tienen menos del mínimo.
     *
     * @param int $minimo Cantidad mínima (default: 10)
     * @return int Cantidad de usuarios actualizados
     */
    public function restablecerPinkys(int $minimo = 10): int
    {
        $usuariosQuery = new \WP_User_Query([
            'fields' => 'ID',
        ]);

        $actualizados = 0;
        if (!empty($usuariosQuery->results)) {
            foreach ($usuariosQuery->results as $userId) {
                $monedasActuales = (int)get_user_meta($userId, 'pinky', true);
                if ($monedasActuales < $minimo) {
                    update_user_meta($userId, 'pinky', $minimo);
                    $actualizados++;
                }
            }
        }

        return $actualizados;
    }

    /**
     * Obtiene el balance de pinkys de un usuario.
     *
     * @param int $userId ID del usuario
     * @return int Balance actual
     */
    public function obtenerPinkys(int $userId): int
    {
        return (int)get_user_meta($userId, 'pinky', true);
    }
}
