<?php

/**
 * Servicio de utilidades para comentarios
 * 
 * Maneja rate limiting, validacion y gestion de metadatos
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ComentarioUtilService
{
    private static ?ComentarioUtilService $instancia = null;
    private ?\Logger $logger;
    private int $limiteComentariosPorMinuto = 3;

    private function __construct()
    {
        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
    }

    /**
     * Obtiene la instancia unica del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Verificar si el usuario puede comentar (rate limiting)
     *
     * @param int $userId ID del usuario
     * @return bool True si puede comentar, false si ha alcanzado el limite
     */
    public function usuarioPuedeComentario(int $userId): bool
    {
        $contadorReciente = get_transient('comentarios_recientes_' . $userId);

        if ($contadorReciente === false) {
            return true;
        }

        return (int) $contadorReciente < $this->limiteComentariosPorMinuto;
    }

    /**
     * Incrementar el contador de comentarios recientes del usuario
     *
     * @param int $userId ID del usuario
     */
    public function incrementarContadorComentarios(int $userId): void
    {
        $contadorReciente = get_transient('comentarios_recientes_' . $userId);

        if ($contadorReciente === false) {
            $contadorReciente = 0;
        }

        $contadorReciente++;
        set_transient('comentarios_recientes_' . $userId, $contadorReciente, 60);
    }

    /**
     * Validar datos del comentario
     *
     * @param array $datos Datos del comentario
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarDatosComentario(array $datos): array
    {
        if (empty($datos['comentario'])) {
            return [
                'valido' => false,
                'mensaje' => 'El comentario no puede estar vacio.'
            ];
        }

        if (empty($datos['postId']) || $datos['postId'] <= 0) {
            return [
                'valido' => false,
                'mensaje' => 'ID de publicacion invalido.'
            ];
        }

        $postDestino = get_post($datos['postId']);
        if (!$postDestino || $postDestino->post_status !== 'publish') {
            return [
                'valido' => false,
                'mensaje' => 'No se puede comentar en una publicacion que no existe o no esta publicada.'
            ];
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Adjuntar archivo si la URL existe
     *
     * @param int $comentarioId ID del comentario
     * @param string $url URL del archivo
     * @return int|null ID del attachment o null
     */
    public function adjuntarArchivoSiExiste(int $comentarioId, string $url): ?int
    {
        if (empty($url)) {
            return null;
        }

        if (function_exists('adjuntarArchivo')) {
            return adjuntarArchivo($comentarioId, $url);
        }

        return null;
    }

    /**
     * Guardar metadatos del comentario
     *
     * @param int $comentarioId ID del comentario
     * @param int $postId ID del post padre
     * @param string $imagenId Hash ID de la imagen
     * @param string $audioId Hash ID del audio
     * @param int|null $attachmentImageId ID del attachment de imagen
     * @param int|null $attachmentAudioId ID del attachment de audio
     */
    public function guardarMetadatosComentario(
        int $comentarioId,
        int $postId,
        string $imagenId,
        string $audioId,
        ?int $attachmentImageId,
        ?int $attachmentAudioId
    ): void {
        update_post_meta($comentarioId, 'postId', $postId);
        update_post_meta($comentarioId, 'hashIdImg', $imagenId);
        update_post_meta($comentarioId, 'hashIdAudio', $audioId);

        if ($attachmentImageId) {
            update_post_meta($comentarioId, 'imagenId', $attachmentImageId);
        }

        if ($attachmentAudioId) {
            update_post_meta($comentarioId, 'audioId', $attachmentAudioId);
        }

        /* Confirmar hash IDs si existen */
        if (!empty($imagenId) && function_exists('confirmarHashId')) {
            confirmarHashId($imagenId, $comentarioId, 'imagen');
        }

        if (!empty($audioId) && function_exists('confirmarHashId')) {
            confirmarHashId($audioId, $comentarioId, 'audio');
        }
    }

    /**
     * Asociar comentario al post original
     *
     * @param int $postId ID del post
     * @param int $comentarioId ID del comentario
     */
    public function asociarComentarioAPost(int $postId, int $comentarioId): void
    {
        $comentariosIds = get_post_meta($postId, 'comentarios_ids', true);

        if (!is_array($comentariosIds)) {
            $comentariosIds = [];
        }

        $comentariosIds[] = $comentarioId;
        update_post_meta($postId, 'comentarios_ids', $comentariosIds);
    }

    /**
     * Desasociar comentario del post
     *
     * @param int $postId ID del post
     * @param int $comentarioId ID del comentario
     */
    public function desasociarComentarioDePost(int $postId, int $comentarioId): void
    {
        $comentariosIds = get_post_meta($postId, 'comentarios_ids', true);
        if (is_array($comentariosIds)) {
            $comentariosIds = array_filter($comentariosIds, function ($id) use ($comentarioId) {
                return (int) $id !== $comentarioId;
            });
            update_post_meta($postId, 'comentarios_ids', array_values($comentariosIds));
        }
    }

    /**
     * Notificar al autor del post sobre el comentario
     *
     * @param int $postId ID del post
     * @param int $comentadorId ID del usuario que comenta
     * @param string $nombreUsuario Nombre del usuario que comenta
     */
    public function notificarAutorPost(int $postId, int $comentadorId, string $nombreUsuario): void
    {
        $post = get_post($postId);
        if (!$post) {
            return;
        }

        $usuarioReceptor = $post->post_author;

        /* No notificar si el autor comenta su propio post */
        if ($usuarioReceptor == $comentadorId) {
            return;
        }

        $contenido = $nombreUsuario . ' ha comentado tu post.';
        $titulo = 'Nuevo comentario';
        $postUrl = get_permalink($postId);

        if (function_exists('crearNotificacion')) {
            crearNotificacion($usuarioReceptor, $contenido, false, $postId, $titulo, $postUrl);
        }
    }
}
