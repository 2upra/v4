<?php

/**
 * Servicio de mensajes para el sistema de chat.
 * 
 * Maneja el CRUD de mensajes: guardar, obtener, marcar como leído.
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Exception;

class ChatMensajeService
{
    private static ?ChatMensajeService $instancia = null;
    private \Logger $logger;
    private ChatConversacionService $conversacionService;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->conversacionService = ChatConversacionService::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): ChatMensajeService
    {
        if (self::$instancia === null) {
            self::$instancia = new ChatMensajeService();
        }
        return self::$instancia;
    }

    /**
     * Formatear tiempo relativo (hace X minutos).
     *
     * @param string|int $fecha Timestamp o string de fecha.
     * @return string Tiempo relativo.
     */
    public function tiempoRelativo($fecha): string
    {
        $timestamp = is_numeric($fecha) ? (int)$fecha : strtotime($fecha);
        $diferencia = time() - $timestamp;

        if ($diferencia < 60) {
            return 'unos segundos';
        } elseif ($diferencia < 3600) {
            $minutos = floor($diferencia / 60);
            return "$minutos minuto" . ($minutos > 1 ? 's' : '');
        } elseif ($diferencia < 86400) {
            $horas = floor($diferencia / 3600);
            return "$horas hora" . ($horas > 1 ? 's' : '');
        } elseif ($diferencia < 604800) {
            $dias = floor($diferencia / 86400);
            return "$dias día" . ($dias > 1 ? 's' : '');
        } else {
            $semanas = floor($diferencia / 604800);
            return "$semanas semana" . ($semanas > 1 ? 's' : '');
        }
    }

    /**
     * Guardar un nuevo mensaje en la base de datos.
     *
     * @param int    $emisor         ID del usuario emisor.
     * @param int    $receptor       ID del usuario receptor (puede ser 0 si hay conversacion_id).
     * @param string $mensaje        Contenido del mensaje.
     * @param mixed  $adjunto        Datos adjuntos (opcional).
     * @param mixed  $metadata       Metadatos adicionales (opcional).
     * @param int|null $conversacionId ID de la conversación (opcional).
     * @return int ID del mensaje guardado.
     * @throws Exception Si ocurre un error al guardar.
     */
    public function guardarMensaje(int $emisor, int $receptor, string $mensaje, $adjunto = null, $metadata = null, ?int $conversacionId = null): int
    {
        global $wpdb;

        $tablaMensajes = $wpdb->prefix . 'mensajes';

        /* Iniciar transacción */
        $wpdb->query('START TRANSACTION');

        try {
            /* Resolver ID de conversación */
            if ($conversacionId) {
                $this->logger->debug('chat', "Usando conversación existente: $conversacionId");
            } else {
                $conversacionId = $this->conversacionService->obtenerConversacionId($emisor, $receptor, true);
            }

            /* Guardar mensaje */
            $datosMensaje = [
                'conversacion' => $conversacionId,
                'emisor' => $emisor,
                'mensaje' => $mensaje,
                'fecha' => current_time('mysql'),
                'adjunto' => isset($adjunto) ? json_encode($adjunto) : null,
                'metadata' => isset($metadata) ? json_encode($metadata) : null,
            ];

            $resultado = $wpdb->insert($tablaMensajes, $datosMensaje);

            if ($resultado === false) {
                throw new Exception("Error al insertar el mensaje: " . $wpdb->last_error);
            }

            $mensajeId = $wpdb->insert_id;

            /* Commit transacción */
            $wpdb->query('COMMIT');

            $this->logger->info('chat', "Mensaje guardado: $mensajeId en conversación: $conversacionId");
            return $mensajeId;
        } catch (Exception $e) {
            /* Rollback en caso de error */
            $wpdb->query('ROLLBACK');
            $this->logger->error('chat', "Error al guardar mensaje: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener mensajes de una conversación.
     *
     * @param int $conversacionId ID de la conversación.
     * @param int $page           Página.
     * @param int $perPage        Mensajes por página.
     * @return array Lista de mensajes.
     */
    public function obtenerMensajes(int $conversacionId, int $page = 1, int $perPage = 20): array
    {
        global $wpdb;
        $tablaMensajes = $wpdb->prefix . 'mensajes';

        $offset = ($page - 1) * $perPage;

        $query = $wpdb->prepare("
            SELECT id, mensaje, emisor AS remitente, fecha, adjunto, metadata, leido, conversacion
            FROM $tablaMensajes
            WHERE conversacion = %d
            ORDER BY fecha DESC
            LIMIT %d OFFSET %d
        ", $conversacionId, $perPage, $offset);

        $mensajes = $wpdb->get_results($query);

        if (!$mensajes) {
            return [];
        }

        /* Procesar datos (decodificar JSON) */
        foreach ($mensajes as $mensaje) {
            if (!empty($mensaje->adjunto)) {
                $mensaje->adjunto = json_decode($mensaje->adjunto, true);
            }
            if (!empty($mensaje->metadata)) {
                $mensaje->metadata = json_decode($mensaje->metadata, true);
            }
        }

        return array_reverse($mensajes);
    }

    /**
     * Marcar mensajes como leídos.
     * 
     * @param int $conversacionId ID conversación.
     * @param int $lectorId      ID del usuario que lee (para no marcar sus propios mensajes).
     * @return int Número de filas afectadas.
     */
    public function marcarComoLeido(int $conversacionId, int $lectorId): int
    {
        global $wpdb;
        $tablaMensajes = $wpdb->prefix . 'mensajes';

        /* Update mensajes donde conversacion = id, emisor != lector, leido = 0 */
        $sql = $wpdb->prepare("
            UPDATE $tablaMensajes 
            SET leido = 1 
            WHERE conversacion = %d 
            AND emisor != %d 
            AND leido = 0
        ", $conversacionId, $lectorId);

        $result = $wpdb->query($sql);

        return $result === false ? 0 : $result;
    }
}
