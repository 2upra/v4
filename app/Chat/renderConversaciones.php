<?php


function conversacionesUsuario($usuarioId)
{
    $conversaciones = obtenerConversaciones($usuarioId);
    return renderConversaciones($conversaciones, $usuarioId);
}

function obtenerConversaciones($usuarioId)
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

    chatLog("Consulta de conversaciones ejecutada: " . $query);

    $conversaciones = $wpdb->get_results($query);

    if ($conversaciones) {
        chatLog("Conversaciones obtenidas: " . print_r($conversaciones, true));
        foreach ($conversaciones as &$conversacion) {
            $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                SELECT mensaje, fecha 
                FROM $tablaMensajes 
                WHERE conversacion = %d 
                ORDER BY fecha DESC
                LIMIT 1
            ", $conversacion->id));

            if ($ultimoMensaje) {
                // Verificar si el mensaje es más largo de 32 caracteres
                if (mb_strlen($ultimoMensaje->mensaje) > 32) {
                    // Cortar el mensaje a 32 caracteres y añadir "..."
                    $ultimoMensaje->mensaje = mb_substr($ultimoMensaje->mensaje, 0, 32) . '...';
                }

                $conversacion->ultimoMensaje = $ultimoMensaje;
            } else {
                $conversacion->ultimoMensaje = null;
            }
        }
    } else {
        chatLog("No se encontraron conversaciones para el usuario con ID: " . $usuarioId);
    }

    return $conversaciones;
}

function renderConversaciones($conversaciones, $usuarioId)
{
    ob_start();

    if ($conversaciones) {
?>
        <div class="modal modalConversaciones">
            <ul class="mensajes">
                <?php
                foreach ($conversaciones as $conversacion):
                    $participantes = json_decode($conversacion->participantes);
                    $otrosParticipantes = array_diff($participantes, [$usuarioId]);
                    $otroParticipanteId = reset($otrosParticipantes);
                    $imagenPerfil = imagenPerfil($otroParticipanteId);

                    $mensajeMostrado = "[No hay mensajes]";
                    $fechaRelativa = "[Fecha desconocida]";

                    // Obtener el último mensaje y su fecha
                    if ($conversacion->ultimoMensaje) {
                        if (!empty($conversacion->ultimoMensaje->mensaje)) {
                            // Mostrar mensaje tal cual (sin descifrar)
                            $mensajeMostrado = $conversacion->ultimoMensaje->mensaje;
                            chatLog("Mensaje mostrado: " . $mensajeMostrado);
                        } else {
                            $mensajeMostrado = "[Mensaje faltante]";
                            chatLog("Error: Mensaje faltante para la conversación con ID: " . $conversacion->id);
                        }
                        $fechaRelativa = tiempoRelativo($conversacion->ultimoMensaje->fecha);
                    }

                ?>
                    <li class="mensaje">
                        <div class="imagenMensaje">
                            <img src="<?= esc_url($imagenPerfil); ?>" alt="Imagen de perfil">
                        </div>
                        <div class="vistaPrevia">
                            <p><?= esc_html($mensajeMostrado); ?></p>
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

    $htmlGenerado = ob_get_clean();
    chatLog("HTML generado: " . $htmlGenerado);

    return $htmlGenerado;
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
