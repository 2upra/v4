<?php

namespace Kamples\Services;

/**
 * Servicio de gestión de estados de posts.
 * 
 * Maneja cambios de estado, verificación y permisos de posts.
 *
 * @since 1.0.0
 */
class PostEstadoService
{
    private static ?PostEstadoService $instancia = null;

    /**
     * Obtiene la instancia singleton del servicio.
     *
     * @return self
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Permite la descarga de un post.
     *
     * @param int $postId ID del post
     * @return array Resultado de la operación
     */
    public function permitirDescarga(int $postId): array
    {
        update_post_meta($postId, 'paraDescarga', true);
        return ['success' => true, 'message' => 'Descarga permitida'];
    }

    /**
     * Comprueba las colaboraciones publicadas de un usuario.
     *
     * @param int $userId ID del usuario
     * @return int Número de colaboraciones
     */
    public function comprobarColabsUsuario(int $userId): int
    {
        $args = [
            'author' => $userId,
            'post_status' => 'publish',
            'post_type' => 'colab',
            'posts_per_page' => -1,
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Cambia el estado de un post.
     *
     * @param int $postId ID del post
     * @param string $nuevoEstado Nuevo estado
     * @return array Resultado de la operación
     */
    public function cambiarEstado(int $postId, string $nuevoEstado): array
    {
        $post = get_post($postId);

        if (!$post) {
            return ['success' => false, 'message' => 'Post no encontrado'];
        }

        $post->post_status = $nuevoEstado;
        $resultado = wp_update_post($post);

        if (is_wp_error($resultado)) {
            return ['success' => false, 'message' => $resultado->get_error_message()];
        }

        return ['success' => true, 'new_status' => $nuevoEstado];
    }

    /**
     * Procesa un cambio de estado según la acción.
     *
     * @param int $postId ID del post
     * @param string $accion Acción a realizar
     * @param string|null $estadoActual Estado actual del post
     * @return array Resultado de la operación
     */
    public function procesarCambioEstado(int $postId, string $accion, ?string $estadoActual = null): array
    {
        $userId = get_current_user_id();

        if ($accion === 'aceptarcolab') {
            $colabsPublicadas = $this->comprobarColabsUsuario($userId);
            if ($colabsPublicadas >= 3) {
                return [
                    'success' => false,
                    'message' => 'Ya tienes 3 colaboraciones en curso. Debes finalizar una para aceptar otra.'
                ];
            }
        }

        $estados = [
            'toggle_post_status' => ($estadoActual == 'pending') ? 'publish' : 'pending',
            'reject_post' => 'rejected',
            'request_post_deletion' => 'pending_deletion',
            'eliminarPostRs' => 'pending_deletion',
            'rechazarcolab' => 'pending_deletion',
            'aceptarcolab' => 'publish',
        ];

        if ($accion === 'permitirDescarga') {
            return $this->permitirDescarga($postId);
        }

        if (isset($estados[$accion])) {
            return $this->cambiarEstado($postId, $estados[$accion]);
        }

        return ['success' => false, 'message' => 'Acción inválida'];
    }

    /**
     * Verifica un post (solo administradores).
     *
     * @param int $postId ID del post
     * @param int $userId ID del usuario que verifica
     * @return array Resultado de la operación
     */
    public function verificarPost(int $postId, int $userId): array
    {
        if (!user_can($userId, 'administrator')) {
            return ['success' => false, 'message' => 'No tienes permisos para verificar este post'];
        }

        update_post_meta($postId, 'Verificado', true);

        return ['success' => true, 'message' => 'Post verificado correctamente'];
    }

    /**
     * Cambia la imagen de un post.
     *
     * @param int $postId ID del post
     * @param array $archivo Datos del archivo subido ($_FILES)
     * @param int $userId ID del usuario actual
     * @return array Resultado de la operación
     */
    public function cambiarImagenPost(int $postId, array $archivo, int $userId): array
    {
        $post = get_post($postId);

        if (!$post) {
            return ['success' => false, 'message' => 'El post no existe.'];
        }

        if ((int)$post->post_author !== $userId) {
            return ['success' => false, 'message' => 'No tienes permisos para cambiar la imagen de este post.'];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($archivo, ['test_form' => false]);

        if (isset($upload['error']) || !isset($upload['file'])) {
            return ['success' => false, 'message' => 'Error al subir la imagen: ' . ($upload['error'] ?? 'Error desconocido')];
        }

        $filePath = $upload['file'];
        $fileUrl = $upload['url'];

        $attachmentId = wp_insert_attachment([
            'guid' => $fileUrl,
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($archivo['name']),
            'post_content' => '',
            'post_status' => 'inherit',
        ], $filePath, $postId);

        if (is_wp_error($attachmentId) || !$attachmentId) {
            return ['success' => false, 'message' => 'Error al guardar la imagen en la biblioteca de medios.'];
        }

        $attachData = wp_generate_attachment_metadata($attachmentId, $filePath);
        wp_update_attachment_metadata($attachmentId, $attachData);

        set_post_thumbnail($postId, $attachmentId);

        return ['success' => true, 'new_image_url' => $fileUrl];
    }
}
