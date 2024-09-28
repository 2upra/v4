<?php

define('CIPHER', 'AES-256-CBC');

function cifrarMensaje($mensaje, $clave, $iv)
{
    $cifrado = openssl_encrypt($mensaje, CIPHER, $clave, 0, $iv);
    return base64_encode($cifrado);
}

function descifrarMensaje($mensajeCifrado, $clave, $iv)
{
    $mensajeCifrado = base64_decode($mensajeCifrado);
    return openssl_decrypt($mensajeCifrado, CIPHER, $clave, 0, $iv);
}

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

    chatLog("Conversaciones obtenidas", $conversaciones);

    return renderConversaciones($conversaciones, $usuarioId);
}

function renderConversaciones($conversaciones, $usuarioId)
{
    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $clave = $_ENV['GALLEKEY'];

    ob_start();

    if ($conversaciones) {
?>
        <div class="modal modalConversaciones">
            <ul class="mensajes">
                <?php foreach ($conversaciones as $conversacion):
                    $participantes = json_decode($conversacion->participantes);
                    $otrosParticipantes = array_diff($participantes, [$usuarioId]);
                    $otroParticipanteId = reset($otrosParticipantes);
                    $imagenPerfil = imagenPerfil($otroParticipanteId);

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
                        $mensajeDescifrado = descifrarMensaje($ultimoMensaje->mensaje, $clave, $ultimoMensaje->iv);
                        chatLog("Mensaje descifrado", $mensajeDescifrado);
                    } else {
                        $mensajeDescifrado = "[No hay mensajes]";
                        chatLog("No se encontró ningún mensaje para la conversación con ID", $conversacion->id);
                    }

                    $fechaRelativa = tiempoRelativo($ultimoMensaje->fecha);
                ?>
                    <li class="mensaje">
                        <div class="imagenMensaje">
                            <img src="<?= $imagenPerfil; ?>">
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

    return ob_get_clean();
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
