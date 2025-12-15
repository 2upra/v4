<?php

namespace Kamples\Services\Social;

use Kreait\Firebase\Factory;

/**
 * Servicio para envio de notificaciones push via Firebase.
 * 
 * Responsabilidad unica: Gestion de Firebase y envio de notificaciones push.
 *
 * @since 3.0.0
 */
class NotificacionPushService
{
    private static ?NotificacionPushService $instancia = null;
    private ?\Logger $logger = null;

    /** @var string Ruta al archivo de credenciales de Firebase */
    private string $firebaseCredentialsPath = '/var/www/firebase_keys/upra-b6879-firebase-adminsdk-w9xma-5f138a5b75.json';

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
     * Envia una notificacion push mediante Firebase.
     * 
     * @param int $userId ID del usuario
     * @param string $title Titulo de la notificacion
     * @param string $message Mensaje de la notificacion
     * @param string $url URL asociada
     * @return string|\WP_Error Resultado del envio
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
     * Limpia el token de Firebase de un usuario cuando es invalido.
     * 
     * @param int $userId ID del usuario
     */
    public function limpiarTokenInvalido(int $userId): void
    {
        delete_user_meta($userId, 'firebase_token');
        $this->logger->warning('notificacion', "Token de Firebase eliminado para el usuario ID: $userId");
    }

    /**
     * Verifica si un usuario tiene token de Firebase.
     * 
     * @param int $userId ID del usuario
     * @return bool True si tiene token
     */
    public function tieneTokenFirebase(int $userId): bool
    {
        $token = get_user_meta($userId, 'firebase_token', true);
        return !empty($token);
    }
}
