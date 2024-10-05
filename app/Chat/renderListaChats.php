<?


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

function obtenerChats($usuarioId, $pagina = 1, $resultadosPorPagina = 10)
{
    global $wpdb;
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $tablaMensajes = $wpdb->prefix . 'mensajes';

    // Calcular el offset para la paginación
    $offset = ($pagina - 1) * $resultadosPorPagina;

    // Obtener conversaciones que incluyan al usuario
    $query = $wpdb->prepare("
        SELECT id, participantes, fecha 
        FROM $tablaConversacion 
        WHERE JSON_CONTAINS(participantes, %s)
    ", json_encode($usuarioId));

    $conversaciones = $wpdb->get_results($query);

    if ($conversaciones) {
        foreach ($conversaciones as &$conversacion) {
            // Obtener el último mensaje de cada conversación
            $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                SELECT mensaje, fecha, emisor 
                FROM $tablaMensajes 
                WHERE conversacion = %d 
                ORDER BY fecha DESC
                LIMIT 1
            ", $conversacion->id));

            if ($ultimoMensaje) {
                // Limitar el mensaje a 32 caracteres
                if (mb_strlen($ultimoMensaje->mensaje) > 32) {
                    $ultimoMensaje->mensaje = mb_substr($ultimoMensaje->mensaje, 0, 32) . '...';
                }

                $conversacion->ultimoMensaje = $ultimoMensaje;
            } else {
                $conversacion->ultimoMensaje = null;
            }
        }

        // Ordenar las conversaciones por la fecha del último mensaje (descendente)
        usort($conversaciones, function ($a, $b) {
            $fechaA = isset($a->ultimoMensaje->fecha) ? strtotime($a->ultimoMensaje->fecha) : 0;
            $fechaB = isset($b->ultimoMensaje->fecha) ? strtotime($b->ultimoMensaje->fecha) : 0;
            return $fechaB - $fechaA; // Orden descendente (más reciente primero)
        });

        // Aplicar paginación después de ordenar
        $conversaciones = array_slice($conversaciones, $offset, $resultadosPorPagina);
    }

    return $conversaciones;
}


function obtenerNombreUsuario($usuarioId)
{
    $usuario = get_userdata($usuarioId);

    if ($usuario) {
        return !empty($usuario->display_name) ? $usuario->display_name : $usuario->user_login;
    }

    return '[Usuario desconocido]';
}



function infoUsuario() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    $receptor = isset($_POST['receptor']) ? intval($_POST['receptor']) : 0;

    if ($receptor <= 0) {
        wp_send_json_error(array('message' => 'ID del receptor inválido.'));
        wp_die();
    }

    $imagenPerfil = imagenPerfil($receptor) ?: 'ruta_por_defecto.jpg';
    $nombreUsuario = obtenerNombreUsuario($receptor) ?: 'Usuario Desconocido';

    if (ob_get_length()) {
        ob_end_clean();
    }

    wp_send_json_success(array(
        'imagenPerfil' => $imagenPerfil,
        'nombreUsuario' => $nombreUsuario
    ));

    wp_die();
}

add_action('wp_ajax_infoUsuario', 'infoUsuario');

function renderListaChats($conversaciones, $usuarioId)
{
    ob_start();

    if ($conversaciones) {
?>
        <div class="bloqueConversaciones bloque" id="bloqueConversaciones-chatIcono" style="display: none;">
            <ul class="mensajes">
                <?
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
                <? endforeach; ?>
            </ul>
        </div>
    <?
    } else {
    ?>
        <p>No tienes conversaciones activas.</p>
<?
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
