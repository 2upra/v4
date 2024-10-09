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
            // Obtener el último mensaje de cada conversación incluyendo 'leido'
            $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                SELECT mensaje, fecha, emisor, COALESCE(leido, FALSE) AS leido
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
            return $fechaB - $fechaA; 
        });

        // Aplicar paginación después de ordenar
        $conversaciones = array_slice($conversaciones, $offset, $resultadosPorPagina);
    }

    return $conversaciones;
}

function renderListaChats($conversaciones, $usuarioId)
{
    ob_start();

    if ($conversaciones) {
    ?>
        <div class="bloqueConversaciones bloque" id="bloqueConversaciones-chatIcono" style="display: none;">
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
                    $leido = 0; // Valor por defecto

                    if ($conversacion->ultimoMensaje) {
                        if (!empty($conversacion->ultimoMensaje->mensaje)) {
                            $mensajeMostrado = ($conversacion->ultimoMensaje->emisor == $usuarioId ? "Tú: " : "") . $conversacion->ultimoMensaje->mensaje;
                        } else {
                            $mensajeMostrado = "[Mensaje faltante]";
                        }
                        $fechaOriginal = $conversacion->ultimoMensaje->fecha;
                        // Obtener el estado 'leido', asegurando que tenga un valor por defecto
                        $leido = isset($conversacion->ultimoMensaje->leido) ? (int)$conversacion->ultimoMensaje->leido : 0;
                    }
                ?>
                    <li class="mensaje <?php echo $leido ? 'leido' : 'no-leido'; ?>" 
                        data-receptor="<?= esc_attr($receptor); ?>" 
                        data-conversacion="<?= esc_attr($conversacion->id); ?>"
                        data-leido="<?= esc_attr($leido); ?>">
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
                        <?php if ($leido): ?>
                            <div class="iconoLeido">
                                ✓ 
                            </div>
                        <?php endif; ?>
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

