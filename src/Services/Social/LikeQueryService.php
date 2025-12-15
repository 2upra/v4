<?php

/**
 * Servicio para consultas de likes/reacciones.
 * 
 * Maneja conteos, estados y obtención de datos
 * de reacciones existentes.
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class LikeQueryService
{
    private string $nombreTabla;
    private \wpdb $db;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->nombreTabla = $wpdb->prefix . 'post_likes';
    }

    /**
     * Contar reacciones de un post.
     *
     * @param int         $postId ID del post.
     * @param string|null $tipo   Tipo de reacción (null para likes + favoritos).
     * @return int
     */
    public function contarReacciones(int $postId, ?string $tipo = null): int
    {
        if ($tipo === null) {
            /* Contar likes y favoritos combinados */
            $resultado = $this->db->get_var($this->db->prepare(
                "SELECT COUNT(*) FROM {$this->nombreTabla} WHERE post_id = %d AND like_type IN ('like', 'favorito')",
                $postId
            ));
        } else {
            /* Contar tipo específico */
            $resultado = $this->db->get_var($this->db->prepare(
                "SELECT COUNT(*) FROM {$this->nombreTabla} WHERE post_id = %d AND like_type = %s",
                $postId,
                $tipo
            ));
        }

        return (int) ($resultado ?? 0);
    }

    /**
     * Verificar si un usuario tiene una reacción en un post.
     */
    public function usuarioTieneReaccion(int $postId, int $userId, string $tipo = 'like'): bool
    {
        $resultado = $this->db->get_var($this->db->prepare(
            "SELECT COUNT(1) FROM {$this->nombreTabla} WHERE post_id = %d AND user_id = %d AND like_type = %s",
            $postId,
            $userId,
            $tipo
        ));

        return ((int) $resultado) > 0;
    }

    /**
     * Obtener todos los contadores de un post.
     *
     * @param int $postId ID del post.
     * @return array ['like' => int, 'favorito' => int, 'no_me_gusta' => int]
     */
    public function obtenerContadores(int $postId): array
    {
        return [
            'like' => $this->contarReacciones($postId),
            'favorito' => $this->contarReacciones($postId, 'favorito'),
            'no_me_gusta' => $this->contarReacciones($postId, 'no_me_gusta'),
        ];
    }

    /**
     * Obtener el estado de reacciones de un usuario en un post.
     *
     * @param int $postId ID del post.
     * @param int $userId ID del usuario.
     * @return array ['like' => bool, 'favorito' => bool, 'no_me_gusta' => bool]
     */
    public function obtenerEstadoUsuario(int $postId, int $userId): array
    {
        return [
            'like' => $this->usuarioTieneReaccion($postId, $userId, 'like'),
            'favorito' => $this->usuarioTieneReaccion($postId, $userId, 'favorito'),
            'no_me_gusta' => $this->usuarioTieneReaccion($postId, $userId, 'no_me_gusta'),
        ];
    }

    /**
     * Obtener IDs de posts que tienen like de un usuario.
     *
     * @param int $userId ID del usuario.
     * @param string $tipo Tipo de reacción (por defecto 'like').
     * @return array IDs de posts.
     */
    public function obtenerLikesDelUsuario(int $userId, string $tipo = 'like'): array
    {
        $resultados = $this->db->get_col($this->db->prepare(
            "SELECT post_id FROM {$this->nombreTabla} WHERE user_id = %d AND like_type = %s",
            $userId,
            $tipo
        ));

        return $resultados ? array_map('intval', $resultados) : [];
    }
}
