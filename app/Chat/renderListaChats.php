<?php


// Función para manejar la solicitud AJAX
add_action('wp_ajax_reiniciarChats', 'reiniciarChats');
function reiniciarChats()
{
    $usuarioId = get_current_user_id();
    $htmlConversaciones = conversacionesUsuario($usuarioId);
    wp_send_json_success(['html' => $htmlConversaciones]);
    exit;
}

function conversacionesUsuario($usuarioId)
{
    $conversaciones = obtenerChats($usuarioId);
    return renderListaChats($conversaciones, $usuarioId);
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
                SELECT mensaje, fecha, emisor 
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

function obtenerNombreUsuario($usuarioId)
{
    $usuario = get_userdata($usuarioId);
    return $usuario ? $usuario->display_name : '[Usuario desconocido]';
}



function infoUsuario() {
    // Guardamos un log al iniciar la función
    chatLog('Iniciando función infoUsuario.');

    // Verificamos si el usuario está autenticado
    if (!is_user_logged_in()) {
        chatLog('Error: Usuario no autenticado.');
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    // Obtenemos el ID del receptor desde la solicitud POST
    $receptor = isset($_POST['receptor']) ? intval($_POST['receptor']) : 0;
    chatLog('Receptor recibido: ' . $receptor);

    // Verificamos si hay un receptor válido
    if ($receptor <= 0) {
        chatLog('Error: ID del receptor inválido.');
        wp_send_json_error(array('message' => 'ID del receptor inválido.'));
        wp_die();
    }

    chatLog('Obteniendo imagen de perfil para receptor: ' . $receptor);
    $imagenPerfil = imagenPerfil($receptor) ?: 'ruta_por_defecto.jpg';
    chatLog('Imagen de perfil obtenida correctamente.');
    
    chatLog('Obteniendo nombre de usuario para receptor: ' . $receptor);
    $nombreUsuario = obtenerNombreUsuario($receptor) ?: 'Usuario Desconocido';
    chatLog('Nombre de usuario obtenido correctamente.');    

    // Guardamos los datos obtenidos en el log
    chatLog('Imagen de perfil obtenida: ' . $imagenPerfil);
    chatLog('Nombre de usuario obtenido: ' . $nombreUsuario);

    // Limpiar cualquier buffer de salida antes de enviar la respuesta
    if (ob_get_length()) {
        ob_end_clean();
    }

    
    wp_send_json_success(array(
        'imagenPerfil' => $imagenPerfil,
        'nombreUsuario' => $nombreUsuario
    ));

    chatLog('Respuesta enviada con éxito para el receptor ID: ' . $receptor);

    // Aseguramos que la ejecución termine correctamente
    wp_die();
}


add_action('wp_ajax_infoUsuario', 'infoUsuario');

function renderListaChats($conversaciones, $usuarioId)
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
                    $receptor = reset($otrosParticipantes);
                    $imagenPerfil = imagenPerfil($receptor);
                    $nombreUsuario = obtenerNombreUsuario($receptor);

                    $mensajeMostrado = "[No hay mensajes]";
                    $fechaOriginal = "";

                    if ($conversacion->ultimoMensaje) {
                        if (!empty($conversacion->ultimoMensaje->mensaje)) {
                            $mensajeMostrado = ($conversacion->ultimoMensaje->emisor == $usuarioId ? "Tú: " : "") . $conversacion->ultimoMensaje->mensaje;
                        } else {
                            $mensajeMostrado = "[Mensaje faltante]";
                        }
                        $fechaOriginal = $conversacion->ultimoMensaje->fecha;
                    }
                ?>
                    <li class="mensaje" data-receptor="<?= esc_attr($receptor); ?>" data-conversacion="<?= esc_attr($conversacion->id); ?>">
                        <div class="imagenMensaje">
                            <img src="<?= esc_url($imagenPerfil); ?>" alt="Imagen de perfil">
                        </div>
                        <div class="infoMensaje">
                            <div class="nombreUsuario">
                                <strong><?= esc_html($nombreUsuario); ?></strong>
                            </div>
                            <div class="vistaPrevia">
                                <p><?= esc_html($mensajeMostrado); ?></p>
                            </div>
                        </div>
                        <div class="tiempoMensaje" data-fecha="<?= esc_attr($fechaOriginal); ?>">
                            <span></span>
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
