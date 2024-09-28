<?php

define('CIPHER', 'AES-256-CBC');

// Función para cifrar un mensaje
function cifrarMensaje($mensaje, $clave, $iv)
{
    $cifrado = openssl_encrypt($mensaje, CIPHER, $clave, 0, $iv);
    return base64_encode($cifrado);
}

// Función para descifrar un mensaje
function descifrarMensaje($mensajeCifrado, $clave, $iv)
{
    $mensajeCifrado = base64_decode($mensajeCifrado);
    return openssl_decrypt($mensajeCifrado, CIPHER, $clave, 0, $iv);
}

// Función para obtener las conversaciones del usuario
function conversacionesUsuario($usuarioId)
{
    global $wpdb;
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $tablaMensajes = $wpdb->prefix . 'mensajes';

    // Obtener conversaciones que incluyan al usuario
    $query = $wpdb->prepare("
        SELECT id, participantes, fecha 
        FROM $tablaConversacion 
        WHERE JSON_CONTAINS(participantes, %s)
    ", json_encode($usuarioId));

    chatLog("Consulta de conversaciones ejecutada", $query);

    $conversaciones = $wpdb->get_results($query);

    // Verificar si se obtuvieron conversaciones
    if ($conversaciones) {
        chatLog("Conversaciones obtenidas", $conversaciones);
    } else {
        chatLog("No se encontraron conversaciones para el usuario con ID", $usuarioId);
    }

    return renderConversaciones($conversaciones, $usuarioId);
}

// Función para renderizar las conversaciones
function renderConversaciones($conversaciones, $usuarioId)
{
    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $clave = $_ENV['GALLEKEY'];  

    ob_start();  // Iniciar el buffer de salida

    if ($conversaciones) {
?>
        <div class="modal modalConversaciones">
            <ul class="mensajes">
                <?php 
                // Iterar sobre cada conversación
                foreach ($conversaciones as $conversacion):
                    $participantes = json_decode($conversacion->participantes);
                    $otrosParticipantes = array_diff($participantes, [$usuarioId]);
                    $otroParticipanteId = reset($otrosParticipantes);
                    $imagenPerfil = imagenPerfil($otroParticipanteId);  // Se asume que esta función existe

                    // Obtener el último mensaje de la conversación
                    $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                        SELECT mensaje, fecha, iv 
                        FROM $tablaMensajes 
                        WHERE conversacion = %d 
                        ORDER BY fecha DESC
                        LIMIT 1
                    ", $conversacion->id));

                    chatLog("Último mensaje obtenido", $ultimoMensaje);

                    if ($ultimoMensaje) {
                        // Verificar si el mensaje y el IV están presentes antes de descifrar
                        if (!empty($ultimoMensaje->mensaje) && !empty($ultimoMensaje->iv)) {
                            $mensajeDescifrado = descifrarMensaje($ultimoMensaje->mensaje, $clave, $ultimoMensaje->iv);
                            if ($mensajeDescifrado !== false) {
                                chatLog("Mensaje descifrado correctamente", $mensajeDescifrado);
                            } else {
                                $mensajeDescifrado = "[Error al descifrar el mensaje]";
                                chatLog("Error al descifrar el mensaje para la conversación con ID", $conversacion->id);
                            }
                        } else {
                            $mensajeDescifrado = "[Mensaje o IV faltante]";
                            chatLog("Error: Mensaje o IV faltante para la conversación con ID", $conversacion->id);
                        }
                    } else {
                        $mensajeDescifrado = "[No hay mensajes]";
                        chatLog("No se encontró ningún mensaje para la conversación con ID", $conversacion->id);
                    }

                    $fechaRelativa = $ultimoMensaje ? tiempoRelativo($ultimoMensaje->fecha) : "[Fecha desconocida]";
                ?>
                    <li class="mensaje">
                        <div class="imagenMensaje">
                            <img src="<?= esc_attr($imagenPerfil); ?>" alt="Imagen de perfil">
                        </div>
                        <div class="vistaPrevia">
                            <p><?= esc_html($mensajeDescifrado); ?></p>
                        </div>
                        <div class="tiempoMensaje">
                            <span><?= esc_html($fechaRelativa); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php
    } else {
    ?>
        <p>No tienes conversaciones activas.</p>
<?php
    }

    return ob_get_clean();  // Devolver el contenido del buffer de salida
}

function tiempoRelativo($fecha)
{
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;

    if ($diferencia < 60) {
        return 'hace unos segundos';
    } elseif ($diferencia < 3600) {
        $minutos = floor($diferencia / 60);
        return "hace $minutos minuto" . ($minutos > 1 ? 's' : '');
    } elseif ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        return "hace $horas hora" . ($horas > 1 ? 's' : '');
    } elseif ($diferencia < 604800) {
        $dias = floor($diferencia / 86400);
        return "hace $dias día" . ($dias > 1 ? 's' : '');
    } else {
        $semanas = floor($diferencia / 604800);
        return "hace $semanas semana" . ($semanas > 1 ? 's' : '');
    }
}
