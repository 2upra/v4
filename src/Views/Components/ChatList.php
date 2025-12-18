<?php

/**
 * Componente de vista para la lista de chats.
 * 
 * Renderiza la lista de conversaciones de un usuario.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

use Kamples\Services\Social\ChatService;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ChatList
{
    /**
     * Servicio de chat.
     * 
     * @var ChatService
     */
    private ChatService $chatService;

    /**
     * Constructor.
     *
     * @param ChatService|null $chatService Servicio de chat.
     */
    public function __construct(?ChatService $chatService = null)
    {
        $this->chatService = $chatService ?? new ChatService();
    }

    /**
     * Obtener conversaciones de un usuario con paginación.
     *
     * @param int $userId ID del usuario.
     * @param int $page Página.
     * @param int $perPage Resultados por página.
     * @return array Lista de conversaciones.
     */
    public function obtenerConversaciones(int $userId, int $page = 1, int $perPage = 10): array
    {
        global $wpdb;
        $tablaConversacion = $wpdb->prefix . 'conversacion';
        $tablaMensajes = $wpdb->prefix . 'mensajes';

        $offset = ($page - 1) * $perPage;

        // Obtener conversaciones del usuario
        $query = $wpdb->prepare("
            SELECT id, participantes, fecha 
            FROM $tablaConversacion 
            WHERE JSON_CONTAINS(participantes, %s)
        ", json_encode($userId));

        $conversaciones = $wpdb->get_results($query);

        if (!$conversaciones) {
            return [];
        }

        // Agregar último mensaje a cada conversación
        foreach ($conversaciones as &$conversacion) {
            $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                SELECT mensaje, fecha, emisor, COALESCE(leido, FALSE) AS leido
                FROM $tablaMensajes 
                WHERE conversacion = %d 
                ORDER BY fecha DESC
                LIMIT 1
            ", $conversacion->id));

            if ($ultimoMensaje) {
                if (mb_strlen($ultimoMensaje->mensaje) > 32) {
                    $ultimoMensaje->mensaje = mb_substr($ultimoMensaje->mensaje, 0, 32) . '...';
                }
                $conversacion->ultimoMensaje = $ultimoMensaje;
            } else {
                $conversacion->ultimoMensaje = null;
            }
        }

        // Ordenar por fecha del último mensaje
        usort($conversaciones, function ($a, $b) {
            $fechaA = isset($a->ultimoMensaje->fecha) ? strtotime($a->ultimoMensaje->fecha) : 0;
            $fechaB = isset($b->ultimoMensaje->fecha) ? strtotime($b->ultimoMensaje->fecha) : 0;
            return $fechaB - $fechaA;
        });

        return array_slice($conversaciones, $offset, $perPage);
    }

    /**
     * Renderizar lista de chats.
     *
     * @param int $userId ID del usuario.
     * @return string HTML de la lista.
     */
    public function render(int $userId): string
    {
        $conversaciones = $this->obtenerConversaciones($userId);

        ob_start();

        if ($conversaciones) {
?>
            <div class="bloqueConversaciones bloque" id="bloqueConversaciones-chatIcono" style="display: none;">
                <ul class="mensajes">
                    <?php foreach ($conversaciones as $conversacion): ?>
                        <?php
                        $participantes = json_decode($conversacion->participantes);
                        $otrosParticipantes = array_diff($participantes, [$userId]);
                        $receptor = reset($otrosParticipantes);
                        $imagenPerfil = function_exists('imagenPerfil') ? imagenPerfil($receptor) : '';
                        $nombreUsuario = function_exists('obtenerNombreUsuario') ? obtenerNombreUsuario($receptor) : 'Usuario';

                        $mensajeMostrado = "Mensaje desconocido";
                        $fechaOriginal = "";
                        $leido = 0;

                        if ($conversacion->ultimoMensaje) {
                            if (!empty($conversacion->ultimoMensaje->mensaje)) {
                                $mensajeMostrado = ($conversacion->ultimoMensaje->emisor == $userId ? "Tú: " : "") . $conversacion->ultimoMensaje->mensaje;
                            }
                            $fechaOriginal = $conversacion->ultimoMensaje->fecha;
                            $leido = isset($conversacion->ultimoMensaje->leido) ? (int)$conversacion->ultimoMensaje->leido : 0;
                        }
                        ?>
                        <li class="mensaje <?= $leido ? 'leido' : 'no-leido' ?>"
                            data-receptor="<?= esc_attr($receptor) ?>"
                            data-conversacion="<?= esc_attr($conversacion->id) ?>"
                            data-leido="<?= esc_attr($leido) ?>">
                            <div class="imagenMensaje">
                                <img src="<?= esc_url($imagenPerfil) ?>" alt="Imagen de perfil">
                            </div>
                            <div class="infoMensaje">
                                <div class="nombreUsuario">
                                    <strong><?= esc_html($nombreUsuario) ?></strong>
                                </div>
                                <div class="vistaPrevia">
                                    <p><?= esc_html($mensajeMostrado) ?></p>
                                </div>
                            </div>
                            <div class="tiempoMensaje" data-fecha="<?= esc_attr($fechaOriginal) ?>">
                                <span></span>
                            </div>
                            <?php if ($leido): ?>
                                <div class="iconoLeido">✓</div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php
        } else {
        ?>
            <div class="bloqueConversaciones bloque" id="bloqueConversaciones-chatIcono" style="display: none;">
                <p>Aquí aparecerán tus mensajes</p>
            </div>
<?php
        }

        return ob_get_clean();
    }

    /**
     * Método estático para uso rápido.
     *
     * @param int $userId ID del usuario.
     * @return string HTML de la lista.
     */
    public static function mostrar(int $userId): string
    {
        $instance = new self();
        return $instance->render($userId);
    }
}

/**
 * Función global para compatibilidad.
 * 
 * @param int $usuarioId ID del usuario.
 * @return string HTML.
 */
function conversacionesUsuario(int $usuarioId): string
{
    return ChatList::mostrar($usuarioId);
}

/**
 * Handler AJAX para reiniciar chats.
 */
add_action('wp_ajax_reiniciarChats', function () {
    $usuarioId = get_current_user_id();
    $html = ChatList::mostrar($usuarioId);
    wp_send_json_success(['html' => $html]);
});
