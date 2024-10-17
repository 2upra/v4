<?



function obtenerChatColab()
{
    chatLog('Iniciando obtenerChatColab...');

    // Verificar si el usuario está autenticado
    if (!is_user_logged_in()) {
        chatLog('Usuario no autenticado.');
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    global $wpdb;
    $usuarioActual = get_current_user_id();
    chatLog('Usuario actual: ' . $usuarioActual);

    $mensajesPorPagina = 20;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;

    // Obtener el ID de la conversación proporcionado
    $conversacion = isset($_POST['conversacion_id']) ? intval($_POST['conversacion_id']) : null;

    if (!$conversacion) {
        chatLog('No se proporcionó un ID de conversación.');
        wp_send_json_error(array('message' => 'No se proporcionó un ID de conversación.'));
        wp_die();
    }

    $tablaConversaciones = $wpdb->prefix . 'conversacion';

    chatLog('Conversación ID proporcionado: ' . $conversacion);

    // Verificar si la conversación existe y obtener participantes
    $conversacionData = $wpdb->get_row($wpdb->prepare("
        SELECT participantes
        FROM $tablaConversaciones
        WHERE id = %d
    ", $conversacion));

    if (!$conversacionData) {
        chatLog('No se encontró la conversación en la base de datos.');
        wp_send_json_error(array('message' => 'No se encontró la conversación.'));
        wp_die();
    }

    $participantes = json_decode($conversacionData->participantes, true);

    if (!in_array($usuarioActual, $participantes)) {
        chatLog('El usuario no está autorizado en la conversación.');
        wp_send_json_error(array('message' => 'El usuario actual no está autorizado para acceder a esta conversación.'));
        wp_die();
    }

    // Obtener mensajes
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $offset = ($page - 1) * $mensajesPorPagina;
    chatLog('Consultando mensajes con offset: ' . $offset);

    $query = $wpdb->prepare("
        SELECT id, mensaje, emisor AS remitente, fecha, adjunto, metadata, leido
        FROM $tablaMensajes
        WHERE conversacion = %d
        ORDER BY fecha DESC
        LIMIT %d OFFSET %d
    ", $conversacion, $mensajesPorPagina, $offset);

    $mensajes = $wpdb->get_results($query);

    if ($mensajes === false) { // Cambiado de === null a === false para manejar errores de consulta
        chatLog('Error en la consulta a la base de datos.');
        wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
        wp_die();
    }

    chatLog('Mensajes obtenidos: ' . count($mensajes));

    // Procesar mensajes
    $mensajes = array_reverse($mensajes); // Para mostrar en orden ascendente
    foreach ($mensajes as $mensaje) {
        $mensaje->clase = ($mensaje->remitente == $usuarioActual) ? 'mensajeDerecha' : 'mensajeIzquierda';
        if (!empty($mensaje->adjunto)) {
            $mensaje->adjunto = json_decode($mensaje->adjunto, true);
        }
    }

    chatLog('Enviando respuesta con los mensajes.');
    wp_send_json_success(array(
        'mensajes'     => $mensajes,
        'conversacion' => $conversacion,
    ));
    wp_die();
}

add_action('wp_ajax_obtenerChatColab', 'obtenerChatColab');





function obtenerChat()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    global $wpdb;
    $usuarioActual = get_current_user_id();
    $receptor = isset($_POST['receptor']) ? intval($_POST['receptor']) : 0;
    $conversacion = isset($_POST['conversacion']) ? intval($_POST['conversacion']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $mensajesPorPagina = 10;

    // Registro de inicio de la función
    chatLog('----------obtener chat------------');
    
    // Registro de parámetros de entrada
    chatLog('Parámetros recibidos: ' . json_encode($_POST));
    chatLog('Usuario actual ID: ' . $usuarioActual);
    chatLog('Receptor ID: ' . $receptor);
    chatLog('Conversación ID proporcionado: ' . $conversacion);
    chatLog('Página solicitada: ' . $page);

    if ($conversacion <= 0 && $receptor <= 0) {
        chatLog('ID de conversación o receptor inválido.');
        wp_send_json_error(array('message' => 'ID de conversación o receptor inválido.'));
        wp_die();
    }

    if ($conversacion <= 0) {
        $tablaConversaciones = $wpdb->prefix . 'conversacion';
        $conversacion = $wpdb->get_var($wpdb->prepare(
            "
            SELECT id 
            FROM $tablaConversaciones 
            WHERE tipo = 1
              AND JSON_CONTAINS(participantes, %s)
              AND JSON_CONTAINS(participantes, %s)
            LIMIT 1
        ",
            json_encode($usuarioActual),
            json_encode($receptor)
        ));
        chatLog('Conversación obtenida desde la base de datos: ' . $conversacion);

        if (!$conversacion) {
            chatLog('No se encontró una conversación válida.');
            wp_send_json_success(array('mensajes' => array(), 'conversacion' => null));
            wp_die();
        }
    }

    // --- Inicio de la actualización de 'leido' ---

    $tablaMensajes = $wpdb->prefix . 'mensajes';

    // Construir la consulta SQL personalizada
    $sql = $wpdb->prepare(
        "UPDATE $tablaMensajes 
         SET leido = %d 
         WHERE conversacion = %d 
           AND remitente != %d 
           AND leido = %d",
        1,                  // leido = 1
        $conversacion,      // conversacion = 20
        $usuarioActual,     // remitente != 1
        0                   // leido = 0
    );

    // Registrar la consulta que se va a ejecutar
    chatLog('Consulta SQL para actualizar mensajes: ' . $sql);

    // Ejecutar la consulta
    $resultadoUpdate = $wpdb->query($sql);

    // Registrar el resultado de la actualización
    if ($resultadoUpdate === false) {
        chatLog('Error al actualizar los mensajes: ' . $wpdb->last_error);
    } else {
        chatLog('Número de mensajes actualizados a leido: ' . $resultadoUpdate);
    }

    // Obtener los mensajes con paginación
    $offset = ($page - 1) * $mensajesPorPagina;
    $query = $wpdb->prepare("
        SELECT mensaje, emisor AS remitente, fecha, adjunto, id, leido, metadata
        FROM $tablaMensajes
        WHERE conversacion = %d
        ORDER BY fecha DESC
        LIMIT %d OFFSET %d
    ", $conversacion, $mensajesPorPagina, $offset);

    // Registrar la consulta
    chatLog('Consulta de mensajes ejecutada: ' . $query);

    $mensajes = $wpdb->get_results($query);

    if ($mensajes === null) {
        chatLog('Error en la consulta a la base de datos: ' . $wpdb->last_error);
        wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
        wp_die();
    }

    chatLog('Número de mensajes obtenidos: ' . count($mensajes));

    $mensajes = array_reverse($mensajes);

    foreach ($mensajes as $mensaje) {
        $mensaje->clase = ($mensaje->remitente == $usuarioActual) ? 'mensajeDerecha' : 'mensajeIzquierda';

        if (!empty($mensaje->adjunto)) {
            $mensaje->adjunto = json_decode($mensaje->adjunto, true);
        }
    }

    // Registrar los mensajes formateados
    chatLog('Mensajes formateados: ' . json_encode($mensajes));

    $wp_response = array(
        'mensajes' => $mensajes ? $mensajes : array(),
        'conversacion' => $conversacion
    );

    // Registrar la respuesta enviada
    chatLog('Respuesta enviada al cliente: ' . json_encode($wp_response));

    wp_send_json_success($wp_response);
    wp_die();
}
add_action('wp_ajax_obtenerChat', 'obtenerChat');




function renderChat()
{
    ob_start();
    ?>
    <div class="bloque modal bloqueChat" id="bloqueChat" data-user-id="" style="display: none;">
        <div class="infoChat">
            <div class="imagenMensaje">
                <img src="" alt="Imagen de perfil">
            </div>
            <div class="nombreConversacion">
                <p></p>
                <span class="estadoConexion">Desconectado</span>
            </div>
            <div class="botoneschat">
                <button class="minizarChat" id="minizarChat"><?php echo $GLOBALS['minus']; ?></button>
                <button class="cerrarChat" id="cerrarChat"><?php echo $GLOBALS['cancelicon']; ?></button>
            </div>
        </div>
        <ul class="listaMensajes"></ul>

        <div class="previewsForm NGEESM previewsChat" style="position: relative;">
            <!-- Vista previa de imagen -->
            <div class="previewAreaArchivos previewChatImagen" id="previewChatImagen" style="display: none;">
                <label>Imagen</label>
            </div>
            <!-- Vista previa de audio -->
            <div class="previewAreaArchivos previewChatAudio" id="previewChatAudio" style="display: none;">
                <label>Audio</label>
            </div>
            <!-- Vista previa de archivo -->
            <div class="previewAreaArchivos previewChatArchivo" id="previewChatArchivo" style="display: none;">
                <label>Archivo</label>
            </div>

            <!-- Botón de cancelar único, que aparecerá en cualquier vista previa -->
            <button class="cancelButton borde cancelUploadButton" id="cancelUploadButton" style="display: none;">Cancelar</button>
        </div>

        <div class="chatEnvio individualSend">
            <textarea class="mensajeContenido" rows="1"></textarea>
            <button class="enviarMensaje"><?php echo $GLOBALS['enviarMensaje']; ?></button>
            <button class="enviarAdjunto" id="enviarAdjunto"><?php echo $GLOBALS['enviarAdjunto']; ?></button>
        </div>
    </div>
    <?php
    $htmlGenerado = ob_get_clean();
    return $htmlGenerado;
}