<?php

function obtenerChat()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }
    $conversacion = isset($_POST['conversacion']) ? intval($_POST['conversacion']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $mensajesPorPagina = 10;
    if ($conversacion <= 0) {
        wp_send_json_error(array('message' => 'ID de conversación inválido.'));
        wp_die();
    }

    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $offset = ($page - 1) * $mensajesPorPagina;
    $usuarioActual = get_current_user_id();
    $query = $wpdb->prepare("
        SELECT mensaje, emisor AS remitente, fecha
        FROM $tablaMensajes 
        WHERE conversacion = %d
        ORDER BY fecha ASC
        LIMIT %d OFFSET %d
    ", $conversacion, $mensajesPorPagina, $offset);

    $mensajes = $wpdb->get_results($query);
    if ($mensajes === null) {
        wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
        wp_die();
    }
    foreach ($mensajes as $mensaje) {
        $mensaje->clase = ($mensaje->remitente == $usuarioActual) ? 'mensajeDerecha' : 'mensajeIzquierda';
    }

    if ($mensajes) {
        wp_send_json_success(array('mensajes' => $mensajes));
    } else {
        wp_send_json_error(array('message' => 'No se encontraron más mensajes.'));
    }

    wp_die();
}
add_action('wp_ajax_obtenerChat', 'obtenerChat');



function renderChat()
{
    ob_start();
?>
        <div class="bloque bloqueChat" style="display: none;">
            <div class="infoChat">
                <div>

                </div>
                <div>
                    
                </div>
            </div>
            <ul class="listaMensajes">
                aqui debe mostrar los mensajes 
            </ul>
            <div class="chatEnvio">
                <textarea class="mensajeContenido" rows="1"></textarea>
                <button class="enviarMensaje"><?php echo $GLOBALS['enviarMensaje']; ?></button>
            </div>
        </div>
<?php
    $htmlGenerado = ob_get_clean();
    return $htmlGenerado;
}
