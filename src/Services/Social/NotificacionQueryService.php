<?php

namespace Kamples\Services\Social;

/**
 * Servicio para consultas de notificaciones.
 * 
 * Responsabilidad unica: Obtener, buscar y marcar notificaciones.
 *
 * @since 3.0.0
 */
class NotificacionQueryService
{
    private static ?NotificacionQueryService $instancia = null;
    private ?\Logger $logger = null;

    /** @var int Notificaciones por pagina */
    private int $notificacionesPorPagina = 12;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene las notificaciones de un usuario.
     * 
     * @param int $usuarioId ID del usuario
     * @param int $pagina Numero de pagina
     * @return array Array con notificaciones y metadatos
     */
    public function obtenerNotificaciones(int $usuarioId, int $pagina = 1): array
    {
        $offset = ($pagina - 1) * $this->notificacionesPorPagina;

        $args = [
            'post_type'      => 'notificaciones',
            'post_status'    => 'publish',
            'posts_per_page' => $this->notificacionesPorPagina,
            'offset'         => $offset,
            'author'         => $usuarioId,
        ];

        $query = new \WP_Query($args);
        $notificaciones = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $postId = get_the_ID();

                $notificaciones[] = [
                    'id'              => $postId,
                    'titulo'          => get_the_title(),
                    'contenido'       => get_the_content(),
                    'fecha'           => get_the_date('Y-m-d H:i:s'),
                    'emisor'          => get_post_meta($postId, 'emisor', true),
                    'solicitud'       => get_post_meta($postId, 'solicitud', true),
                    'postRelacionado' => get_post_meta($postId, 'post_relacionado', true),
                    'visto'           => get_post_meta($postId, 'visto', true) === '1',
                ];
            }
            wp_reset_postdata();
        }

        return [
            'notificaciones' => $notificaciones,
            'total'          => $query->found_posts,
            'paginas'        => ceil($query->found_posts / $this->notificacionesPorPagina),
            'paginaActual'   => $pagina,
        ];
    }

    /**
     * Marca una notificacion como vista.
     * 
     * @param int $notificacionId ID de la notificacion
     * @param int $userId ID del usuario actual
     * @return array Resultado de la operacion
     */
    public function marcarComoVista(int $notificacionId, int $userId): array
    {
        if ($notificacionId <= 0 || !get_post($notificacionId)) {
            return [
                'success' => false,
                'message' => 'El ID de la notificacion no es valido.',
                'code'    => 400
            ];
        }

        $postAuthorId = intval(get_post_field('post_author', $notificacionId));

        if ($postAuthorId !== $userId) {
            $this->logger->warning('notificacion', "Permiso denegado: Usuario $userId no es el autor ($postAuthorId) del post $notificacionId.");
            return [
                'success' => false,
                'message' => 'No tienes permiso para modificar esta notificacion.',
                'code'    => 403
            ];
        }

        $metaActual = get_post_meta($notificacionId, 'visto', true);
        if ($metaActual === '1') {
            return [
                'success' => true,
                'message' => 'La notificacion ya estaba marcada como vista.',
                'notificacionId' => $notificacionId
            ];
        }

        $actualizado = update_post_meta($notificacionId, 'visto', 1);

        if ($actualizado === false) {
            global $wpdb;
            $this->logger->error('notificacion', "Fallo al actualizar la meta 'visto' para el ID: $notificacionId. Error: " . ($wpdb->last_error ?: 'ninguno'));
            return [
                'success' => false,
                'message' => 'No se pudo actualizar la meta de la notificacion.',
                'code'    => 500
            ];
        }

        $this->logger->debug('notificacion', "Meta 'visto' actualizada correctamente para el ID: $notificacionId por el usuario: $userId.");

        return [
            'success' => true,
            'message' => 'Notificacion marcada como vista.',
            'notificacionId' => $notificacionId
        ];
    }

    /**
     * Verifica si hay notificaciones no vistas para un usuario.
     * 
     * @param int $userId ID del usuario
     * @return bool True si hay notificaciones no vistas
     */
    public function tieneNotificacionesNoVistas(int $userId): bool
    {
        $args = [
            'post_type'      => 'notificaciones',
            'posts_per_page' => 1,
            'author'         => $userId,
            'meta_query'     => [
                [
                    'key'     => 'visto',
                    'value'   => '1',
                    'compare' => '!='
                ]
            ]
        ];

        $query = new \WP_Query($args);
        return $query->have_posts();
    }

    /**
     * Obtiene la ultima notificacion de un usuario.
     * 
     * @param int $userId ID del usuario
     * @return array|null Datos de la notificacion o null
     */
    public function obtenerUltimaNotificacion(int $userId): ?array
    {
        $args = [
            'post_type'      => 'notificaciones',
            'posts_per_page' => 1,
            'author'         => $userId,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            return null;
        }

        $query->the_post();
        $postId = get_the_ID();

        $notificacion = [
            'id'     => $postId,
            'titulo' => get_the_title(),
            'visto'  => get_post_meta($postId, 'visto', true) === '1',
        ];

        wp_reset_postdata();

        return $notificacion;
    }
}
