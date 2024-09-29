<?php


function conversacionesUsuario($usuarioId)
{
    $conversaciones = obtenerChats($usuarioId);
    return renderchats($conversaciones, $usuarioId);
}

function obtenerChats($usuarioId)
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
                if (mb_strlen($ultimoMensaje->mensaje) > 32) {
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

function renderchats($conversaciones, $usuarioId)
{
    ob_start();

    if ($conversaciones) {
        ?>
        <div class="bloque bloqueConversaciones">
            <ul class="mensajes">
                <?php
                foreach ($conversaciones as $conversacion):
                    $participantes = json_decode($conversacion->participantes);
                    $otrosParticipantes = array_diff($participantes, [$usuarioId]);
                    $otroParticipanteId = reset($otrosParticipantes);
                    $imagenPerfil = imagenPerfil($otroParticipanteId);

                    $mensajeMostrado = "[No hay mensajes]";
                    $fechaRelativa = "";
                    if ($conversacion->ultimoMensaje) {
                        if (!empty($conversacion->ultimoMensaje->mensaje)) {
                            $mensajeMostrado = $conversacion->ultimoMensaje->mensaje;
                        } else {
                            $mensajeMostrado = "[Mensaje faltante]";
                        }
                        $fechaRelativa = tiempoRelativo($conversacion->ultimoMensaje->fecha);
                    }
                    ?>
                    <li class="mensaje" data-receptor="<?= esc_attr($otroParticipanteId); ?>" data-conversacion="<?= esc_attr($conversacion->id); ?>">
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
    return $htmlGenerado;
}
?>
<?php

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
