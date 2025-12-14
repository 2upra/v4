<?php

/**
 * Servicio para gestionar likes/favoritos/dislikes de posts.
 * 
 * Encapsula toda la lógica de negocio relacionada con el sistema
 * de reacciones de posts (likes, favoritos, no me gusta).
 *
 * @package Theme_V4
 * @since 1.0.0
 */

namespace Theme\V4\Services;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class LikeService
{
    /**
     * Tipos de reacciones permitidas.
     */
    private const TIPOS_PERMITIDOS = ['like', 'favorito', 'no_me_gusta'];

    /**
     * Nombre de la tabla de likes.
     * 
     * @var string
     */
    private string $nombreTabla;

    /**
     * Instancia de wpdb.
     * 
     * @var \wpdb
     */
    private \wpdb $db;

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
        global $wpdb;
        $this->db = $wpdb;
        $this->nombreTabla = $wpdb->prefix . 'post_likes';

        // Inicializar logger
        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
    }

    /**
     * Obtener tipos de reacciones permitidas.
     *
     * @return array
     */
    public function obtenerTiposPermitidos(): array
    {
        return self::TIPOS_PERMITIDOS;
    }

    /**
     * Verificar si un tipo de reacción es válido.
     *
     * @param string $tipo Tipo de reacción.
     * @return bool
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
        // Log de la acción
        if ($this->logger) {
            $this->logger->debug('post', "[LikeService] Ejecutando acción: {$accion} tipo: {$tipo} post: {$postId} usuario: {$userId}");
        }

        // Si es una acción de like y ya existe, convertir a unlike
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
     *
     * @param int    $postId ID del post.
     * @param int    $userId ID del usuario.
     * @param string $tipo   Tipo de reacción.
     * @return bool
     */
    private function agregarReaccion(int $postId, int $userId, string $tipo): bool
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

        // Acciones post-inserción según el tipo
        $this->procesarAccionesPostReaccion($postId, $userId, $tipo);

        return true;
    }

    /**
     * Procesar acciones adicionales después de agregar una reacción.
     *
     * @param int    $postId ID del post.
     * @param int    $userId ID del usuario.
     * @param string $tipo   Tipo de reacción.
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
     *
     * @param int $postId ID del post.
     * @param int $userId ID del usuario.
     */
    private function procesarLike(int $postId, int $userId): void
    {
        // Actualizar contador de likes del usuario
        $contadorLikes = (int) get_user_meta($userId, 'like_count', true);
        $contadorLikes++;
        update_user_meta($userId, 'like_count', $contadorLikes);

        // Reiniciar feed cada 2 likes
        if ($contadorLikes % 2 === 0 && function_exists('reiniciarFeed')) {
            reiniciarFeed($userId);
        }

        // Notificar al autor
        $this->notificarAutor($postId, $userId, 'le ha dado me gusta a tu publicación.');
    }

    /**
     * Procesar acciones específicas para favoritos.
     *
     * @param int $postId ID del post.
     * @param int $userId ID del usuario.
     */
    private function procesarFavorito(int $postId, int $userId): void
    {
        $this->notificarAutor($postId, $userId, 'le ha encantado tu publicación.');
    }

    /**
     * Notificar al autor del post sobre una reacción.
     *
     * @param int    $postId  ID del post.
     * @param int    $userId  ID del usuario que reacciona.
     * @param string $mensaje Mensaje de la notificación.
     */
    private function notificarAutor(int $postId, int $userId, string $mensaje): void
    {
        $autorId = (int) get_post_field('post_author', $postId);

        // No notificar si el autor es el mismo usuario
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
     *
     * @param int    $postId ID del post.
     * @param int    $userId ID del usuario.
     * @param string $tipo   Tipo de reacción.
     * @return bool
     */
    private function eliminarReaccion(int $postId, int $userId, string $tipo): bool
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

        // Decrementar contador si es un like
        if ($tipo === 'like') {
            $contadorLikes = (int) get_user_meta($userId, 'like_count', true);
            $contadorLikes = max(0, $contadorLikes - 1);
            update_user_meta($userId, 'like_count', $contadorLikes);

            // Reiniciar feed si aplica
            if ($contadorLikes % 2 === 0 && $contadorLikes > 0 && function_exists('reiniciarFeed')) {
                reiniciarFeed($userId);
            }
        }

        return true;
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
            // Contar likes y favoritos combinados
            $resultado = $this->db->get_var($this->db->prepare(
                "SELECT COUNT(*) FROM {$this->nombreTabla} WHERE post_id = %d AND like_type IN ('like', 'favorito')",
                $postId
            ));
        } else {
            // Contar tipo específico
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
     *
     * @param int    $postId ID del post.
     * @param int    $userId ID del usuario.
     * @param string $tipo   Tipo de reacción.
     * @return bool
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
}
