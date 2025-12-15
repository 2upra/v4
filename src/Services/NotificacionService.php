<?php

namespace Kamples\Services;

use Kreait\Firebase\Factory;

/**
 * Servicio de gestión de notificaciones.
 * 
 * Maneja la creación, envío y gestión de notificaciones
 * tanto internas (WordPress) como push (Firebase).
 *
 * @since 2.0.0
 */
class NotificacionService
{
    private static ?NotificacionService $instancia = null;
    private ?\Logger $logger = null;

    /** @var string Ruta al archivo de credenciales de Firebase */
    private string $firebaseCredentialsPath = '/var/www/firebase_keys/upra-b6879-firebase-adminsdk-w9xma-5f138a5b75.json';

    /** @var int Notificaciones por página */
    private int $notificacionesPorPagina = 12;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia singleton del servicio.
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Procesa las notificaciones pendientes en cola.
     * 
     * Se ejecuta mediante cron para procesar lotes de 5 notificaciones.
     */
    public function procesarNotificacionesPendientes(): void
    {
        $notificacionesPendientes = get_option('notificaciones_pendientes', []);

        if (empty($notificacionesPendientes)) {
            $this->logger->debug('notificacion', 'No hay notificaciones pendientes.');
            return;
        }

        $lote = array_splice($notificacionesPendientes, 0, 5);

        foreach ($lote as $notificacion) {
            $this->enviarNotificacion($notificacion);
        }

        update_option('notificaciones_pendientes', $notificacionesPendientes);

        if (empty($notificacionesPendientes)) {
            $this->logger->info('notificacion', 'No quedan notificaciones pendientes. Desactivando el cron.');
            wp_clear_scheduled_hook('wp_enqueue_notifications');
        }
    }

    /**
     * Envía una notificación individual.
     * 
     * @param array $notificacion Datos de la notificación
     */
    private function enviarNotificacion(array $notificacion): void
    {
        $url = $notificacion['url'] ?? '';
        $autorId = intval($notificacion['autor_id'] ?? 0);

        if ($autorId === 10000) {
            $this->enviarNotificacionMasiva($notificacion, $autorId);
        } else {
            $this->enviarNotificacionIndividual($notificacion, $autorId);
        }
    }

    /**
     * Envía una notificación a todos los usuarios.
     */
    private function enviarNotificacionMasiva(array $notificacion, int $autorId): void
    {
        $usuarios = get_users();

        foreach ($usuarios as $usuario) {
            if ($usuario->ID === $autorId) {
                continue;
            }

            $resultado = $this->crearNotificacion(
                $usuario->ID,
                $notificacion['mensaje'] ?? '',
                false,
                intval($notificacion['post_id'] ?? 0),
                $notificacion['titulo'] ?? 'Nueva notificacion',
                $notificacion['url'] ?? null,
                $autorId
            );

            $this->manejarResultadoEnvio($resultado, $usuario->ID, 'usuario');
        }
    }

    /**
     * Envía una notificación a un seguidor específico.
     */
    private function enviarNotificacionIndividual(array $notificacion, int $autorId): void
    {
        $seguidorId = intval($notificacion['seguidor_id'] ?? 0);

        if ($seguidorId === $autorId) {
            $this->logger->debug('notificacion', "Se omitio la notificacion al seguidor $seguidorId porque es el mismo que el autor.");
            return;
        }

        $resultado = $this->crearNotificacion(
            $seguidorId,
            $notificacion['mensaje'] ?? '',
            false,
            intval($notificacion['post_id'] ?? 0),
            $notificacion['titulo'] ?? 'Nueva notificacion',
            $notificacion['url'] ?? null,
            $autorId
        );

        $this->manejarResultadoEnvio($resultado, $seguidorId, 'seguidor');
    }

    /**
     * Maneja el resultado de un envío de notificación.
     */
    private function manejarResultadoEnvio($resultado, int $userId, string $tipo): void
    {
        if (!is_wp_error($resultado)) {
            return;
        }

        $errorCode = $resultado->get_error_code();
        $mensaje = match ($errorCode) {
            'not_found', 'no_token' => "No se pudo enviar la notificacion al $tipo $userId (token no encontrado).",
            'usuario_invalido' => "No se pudo enviar la notificacion al $tipo $userId (usuario invalido).",
            default => "Error al enviar a $tipo $userId: " . $resultado->get_error_message()
        };

        $this->logger->warning('notificacion', $mensaje);
    }

    /**
     * Crea una nueva notificación.
     * 
     * @param int $usuarioReceptor ID del usuario receptor
     * @param string $contenido Contenido de la notificación
     * @param bool $metaSolicitud Si es una solicitud
     * @param int $postIdRelacionado ID del post relacionado
     * @param string $titulo Título de la notificación
     * @param string|null $url URL de la notificación
     * @param int|null $emisor ID del emisor
     * @return int|\WP_Error ID del post creado o error
     */
    public function crearNotificacion(
        int $usuarioReceptor,
        string $contenido,
        bool $metaSolicitud = false,
        int $postIdRelacionado = 0,
        string $titulo = 'Nueva notificacion',
        ?string $url = null,
        ?int $emisor = null
    ) {
        $usuario = get_user_by('ID', $usuarioReceptor);
        if (!$usuario) {
            $this->logger->error('notificacion', "Usuario receptor no valido ID: $usuarioReceptor");
            return new \WP_Error('usuario_invalido', "Usuario receptor no valido ID: $usuarioReceptor");
        }

        $postId = wp_insert_post([
            'post_type'    => 'notificaciones',
            'post_title'   => sanitize_text_field($titulo),
            'post_content' => wp_kses($contenido, 'post'),
            'post_author'  => $usuarioReceptor,
            'post_status'  => 'publish',
            'meta_input'   => [
                'emisor'          => $emisor ?? get_current_user_id(),
                'solicitud'       => $metaSolicitud,
                'post_relacionado' => $postIdRelacionado,
            ]
        ]);

        if (is_wp_error($postId)) {
            $this->logger->error('notificacion', "Error al crear la notificacion: " . $postId->get_error_message());
            return $postId;
        }

        $url = $url ?? get_permalink($postId);

        $firebaseToken = get_user_meta($usuarioReceptor, 'firebase_token', true);
        if (empty($firebaseToken)) {
            return $postId;
        }

        $resultadoPush = $this->enviarPushNotification($usuarioReceptor, $titulo, $contenido, $url);

        if (is_wp_error($resultadoPush)) {
            $errorCode = $resultadoPush->get_error_code();
            if (in_array($errorCode, ['not_found', 'no_token'])) {
                delete_user_meta($usuarioReceptor, 'firebase_token');
                $this->logger->warning('notificacion', "Token de Firebase eliminado para el usuario ID: $usuarioReceptor");
            }
            return $resultadoPush;
        }

        $this->logger->info('notificacion', "Notificacion push enviada con exito al usuario ID: $usuarioReceptor");
        return $postId;
    }

    /**
     * Envía una notificación push mediante Firebase.
     * 
     * @param int $userId ID del usuario
     * @param string $title Título de la notificación
     * @param string $message Mensaje de la notificación
     * @param string $url URL asociada
     * @return string|\WP_Error Resultado del envío
     */
    public function enviarPushNotification(int $userId, string $title, string $message, string $url)
    {
        if (!file_exists($this->firebaseCredentialsPath)) {
            $this->logger->error('notificacion', 'No se encontro el archivo de credenciales en ' . $this->firebaseCredentialsPath);
            return new \WP_Error('no_service_account', 'No se encontro el archivo de credenciales.', ['status' => 500]);
        }

        try {
            $factory = (new Factory)->withServiceAccount($this->firebaseCredentialsPath);
            $messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            $this->logger->error('notificacion', 'Error al inicializar Firebase: ' . $e->getMessage());
            return new \WP_Error('firebase_init_failed', 'Error al inicializar Firebase.', ['status' => 500]);
        }

        $firebaseToken = get_user_meta($userId, 'firebase_token', true);

        if (empty($firebaseToken)) {
            $this->logger->debug('notificacion', "El usuario $userId no tiene un token de Firebase.");
            return new \WP_Error('no_token', 'El usuario no tiene un token de Firebase.', ['status' => 404]);
        }

        $messageData = [
            'token' => $firebaseToken,
            'notification' => [
                'title'        => $title,
                'body'         => $message,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'icon'         => 'https://example.com/icon.png',
            ],
            'data' => [
                'url' => $url,
            ],
        ];

        try {
            $messaging->send($messageData);
            $this->logger->info('notificacion', "Notificacion enviada al usuario $userId");
            return 'Notificacion enviada con exito.';
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            $this->logger->warning('notificacion', 'Error al enviar la notificacion (NotFound): ' . $e->getMessage());
            return new \WP_Error('not_found', 'Requested entity was not found.', ['status' => 404]);
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
            $this->logger->warning('notificacion', 'Error al enviar la notificacion (InvalidMessage): ' . $e->getMessage());
            return new \WP_Error('invalid_message', 'El mensaje enviado es invalido.', ['status' => 400]);
        } catch (\Kreait\Firebase\Exception\Messaging\MessagingException $e) {
            $this->logger->error('notificacion', 'Error al enviar la notificacion: ' . $e->getMessage());
            return new \WP_Error('messaging_error', 'Error al enviar la notificacion.', ['status' => 500]);
        } catch (\Exception $e) {
            $this->logger->error('notificacion', 'Error desconocido al enviar la notificacion: ' . $e->getMessage());
            return new \WP_Error('unknown_error', 'Ocurrio un error desconocido.', ['status' => 500]);
        }
    }

    /**
     * Obtiene las notificaciones de un usuario.
     * 
     * @param int $usuarioId ID del usuario
     * @param int $pagina Número de página
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
     * Marca una notificación como vista.
     * 
     * @param int $notificacionId ID de la notificación
     * @param int $userId ID del usuario actual
     * @return array Resultado de la operación
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
     * Obtiene la última notificación de un usuario.
     * 
     * @param int $userId ID del usuario
     * @return array|null Datos de la notificación o null
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

    /**
     * Encola una notificación para procesamiento asíncrono.
     * 
     * @param array $datos Datos de la notificación
     */
    public function encolarNotificacion(array $datos): void
    {
        $pendientes = get_option('notificaciones_pendientes', []);
        $pendientes[] = $datos;
        update_option('notificaciones_pendientes', $pendientes);

        if (!wp_next_scheduled('wp_enqueue_notifications')) {
            wp_schedule_event(time(), 'every_minute', 'wp_enqueue_notifications');
        }
    }
}
