<?php

/**
 * Servicio para el sistema de chat en tiempo real.
 * 
 * Encapsula la lógica de seguridad (tokens), mensajería y utilidades
 * relacionadas con el chat Galle v2.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Exception;

class ChatService
{
    /**
     * Clave secreta para generación de tokens.
     * 
     * @var string
     */
    private string $secretKey;

    /**
     * Instancia del Logger.
     * 
     * @var \Logger|null
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Obtener clave secreta del entorno
        $this->secretKey = $_ENV['GALLEKEY'] ?? '';

        // Inicializar logger si está disponible
        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
    }

    /**
     * Verificar si la clave secreta está configurada.
     *
     * @return bool
     */
    public function tieneClaveSecreta(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Generar un token de seguridad para el usuario.
     *
     * @param int $userId ID del usuario.
     * @return string Token SHA256 HMAC.
     */
    public function generarToken(int $userId): string
    {
        if (!$this->tieneClaveSecreta()) {
            $this->log('Error: La clave secreta GALLEKEY no está definida.', 'ERROR');
            return '';
        }

        $tiempoRedondeado = floor(time() / 86400); // Token válido por día

        return hash_hmac('sha256', $userId . $tiempoRedondeado, $this->secretKey);
    }

    /**
     * Verificar si un token es válido.
     * 
     * Acepta tokens del día actual y del día anterior para evitar problemas
     * en el cambio de día.
     *
     * @param string $token  Token recibido.
     * @param int    $userId ID del usuario.
     * @return bool True si el token es válido.
     */
    public function verificarToken(string $token, int $userId): bool
    {
        if (!$this->tieneClaveSecreta() || empty($token)) {
            return false;
        }

        $currentTime = time();
        $roundedTime = floor($currentTime / 86400);

        // Generar token esperado para hoy
        $expectedToken = hash_hmac('sha256', $userId . $roundedTime, $this->secretKey);

        // Generar token esperado para ayer (tolerancia de cambio de día)
        $previousRoundedTime = $roundedTime - 1;
        $previousExpectedToken = hash_hmac('sha256', $userId . $previousRoundedTime, $this->secretKey);

        // Verificar coincidencia usando comparación segura
        if (hash_equals($expectedToken, $token) || hash_equals($previousExpectedToken, $token)) {
            return true;
        }

        $this->log("Token inválido para usuario $userId. Recibido: $token, Esperado: $expectedToken", 'WARNING');
        return false;
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
        $imagen = function_exists('imagenPerfil') ? imagenPerfil($userId) : '';

        return [
            'id' => $userId,
            'nombre' => $nombre,
            'imagen' => $imagen ?: 'ruta_por_defecto.jpg', // Placeholder
        ];
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
        $tablaConversacion = $wpdb->prefix . 'conversacion';

        // Iniciar transacción
        $wpdb->query('START TRANSACTION');

        try {
            // Resolver ID de conversación
            if ($conversacionId) {
                $this->log("Usando la conversación existente con ID: $conversacionId");
            } else {
                $conversacionId = $this->obtenerConversacionId($emisor, $receptor, true);
            }

            // Guardar mensaje
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

            // Commit transacción
            $wpdb->query('COMMIT');

            $this->log("Mensaje guardado con ID: $mensajeId en la conversación: $conversacionId");
            return $mensajeId;
        } catch (Exception $e) {
            // Rollback en caso de error
            $wpdb->query('ROLLBACK');
            $this->log("Error al guardar mensaje: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
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
            return $wpdb->insert_id;
        }

        return null;
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

        // Procesar datos (decodificar JSON)
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

        // Update mensajes donde conversacion = id, emisor != lector, leido = 0
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
     * Registrar mensaje en el log del sistema.
     *
     * @param string $mensaje Mensaje a loggear.
     * @param string $nivel   Nivel de log (INFO, ERROR, WARNING).
     */
    private function log(string $mensaje, string $nivel = 'INFO'): void
    {
        if ($this->logger) {
            $metodo = strtolower($nivel);
            if (method_exists($this->logger, $metodo)) {
                $this->logger->$metodo('chat', $mensaje);
                return;
            }
        }

        // Fallback si no hay logger configurado
        if (function_exists('chatLog')) {
            chatLog($mensaje);
        } else {
            error_log("[ChatService] $mensaje");
        }
    }
}
