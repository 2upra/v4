<?php

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

    $conversaciones = $wpdb->get_results($query);

    return renderConversaciones($conversaciones, $usuarioId);
}

function renderConversaciones($conversaciones, $usuarioId)
{
    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';

    ob_start();

    if ($conversaciones) {
        ?>
        <div class="modal modalConversaciones">
            <ul class="mensajes">
                <?php foreach ($conversaciones as $conversacion):
                    $participantes = json_decode($conversacion->participantes);
                    // Obtener al otro participante (asumiendo conversación uno a uno)
                    $otrosParticipantes = array_diff($participantes, [$usuarioId]);
                    $otroParticipanteId = reset($otrosParticipantes); // Primer participante que no es el usuario actual

                    // Obtener la imagen de perfil del otro participante
                    $imagenPerfil = imagenPerfil($otroParticipanteId);

                    // Obtener el último mensaje de la conversación
                    $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                        SELECT mensaje, fecha 
                        FROM $tablaMensajes 
                        WHERE conversacion = %d 
                        ORDER BY fecha DESC
                        LIMIT 1
                    ", $conversacion->id));

                    // Formatear la fecha a un formato relativo
                    $fechaRelativa = tiempoRelativo($ultimoMensaje->fecha);
                ?>
                    <li class="mensaje">
                        <div class="imagenMensaje">
                            <?= $imagenPerfil; ?>
                        </div>
                        <div class="vistaPrevia">
                            <p><?= esc_html($ultimoMensaje->mensaje); ?></p>
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

    return ob_get_clean();
}

// Función para calcular el tiempo relativo (ej: "hace 3 horas", "hace 2 días")
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