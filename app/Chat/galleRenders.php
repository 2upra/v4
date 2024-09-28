<?php

define('CIPHER', 'AES-256-CBC');

// Función para cifrar un mensaje
function cifrarMensaje($mensaje, $clave, $iv)
{
    chatLog("Iniciando cifrado de mensaje");

    if (empty($mensaje) || empty($clave) || empty($iv)) {
        chatLog("Error: Mensaje, clave o IV vacíos");
        return false;
    }

    try {
        $cifrado = openssl_encrypt($mensaje, CIPHER, $clave, 0, $iv);
        if ($cifrado === false) {
            chatLog("Error en openssl_encrypt: " . openssl_error_string());
            return false;
        }

        $resultado = base64_encode($cifrado);
        chatLog("Mensaje cifrado exitosamente");
        return $resultado;
    } catch (Exception $e) {
        chatLog("Excepción durante el cifrado: " . $e->getMessage());
        return false;
    }
}

// Función para descifrar un mensaje
function descifrarMensaje($mensajeCifrado, $clave, $iv)
{
    chatLog("Iniciando descifrado de mensaje");

    if (empty($mensajeCifrado) || empty($clave) || empty($iv)) {
        chatLog("Error: Mensaje cifrado, clave o IV vacíos");
        return false;
    }

    try {
        $mensajeDecodificado = base64_decode($mensajeCifrado);
        if ($mensajeDecodificado === false) {
            chatLog("Error: No se pudo decodificar el mensaje base64");
            return false;
        }

        $mensajeDescifrado = openssl_decrypt($mensajeDecodificado, CIPHER, $clave, 0, $iv);
        if ($mensajeDescifrado === false) {
            chatLog("Error en openssl_decrypt: " . openssl_error_string());
            return false;
        }

        chatLog("Mensaje descifrado exitosamente");
        return $mensajeDescifrado;
    } catch (Exception $e) {
        chatLog("Excepción durante el descifrado: " . $e->getMessage());
        return false;
    }
}

function conversacionesUsuario($usuarioId)
{
    global $wpdb;
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $tablaMensajes = $wpdb->prefix . 'mensajes';

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
    $clave = $_ENV['GALLEKEY'];

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

                    $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                        SELECT mensaje, fecha, iv 
                        FROM $tablaMensajes 
                        WHERE conversacion = %d 
                        ORDER BY fecha DESC
                        LIMIT 1
                    ", $conversacion->id));

                    $mensajeDescifrado = "[No hay mensajes]";
                    $fechaRelativa = "[Fecha desconocida]";

                    if ($ultimoMensaje && !empty($ultimoMensaje->mensaje) && !empty($ultimoMensaje->iv)) {
                        $mensajeDescifrado = descifrarMensaje($ultimoMensaje->mensaje, $clave, $ultimoMensaje->iv);
                        $fechaRelativa = tiempoRelativo($ultimoMensaje->fecha);
                    }
                ?>
                    <li class="mensaje">
                        <div class="imagenMensaje">
                            <img src="<?= esc_url($imagenPerfil); ?>" alt="Imagen de perfil">
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
