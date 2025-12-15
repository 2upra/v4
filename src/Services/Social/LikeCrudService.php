<?php

/**
 * Servicio para operaciones CRUD de likes/reacciones.
 * 
 * Maneja la inserción, eliminación y procesamiento
 * de reacciones a posts (likes, favoritos, no_me_gusta).
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class LikeCrudService
{
    /**
     * Tipos de reacciones permitidas.
     */
    private const TIPOS_PERMITIDOS = ['like', 'favorito', 'no_me_gusta'];

    private string $nombreTabla;
    private \wpdb $db;
    private $logger;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->nombreTabla = $wpdb->prefix . 'post_likes';

        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
    }

    /**
     * Obtener tipos de reacciones permitidas.
     */
    public function obtenerTiposPermitidos(): array
    {
        return self::TIPOS_PERMITIDOS;
    }

    /**
     * Verificar si un tipo de reacción es válido.
     */
    public function esTipoValido(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_PERMITIDOS, true);
    }

    /**
     * Ejecutar una acción de like/unlike.
     *
     * @param int    $postId  ID del post.
     * @param int    $userId  ID del usuario.
     * @param string $accion  Acción a realizar ('like', 'favorito', 'no_me_gusta' o 'unlike').
     * @param string $tipo    Tipo de reacción.
     * @return bool True si la operación fue exitosa.
     */
    public function ejecutarAccion(int $postId, int $userId, string $accion, string $tipo = 'like'): bool
    {
        if ($this->logger) {
            $this->logger->debug('post', "[LikeService] Ejecutando acción: {$accion} tipo: {$tipo} post: {$postId} usuario: {$userId}");
        }

        /* Si es una acción de like y ya existe, convertir a unlike */
        if ($accion === $tipo && $accion !== 'unlike') {
            if ($this->usuarioTieneReaccion($postId, $userId, $tipo)) {
                $accion = 'unlike';
            } else {
                return $this->agregarReaccion($postId, $userId, $tipo);
            }
        }

        if ($accion === 'unlike') {
            return $this->eliminarReaccion($postId, $userId, $tipo);
        }

        return false;
    }

    /**
     * Agregar una reacción a un post.
     */
    public function agregarReaccion(int $postId, int $userId, string $tipo): bool
    {
        $resultado = $this->db->insert(
            $this->nombreTabla,
            [
                'user_id' => $userId,
                'post_id' => $postId,
                'like_type' => $tipo
            ],
            ['%d', '%d', '%s']
        );

        if ($resultado === false) {
            return false;
        }

        /* Acciones post-inserción según el tipo */
        $this->procesarAccionesPostReaccion($postId, $userId, $tipo);

        return true;
    }

    /**
     * Procesar acciones adicionales después de agregar una reacción.
     */
    private function procesarAccionesPostReaccion(int $postId, int $userId, string $tipo): void
    {
        if ($tipo === 'like') {
            $this->procesarLike($postId, $userId);
        } elseif ($tipo === 'favorito') {
            $this->procesarFavorito($postId, $userId);
        }
    }

    /**
     * Procesar acciones específicas para likes.
     */
    private function procesarLike(int $postId, int $userId): void
    {
        /* Actualizar contador de likes del usuario */
        $contadorLikes = (int) get_user_meta($userId, 'like_count', true);
        $contadorLikes++;
        update_user_meta($userId, 'like_count', $contadorLikes);

        /* Reiniciar feed cada 2 likes */
        if ($contadorLikes % 2 === 0 && function_exists('reiniciarFeed')) {
            reiniciarFeed($userId);
        }

        /* Notificar al autor */
        $this->notificarAutor($postId, $userId, 'le ha dado me gusta a tu publicación.');
    }

    /**
     * Procesar acciones específicas para favoritos.
     */
    private function procesarFavorito(int $postId, int $userId): void
    {
        $this->notificarAutor($postId, $userId, 'le ha encantado tu publicación.');
    }

    /**
     * Notificar al autor del post sobre una reacción.
     */
    private function notificarAutor(int $postId, int $userId, string $mensaje): void
    {
        $autorId = (int) get_post_field('post_author', $postId);

        /* No notificar si el autor es el mismo usuario */
        if ($autorId === $userId) {
            return;
        }

        $usuario = get_userdata($userId);
        if ($usuario && function_exists('crearNotificacion')) {
            crearNotificacion(
                $autorId,
                $usuario->user_login . ' ' . $mensaje,
                false,
                $postId
            );
        }
    }

    /**
     * Eliminar una reacción de un post.
     */
    public function eliminarReaccion(int $postId, int $userId, string $tipo): bool
    {
        $resultado = $this->db->delete(
            $this->nombreTabla,
            [
                'user_id' => $userId,
                'post_id' => $postId,
                'like_type' => $tipo
            ],
            ['%d', '%d', '%s']
        );

        if ($resultado === false) {
            return false;
        }

        /* Decrementar contador si es un like */
        if ($tipo === 'like') {
            $contadorLikes = (int) get_user_meta($userId, 'like_count', true);
            $contadorLikes = max(0, $contadorLikes - 1);
            update_user_meta($userId, 'like_count', $contadorLikes);

            /* Reiniciar feed si aplica */
            if ($contadorLikes % 2 === 0 && $contadorLikes > 0 && function_exists('reiniciarFeed')) {
                reiniciarFeed($userId);
            }
        }

        return true;
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
}
