<?php

/**
 * Servicio de conversaciones para el sistema de chat.
 * 
 * Maneja la gestión de conversaciones: crear, obtener, validar participantes.
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

use Kamples\Services\Usuario\PerfilService;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ChatConversacionService
{
    private static ?ChatConversacionService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): ChatConversacionService
    {
        if (self::$instancia === null) {
            self::$instancia = new ChatConversacionService();
        }
        return self::$instancia;
    }

    /**
     * Obtener o crear una conversación entre dos usuarios.
     *
     * @param int  $user1 ID del usuario 1.
     * @param int  $user2 ID del usuario 2.
     * @param bool $crearSiNoExiste Crear si no existe.
     * @return int|null ID de la conversación o null.
     */
    public function obtenerConversacionId(int $user1, int $user2, bool $crearSiNoExiste = false): ?int
    {
        global $wpdb;
        $tablaConversacion = $wpdb->prefix . 'conversacion';

        $query = $wpdb->prepare("
            SELECT id FROM $tablaConversacion
            WHERE tipo = 1
            AND JSON_CONTAINS(participantes, %s)
            AND JSON_CONTAINS(participantes, %s)
            LIMIT 1
        ", json_encode($user1), json_encode($user2));

        $conversacionId = $wpdb->get_var($query);

        if ($conversacionId) {
            return (int) $conversacionId;
        }

        if ($crearSiNoExiste) {
            $participantes = json_encode([$user1, $user2], JSON_NUMERIC_CHECK);
            $wpdb->insert($tablaConversacion, [
                'tipo' => 1,
                'participantes' => $participantes,
                'fecha' => current_time('mysql')
            ]);

            $this->logger->info('chat', "Nueva conversación creada entre $user1 y $user2");
            return $wpdb->insert_id;
        }

        return null;
    }

    /**
     * Verificar si un usuario pertenece a una conversación.
     * 
     * @param int $conversacionId
     * @param int $userId
     * @return bool
     */
    public function validarParticipante(int $conversacionId, int $userId): bool
    {
        global $wpdb;
        $tablaConversacion = $wpdb->prefix . 'conversacion';

        $participantesJson = $wpdb->get_var($wpdb->prepare(
            "SELECT participantes FROM $tablaConversacion WHERE id = %d",
            $conversacionId
        ));

        if (!$participantesJson) {
            return false;
        }

        $participantes = json_decode($participantesJson, true);
        return is_array($participantes) && in_array($userId, $participantes);
    }

    /**
     * Obtener información básica de un usuario para el chat.
     *
     * @param int $userId ID del usuario.
     * @return array|null Datos del usuario o null si no existe.
     */
    public function obtenerInfoUsuario(int $userId): ?array
    {
        $usuario = get_userdata($userId);

        if (!$usuario) {
            return null;
        }

        $nombre = !empty($usuario->display_name) ? $usuario->display_name : $usuario->user_login;
        $imagen = PerfilService::obtenerInstancia()->obtenerImagenPerfil($userId);

        return [
            'id' => $userId,
            'nombre' => $nombre,
            'imagen' => $imagen ?: 'ruta_por_defecto.jpg',
        ];
    }
}
