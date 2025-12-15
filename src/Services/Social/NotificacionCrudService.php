<?php

namespace Kamples\Services\Social;

/**
 * Servicio para creacion de notificaciones en WordPress.
 * 
 * Responsabilidad unica: Creacion de posts de tipo notificacion.
 *
 * @since 3.0.0
 */
class NotificacionCrudService
{
    private static ?NotificacionCrudService $instancia = null;
    private ?\Logger $logger = null;
    private ?NotificacionPushService $pushService = null;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->pushService = NotificacionPushService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Crea una nueva notificacion.
     * 
     * @param int $usuarioReceptor ID del usuario receptor
     * @param string $contenido Contenido de la notificacion
     * @param bool $metaSolicitud Si es una solicitud
     * @param int $postIdRelacionado ID del post relacionado
     * @param string $titulo Titulo de la notificacion
     * @param string|null $url URL de la notificacion
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

        if (!$this->pushService->tieneTokenFirebase($usuarioReceptor)) {
            return $postId;
        }

        $resultadoPush = $this->pushService->enviarPushNotification($usuarioReceptor, $titulo, $contenido, $url);

        if (is_wp_error($resultadoPush)) {
            $errorCode = $resultadoPush->get_error_code();
            if (in_array($errorCode, ['not_found', 'no_token'])) {
                $this->pushService->limpiarTokenInvalido($usuarioReceptor);
            }
            return $resultadoPush;
        }

        $this->logger->info('notificacion', "Notificacion push enviada con exito al usuario ID: $usuarioReceptor");
        return $postId;
    }

    /**
     * Envia una notificacion a todos los usuarios.
     * 
     * @param array $notificacion Datos de la notificacion
     * @param int $autorId ID del autor
     */
    public function enviarNotificacionMasiva(array $notificacion, int $autorId): void
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
     * Envia una notificacion a un seguidor especifico.
     * 
     * @param array $notificacion Datos de la notificacion
     * @param int $autorId ID del autor
     */
    public function enviarNotificacionIndividual(array $notificacion, int $autorId): void
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
     * Maneja el resultado de un envio de notificacion.
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
}
