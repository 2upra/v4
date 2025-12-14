<?php

/**
 * Servicio para gestionar relaciones de seguimiento entre usuarios.
 * 
 * Encapsula toda la lógica de negocio relacionada con seguir/dejar de seguir
 * usuarios y obtener contadores de seguidores/seguidos.
 *
 * @package Theme_V4
 * @since 1.0.0
 */

namespace Theme\V4\Services;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class SeguirService
{
    /**
     * Meta key para la lista de usuarios que sigue.
     */
    private const META_SIGUIENDO = 'siguiendo';

    /**
     * Meta key para la lista de seguidores.
     */
    private const META_SEGUIDORES = 'seguidores';

    /**
     * Instancia del Logger.
     * 
     * @var \Logger
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
    }

    /**
     * Seguir a un usuario.
     *
     * @param int $seguidorId ID del usuario que sigue.
     * @param int $seguidoId  ID del usuario a seguir.
     * @return bool True si la operación fue exitosa.
     */
    public function seguir(int $seguidorId, int $seguidoId): bool
    {
        return $this->actualizarRelacion($seguidorId, $seguidoId, 'follow');
    }

    /**
     * Dejar de seguir a un usuario.
     *
     * @param int $seguidorId ID del usuario que dejará de seguir.
     * @param int $seguidoId  ID del usuario a dejar de seguir.
     * @return bool True si la operación fue exitosa.
     */
    public function dejarDeSeguir(int $seguidorId, int $seguidoId): bool
    {
        return $this->actualizarRelacion($seguidorId, $seguidoId, 'unfollow');
    }

    /**
     * Actualizar la relación de seguimiento entre dos usuarios.
     *
     * @param int    $seguidorId ID del usuario que sigue.
     * @param int    $seguidoId  ID del usuario a seguir/dejar de seguir.
     * @param string $accion     'follow' o 'unfollow'.
     * @return bool True si la operación fue exitosa.
     */
    private function actualizarRelacion(int $seguidorId, int $seguidoId, string $accion): bool
    {
        // Validar IDs
        if ($seguidorId <= 0 || $seguidoId <= 0) {
            return false;
        }

        // Evitar seguirse a sí mismo (excepto para el auto-follow inicial)
        if ($seguidorId === $seguidoId && $accion === 'follow') {
            // Permitido: un usuario se sigue a sí mismo (comportamiento especial)
        }

        // Obtener listas actuales
        $siguiendo = $this->obtenerSiguiendo($seguidorId);
        $seguidores = $this->obtenerSeguidores($seguidoId);

        if ($accion === 'follow') {
            // Verificar si ya sigue al usuario
            if (in_array($seguidoId, $siguiendo, true)) {
                return false;
            }

            $siguiendo[] = $seguidoId;
            $seguidores[] = $seguidorId;

            if ($this->logger) $this->logger->info('post', "[SeguirService] Usuario {$seguidorId} empezó a seguir a {$seguidoId}");
        } elseif ($accion === 'unfollow') {
            $siguiendo = array_values(array_diff($siguiendo, [$seguidoId]));
            $seguidores = array_values(array_diff($seguidores, [$seguidorId]));

            if ($this->logger) $this->logger->info('post', "[SeguirService] Usuario {$seguidorId} dejó de seguir a {$seguidoId}");
        }

        // Actualizar ambas listas
        $actualizadoSiguiendo = update_user_meta($seguidorId, self::META_SIGUIENDO, $siguiendo);
        $actualizadoSeguidores = update_user_meta($seguidoId, self::META_SEGUIDORES, $seguidores);

        return $actualizadoSiguiendo !== false && $actualizadoSeguidores !== false;
    }

    /**
     * Obtener lista de usuarios que sigue un usuario.
     *
     * @param int $userId ID del usuario.
     * @return array Lista de IDs de usuarios seguidos.
     */
    public function obtenerSiguiendo(int $userId): array
    {
        $siguiendo = get_user_meta($userId, self::META_SIGUIENDO, true);
        return is_array($siguiendo) ? array_map('intval', $siguiendo) : [];
    }

    /**
     * Obtener lista de seguidores de un usuario.
     *
     * @param int $userId ID del usuario.
     * @return array Lista de IDs de seguidores.
     */
    public function obtenerSeguidores(int $userId): array
    {
        $seguidores = get_user_meta($userId, self::META_SEGUIDORES, true);
        return is_array($seguidores) ? array_map('intval', $seguidores) : [];
    }

    /**
     * Contar seguidores de un usuario.
     *
     * @param int $userId ID del usuario.
     * @return int Número de seguidores.
     */
    public function contarSeguidores(int $userId): int
    {
        return count($this->obtenerSeguidores($userId));
    }

    /**
     * Contar usuarios que sigue un usuario.
     *
     * @param int $userId ID del usuario.
     * @return int Número de usuarios seguidos.
     */
    public function contarSiguiendo(int $userId): int
    {
        return count($this->obtenerSiguiendo($userId));
    }

    /**
     * Verificar si un usuario sigue a otro.
     *
     * @param int $seguidorId ID del posible seguidor.
     * @param int $seguidoId  ID del posible seguido.
     * @return bool True si seguidorId sigue a seguidoId.
     */
    public function estaSiguiendo(int $seguidorId, int $seguidoId): bool
    {
        $siguiendo = $this->obtenerSiguiendo($seguidorId);
        return in_array($seguidoId, $siguiendo, true);
    }

    /**
     * Obtener contadores de un usuario (seguidores, siguiendo, posts).
     *
     * @param int $userId ID del usuario.
     * @return array ['seguidores' => int, 'siguiendo' => int, 'posts' => int]
     */
    public function obtenerContadores(int $userId): array
    {
        $query = new \WP_Query([
            'author' => $userId,
            'post_type' => 'social_post',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        return [
            'seguidores' => $this->contarSeguidores($userId),
            'siguiendo' => $this->contarSiguiendo($userId),
            'posts' => $query->found_posts,
        ];
    }

    /**
     * Auto-seguir al propio usuario (para nuevo registro).
     *
     * @param int $userId ID del usuario.
     * @return bool True si la operación fue exitosa.
     */
    public function autoSeguir(int $userId): bool
    {
        $siguiendo = $this->obtenerSiguiendo($userId);

        if (in_array($userId, $siguiendo, true)) {
            return false; // Ya se sigue a sí mismo
        }

        $siguiendo[] = $userId;
        return update_user_meta($userId, self::META_SIGUIENDO, $siguiendo) !== false;
    }
}
