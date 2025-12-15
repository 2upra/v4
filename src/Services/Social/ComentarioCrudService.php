<?php

/**
 * Servicio CRUD para comentarios
 * 
 * Maneja la creacion y eliminacion de comentarios
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ComentarioCrudService
{
    private static ?ComentarioCrudService $instancia = null;
    private ?\Logger $logger;
    private ComentarioUtilService $utilService;

    private function __construct()
    {
        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
        $this->utilService = ComentarioUtilService::obtenerInstancia();
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
     * Crear un nuevo comentario
     *
     * @param array $datos Datos del comentario
     * @return array ['exito' => bool, 'comentarioId' => int|null, 'mensaje' => string]
     */
    public function crearComentario(array $datos): array
    {
        $userId = $datos['userId'];
        $postId = $datos['postId'];
        $comentarioTexto = $datos['comentario'];
        $imagenUrl = $datos['imagenUrl'] ?? '';
        $audioUrl = $datos['audioUrl'] ?? '';
        $imagenId = $datos['imagenId'] ?? '';
        $audioId = $datos['audioId'] ?? '';

        /* Generar titulo del comentario */
        $tituloPost = get_the_title($postId);
        $tituloPostCorto = wp_trim_words($tituloPost, 10, '...');
        $usuarioActual = wp_get_current_user();
        $nombreUsuario = $usuarioActual->display_name;
        $tituloComentario = sanitize_text_field(
            $nombreUsuario . ' hace un comentario en ' . $tituloPostCorto
        );

        /* Crear el post del comentario */
        $comentarioId = wp_insert_post([
            'post_title'   => $tituloComentario,
            'post_content' => $comentarioTexto,
            'post_status'  => 'publish',
            'post_type'    => 'comentarios',
            'post_author'  => $userId,
        ]);

        if (is_wp_error($comentarioId)) {
            if ($this->logger) {
                $this->logger->error(
                    'post',
                    '[ComentarioCrudService] Error al crear comentario: ' . $comentarioId->get_error_message()
                );
            }
            return [
                'exito' => false,
                'comentarioId' => null,
                'mensaje' => 'Error al crear el comentario.'
            ];
        }

        /* Adjuntar archivos si existen */
        $attachmentImageId = $this->utilService->adjuntarArchivoSiExiste($comentarioId, $imagenUrl);
        $attachmentAudioId = $this->utilService->adjuntarArchivoSiExiste($comentarioId, $audioUrl);

        /* Actualizar metadatos del comentario */
        $this->utilService->guardarMetadatosComentario(
            $comentarioId,
            $postId,
            $imagenId,
            $audioId,
            $attachmentImageId,
            $attachmentAudioId
        );

        /* Asociar comentario al post original */
        $this->utilService->asociarComentarioAPost($postId, $comentarioId);

        /* Crear notificacion para el autor del post */
        $this->utilService->notificarAutorPost($postId, $userId, $nombreUsuario);

        /* Incrementar contador de rate limiting */
        $this->utilService->incrementarContadorComentarios($userId);

        if ($this->logger) {
            $this->logger->info(
                'post',
                "[ComentarioCrudService] Comentario #{$comentarioId} creado exitosamente en post #{$postId}"
            );
        }

        return [
            'exito' => true,
            'comentarioId' => $comentarioId,
            'mensaje' => 'Comentario creado con exito.'
        ];
    }

    /**
     * Eliminar un comentario (solo por autor o admin)
     *
     * @param int $comentarioId ID del comentario
     * @param int $userId ID del usuario que intenta eliminar
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function eliminarComentario(int $comentarioId, int $userId): array
    {
        $comentario = get_post($comentarioId);

        if (!$comentario || $comentario->post_type !== 'comentarios') {
            return [
                'exito' => false,
                'mensaje' => 'Comentario no encontrado.'
            ];
        }

        /* Verificar permisos: autor o administrador */
        $esAutor = (int) $comentario->post_author === $userId;
        $esAdmin = current_user_can('manage_options');

        if (!$esAutor && !$esAdmin) {
            return [
                'exito' => false,
                'mensaje' => 'No tienes permisos para eliminar este comentario.'
            ];
        }

        /* Obtener post padre para actualizar array de IDs */
        $postPadreId = get_post_meta($comentarioId, 'postId', true);

        /* Eliminar el comentario */
        $resultado = wp_delete_post($comentarioId, true);

        if (!$resultado) {
            return [
                'exito' => false,
                'mensaje' => 'Error al eliminar el comentario.'
            ];
        }

        /* Actualizar array de comentarios en post padre */
        if ($postPadreId) {
            $this->utilService->desasociarComentarioDePost($postPadreId, $comentarioId);
        }

        if ($this->logger) {
            $this->logger->info(
                'post',
                "[ComentarioCrudService] Comentario #{$comentarioId} eliminado por usuario #{$userId}"
            );
        }

        return [
            'exito' => true,
            'mensaje' => 'Comentario eliminado correctamente.'
        ];
    }
}
