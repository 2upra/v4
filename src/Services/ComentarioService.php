<?php

/**
 * Servicio para gestionar comentarios de posts.
 * 
 * Encapsula toda la lógica de negocio relacionada con el sistema
 * de comentarios personalizados (CPT 'comentarios').
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services;

/* Evitar acceso directo */

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ComentarioService
{
    /**
     * Instancia del Logger.
     * 
     * @var \Logger|null
     */
    private $logger;

    /**
     * Límite de comentarios por minuto por usuario.
     * 
     * @var int
     */
    private int $limiteComentariosPorMinuto = 3;

    /**
     * Comentarios por página para paginación.
     * 
     * @var int
     */
    private int $comentariosPorPagina = 12;

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
     * Verificar si el usuario puede comentar (rate limiting).
     *
     * @param int $userId ID del usuario.
     * @return bool True si puede comentar, false si ha alcanzado el límite.
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
     * Incrementar el contador de comentarios recientes del usuario.
     *
     * @param int $userId ID del usuario.
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
     * Validar datos del comentario.
     *
     * @param array $datos Datos del comentario.
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarDatosComentario(array $datos): array
    {
        if (empty($datos['comentario'])) {
            return [
                'valido' => false,
                'mensaje' => 'El comentario no puede estar vacío.'
            ];
        }

        if (empty($datos['postId']) || $datos['postId'] <= 0) {
            return [
                'valido' => false,
                'mensaje' => 'ID de publicación inválido.'
            ];
        }

        $postDestino = get_post($datos['postId']);
        if (!$postDestino || $postDestino->post_status !== 'publish') {
            return [
                'valido' => false,
                'mensaje' => 'No se puede comentar en una publicación que no existe o no está publicada.'
            ];
        }

        return ['valido' => true, 'mensaje' => ''];
    }

    /**
     * Crear un nuevo comentario.
     *
     * @param array $datos Datos del comentario.
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

        /* Generar título del comentario */
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
                    '[ComentarioService] Error al crear comentario: ' . $comentarioId->get_error_message()
                );
            }
            return [
                'exito' => false,
                'comentarioId' => null,
                'mensaje' => 'Error al crear el comentario.'
            ];
        }

        /* Adjuntar archivos si existen */
        $attachmentImageId = $this->adjuntarArchivoSiExiste($comentarioId, $imagenUrl);
        $attachmentAudioId = $this->adjuntarArchivoSiExiste($comentarioId, $audioUrl);

        /* Actualizar metadatos del comentario */
        $this->guardarMetadatosComentario(
            $comentarioId,
            $postId,
            $imagenId,
            $audioId,
            $attachmentImageId,
            $attachmentAudioId
        );

        /* Asociar comentario al post original */
        $this->asociarComentarioAPost($postId, $comentarioId);

        /* Crear notificación para el autor del post */
        $this->notificarAutorPost($postId, $userId, $nombreUsuario);

        /* Incrementar contador de rate limiting */
        $this->incrementarContadorComentarios($userId);

        if ($this->logger) {
            $this->logger->info(
                'post',
                "[ComentarioService] Comentario #{$comentarioId} creado exitosamente en post #{$postId}"
            );
        }

        return [
            'exito' => true,
            'comentarioId' => $comentarioId,
            'mensaje' => 'Comentario creado con éxito.'
        ];
    }

    /**
     * Adjuntar archivo si la URL existe.
     *
     * @param int    $comentarioId ID del comentario.
     * @param string $url          URL del archivo.
     * @return int|null ID del attachment o null.
     */
    private function adjuntarArchivoSiExiste(int $comentarioId, string $url): ?int
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
     * Guardar metadatos del comentario.
     *
     * @param int      $comentarioId     ID del comentario.
     * @param int      $postId           ID del post padre.
     * @param string   $imagenId         Hash ID de la imagen.
     * @param string   $audioId          Hash ID del audio.
     * @param int|null $attachmentImageId ID del attachment de imagen.
     * @param int|null $attachmentAudioId ID del attachment de audio.
     */
    private function guardarMetadatosComentario(
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
     * Asociar comentario al post original.
     *
     * @param int $postId       ID del post.
     * @param int $comentarioId ID del comentario.
     */
    private function asociarComentarioAPost(int $postId, int $comentarioId): void
    {
        $comentariosIds = get_post_meta($postId, 'comentarios_ids', true);

        if (!is_array($comentariosIds)) {
            $comentariosIds = [];
        }

        $comentariosIds[] = $comentarioId;
        update_post_meta($postId, 'comentarios_ids', $comentariosIds);
    }

    /**
     * Notificar al autor del post sobre el comentario.
     *
     * @param int    $postId        ID del post.
     * @param int    $comentadorId  ID del usuario que comenta.
     * @param string $nombreUsuario Nombre del usuario que comenta.
     */
    private function notificarAutorPost(int $postId, int $comentadorId, string $nombreUsuario): void
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

    /**
     * Obtener comentarios de un post con paginación.
     *
     * @param int $postId ID del post.
     * @param int $pagina Número de página (1-indexed).
     * @return array ['comentarios' => WP_Post[], 'hayMas' => bool, 'total' => int]
     */
    public function obtenerComentariosPost(int $postId, int $pagina = 1): array
    {
        $comentariosIds = get_post_meta($postId, 'comentarios_ids', true);

        if (empty($comentariosIds) || !is_array($comentariosIds)) {
            return [
                'comentarios' => [],
                'hayMas' => false,
                'total' => 0
            ];
        }

        $offset = ($pagina - 1) * $this->comentariosPorPagina;

        $args = [
            'post_type'      => 'comentarios',
            'post_status'    => 'publish',
            'posts_per_page' => $this->comentariosPorPagina,
            'offset'         => $offset,
            'post__in'       => $comentariosIds,
            'orderby'        => 'post__in',
        ];

        $query = new \WP_Query($args);
        $totalComentarios = count($comentariosIds);
        $hayMas = ($offset + $this->comentariosPorPagina) < $totalComentarios;

        return [
            'comentarios' => $query->posts,
            'hayMas' => $hayMas,
            'total' => $totalComentarios
        ];
    }

    /**
     * Obtener datos formateados de un comentario para renderizado.
     *
     * @param \WP_Post $comentario Post de tipo comentario.
     * @return array Datos del comentario formateados.
     */
    public function formatearComentario(\WP_Post $comentario): array
    {
        $comentarioId = $comentario->ID;
        $autorId = $comentario->post_author;
        $autor = get_userdata($autorId);

        $audio = get_post_meta($comentarioId, 'post_audio_lite', true);
        $imagenPortada = get_the_post_thumbnail_url($comentarioId, 'full');
        $audioUrl = wp_get_attachment_url(
            get_post_meta($comentarioId, 'post_audio', true)
        );

        return [
            'id' => $comentarioId,
            'autorId' => $autorId,
            'autorNombre' => $autor ? $autor->display_name : 'Usuario desconocido',
            'contenido' => $comentario->post_content,
            'fecha' => $comentario->post_date,
            'fechaRelativa' => function_exists('tiempoRelativo')
                ? tiempoRelativo($comentario->post_date)
                : human_time_diff(strtotime($comentario->post_date), current_time('timestamp')),
            'avatar' => function_exists('imagenPerfil')
                ? imagenPerfil($autorId)
                : get_avatar_url($autorId),
            'imagenPortada' => $imagenPortada
                ? (function_exists('img') ? img($imagenPortada) : $imagenPortada)
                : '',
            'audio' => $audio,
            'audioUrl' => $audioUrl ?: '',
        ];
    }

    /**
     * Eliminar un comentario (solo por autor o admin).
     *
     * @param int $comentarioId ID del comentario.
     * @param int $userId       ID del usuario que intenta eliminar.
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
            $comentariosIds = get_post_meta($postPadreId, 'comentarios_ids', true);
            if (is_array($comentariosIds)) {
                $comentariosIds = array_filter($comentariosIds, function ($id) use ($comentarioId) {
                    return (int) $id !== $comentarioId;
                });
                update_post_meta($postPadreId, 'comentarios_ids', array_values($comentariosIds));
            }
        }

        if ($this->logger) {
            $this->logger->info(
                'post',
                "[ComentarioService] Comentario #{$comentarioId} eliminado por usuario #{$userId}"
            );
        }

        return [
            'exito' => true,
            'mensaje' => 'Comentario eliminado correctamente.'
        ];
    }
}
