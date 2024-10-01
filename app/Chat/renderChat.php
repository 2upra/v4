<?php



function obtenerChat()
{
    chatLog('Iniciando función obtenerChat.');

    if (!is_user_logged_in()) {
        chatLog('Error: Usuario no autenticado.');
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    global $wpdb;
    
    $usuarioActual = get_current_user_id();
    chatLog('Usuario actual ID: ' . $usuarioActual);

    $receptor = isset($_POST['receptor']) ? intval($_POST['receptor']) : 0;
    chatLog('Receptor ID recibido: ' . $receptor);

    $conversacion = isset($_POST['conversacion']) ? intval($_POST['conversacion']) : 0;
    chatLog('Conversación ID recibido: ' . $conversacion);

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    chatLog('Número de página recibido: ' . $page);

    $mensajesPorPagina = 10;

    if ($conversacion <= 0 && $receptor <= 0) {
        chatLog('Error: ID de conversación o receptor inválido.');
        wp_send_json_error(array('message' => 'ID de conversación o receptor inválido.'));
        wp_die();
    }

    if ($conversacion <= 0) {
        chatLog('No se envió ID de conversación, buscando existente.');

        $tablaConversaciones = $wpdb->prefix . 'conversacion';

        // Asegurarse de que los IDs se tratan como enteros
        $conversacion = $wpdb->get_var($wpdb->prepare("
            SELECT id 
            FROM $tablaConversaciones 
            WHERE tipo = 1
            AND JSON_CONTAINS(participantes, %s)
            AND JSON_CONTAINS(participantes, %s)
            LIMIT 1
        ", 
        json_encode($usuarioActual), // No convertir a string, mantenerlo como entero
        json_encode($receptor)));

        if (!$conversacion) {
            chatLog('No se encontró una conversación, enviando resultados vacíos.');
            wp_send_json_success(array('mensajes' => array()));
            wp_die();
        } else {
            chatLog('ID de conversación encontrada: ' . $conversacion);
        }
    }

    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $offset = ($page - 1) * $mensajesPorPagina;
    chatLog('Calculado offset de página: ' . $offset);

    $query = $wpdb->prepare("
        SELECT mensaje, emisor AS remitente, fecha
        FROM $tablaMensajes
        WHERE conversacion = %d
        ORDER BY fecha DESC
        LIMIT %d OFFSET %d
    ", $conversacion, $mensajesPorPagina, $offset);

    $mensajes = $wpdb->get_results($query);

    if ($mensajes === null) {
        chatLog('Error en la consulta a la base de datos.');
        wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
        wp_die();
    }

    chatLog('Mensajes obtenidos, invirtiendo orden para enviar.');

    $mensajes = array_reverse($mensajes);

    foreach ($mensajes as $mensaje) {
        $mensaje->clase = ($mensaje->remitente == $usuarioActual) ? 'mensajeDerecha' : 'mensajeIzquierda';
        chatLog('Mensaje procesado: ' . json_encode($mensaje));
    }

    if ($mensajes) {
        chatLog('Enviando mensajes obtenidos.');
        wp_send_json_success(array('mensajes' => $mensajes));
    } else {
        chatLog('No se encontraron mensajes, enviando lista vacía.');
        wp_send_json_success(array('mensajes' => array()));
    }

    chatLog('Finalizando función obtenerChat.');
    wp_die();
}
add_action('wp_ajax_obtenerChat', 'obtenerChat');


function renderChat()
{
    ob_start();
?>
    <div class="bloque bloqueChat" style="display: none;">
        <div class="infoChat">
            <div class="imagenMensaje">
                <img src="" alt="Imagen de perfil">
            </div>
            <div class="nombreConversacion">
                <p></p>
                <span class="estadoConexion">Desconectado</span>
            </div>
        </div>
        <ul class="listaMensajes">

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
