<?

function obtenerChatColab()
{
    chatLog('Iniciando obtenerChatColab...');

    if (!is_user_logged_in()) {
        chatLog('Usuario no autenticado.');
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    global $wpdb;
    $usuarioActual = get_current_user_id();
    chatLog('Usuario actual: ' . $usuarioActual);

    $colab_id = isset($_POST['colab_id']) ? intval($_POST['colab_id']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    chatLog('Colab ID: ' . $colab_id . ', Página: ' . $page);

    $mensajesPorPagina = 10;

    if ($colab_id <= 0) {
        chatLog('ID de colaboración inválido: ' . $colab_id);
        wp_send_json_error(array('message' => 'No se ha proporcionado un ID de colaboración válido.'));
        wp_die();
    }

    // Obtener participantes desde el metadato 'participantes' o crear uno nuevo
    $participantesMeta = get_post_meta($colab_id, 'participantes', true);
    if (!empty($participantesMeta)) {
        $participantes = json_decode($participantesMeta, true);
        chatLog('Participantes obtenidos de metadatos: ' . implode(', ', $participantes));
    } else {
        // Obtener participantes individuales
        $colabAutor = get_post_meta($colab_id, 'colabAutor', true);
        $colabColaborador = get_post_meta($colab_id, 'colabColaborador', true);
        chatLog('Autor: ' . $colabAutor . ', Colaborador: ' . $colabColaborador);

        if (!empty($colabAutor) && !empty($colabColaborador)) {
            $participantes = array($colabAutor, $colabColaborador);
            // Guardar participantes en un solo metadato en formato JSON
            update_post_meta($colab_id, 'participantes', json_encode($participantes));
            chatLog('Metadato "participantes" actualizado en el post.');
        } else {
            chatLog('No se encontraron participantes en los metadatos del post.');
            wp_send_json_error(array('message' => 'No se encontraron participantes para esta colaboración.'));
            wp_die();
        }
    }

    if (!in_array($usuarioActual, $participantes)) {
        chatLog('Usuario no autorizado para acceder a la conversación.');
        wp_send_json_error(array('message' => 'El usuario actual no está autorizado para acceder a esta conversación.'));
        wp_die();
    }

    // Obtener o crear la conversación
    $conversacion = get_post_meta($colab_id, 'conversacion_id', true);
    $tablaConversaciones = $wpdb->prefix . 'conversacion';

    if (empty($conversacion)) {
        chatLog('No existe conversación, creando una nueva.');
        $participantesJson = json_encode($participantes);

        $resultadoInsert = $wpdb->insert($tablaConversaciones, array(
            'tipo'         => 2,
            'participantes' => $participantesJson,
            'fecha'        => current_time('mysql'),
        ));

        if ($resultadoInsert === false) {
            chatLog('Error al crear la nueva conversación.');
            wp_send_json_error(array('message' => 'Error al crear la conversación.'));
            wp_die();
        }

        $conversacion = $wpdb->insert_id;
        chatLog('Nueva conversación creada con ID: ' . $conversacion);

        update_post_meta($colab_id, 'conversacion_id', $conversacion);
        chatLog('ID de conversación guardado en los metadatos del post.');
    } else {
        chatLog('Conversación existente con ID: ' . $conversacion);
    }

    // Verificar si la conversación existe en la base de datos
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

    $participantesExistentes = json_decode($conversacionData->participantes, true);

    if (!in_array($usuarioActual, $participantesExistentes)) {
        chatLog('El usuario no está autorizado en la conversación.');
        wp_send_json_error(array('message' => 'El usuario actual no está autorizado para acceder a esta conversación.'));
        wp_die();
    }

    // Actualizar metadato 'participantes' si es necesario
    $participantesActualizados = array_unique(array_merge($participantesExistentes, $participantes));
    if ($participantesExistentes !== $participantesActualizados) {
        $participantesJson = json_encode($participantesActualizados);
        $wpdb->update($tablaConversaciones, array(
            'participantes' => $participantesJson,
        ), array('id' => $conversacion));

        update_post_meta($colab_id, 'participantes', $participantesJson);
        chatLog('Participantes actualizados en la conversación y metadatos.');
    }

    // Obtener mensajes
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $offset = ($page - 1) * $mensajesPorPagina;
    chatLog('Consultando mensajes con offset: ' . $offset);

    $query = $wpdb->prepare("
        SELECT mensaje, emisor AS remitente, fecha, adjunto
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

    chatLog('Mensajes obtenidos: ' . count($mensajes));

    // Procesar mensajes
    $mensajes = array_reverse($mensajes);
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

    if ($conversacion <= 0 && $receptor <= 0) {
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

        if (!$conversacion) {
            wp_send_json_success(array('mensajes' => array(), 'conversacion' => null));
            wp_die();
        }
    }

    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $offset = ($page - 1) * $mensajesPorPagina;
    $query = $wpdb->prepare("
        SELECT mensaje, emisor AS remitente, fecha, adjunto
        FROM $tablaMensajes
        WHERE conversacion = %d
        ORDER BY fecha DESC
        LIMIT %d OFFSET %d
    ", $conversacion, $mensajesPorPagina, $offset);

    $mensajes = $wpdb->get_results($query);

    if ($mensajes === null) {
        wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
        wp_die();
    }

    $mensajes = array_reverse($mensajes);

    foreach ($mensajes as $mensaje) {
        $mensaje->clase = ($mensaje->remitente == $usuarioActual) ? 'mensajeDerecha' : 'mensajeIzquierda';

        if (!empty($mensaje->adjunto)) {
            $mensaje->adjunto = json_decode($mensaje->adjunto, true);
        }
    }

    wp_send_json_success(array(
        'mensajes' => $mensajes ? $mensajes : array(),
        'conversacion' => $conversacion
    ));
    wp_die();
}
add_action('wp_ajax_obtenerChat', 'obtenerChat');


/* en mi logica necesito que las ventanas al abrirse, tengan un data user id de quien corresponde chatWindow = document.querySelector(`.bloqueChat[data-user-id="${contactoId}"]`);
const contactoId = msgEmisor === currentUserId ? msgReceptor : msgEmisor;
console.log(`Buscando ventana de chat con data-user-id="${contactoId}"`);

ayudame a colocarlo, se puede desde aqui?

*/

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
                <button id="minizarChat"><?php echo $GLOBALS['minus']; ?></button>
                <button id="cerrarChat"><?php echo $GLOBALS['cancelicon']; ?></button>
            </div>
        </div>
        <ul class="listaMensajes"></ul>

        <div class="previewsForm NGEESM previewsChat" style="position: relative;">
            <!-- Vista previa de imagen -->
            <div class="previewAreaArchivos" id="previewChatImagen" style="display: none;">
                <label>Imagen</label>
            </div>
            <!-- Vista previa de audio -->
            <div class="previewAreaArchivos" id="previewChatAudio" style="display: none;">
                <label>Audio</label>
            </div>
            <!-- Vista previa de archivo -->
            <div class="previewAreaArchivos" id="previewChatArchivo" style="display: none;">
                <label>Archivo</label>
            </div>

            <!-- Botón de cancelar único, que aparecerá en cualquier vista previa -->
            <button class="cancelButton borde" id="cancelUploadButton" style="display: none;">Cancelar</button>
        </div>

        <div class="chatEnvio">
            <textarea class="mensajeContenido" rows="1"></textarea>
            <button class="enviarMensaje"><?php echo $GLOBALS['enviarMensaje']; ?></button>
            <button class="enviarAdjunto" id="enviarAdjunto"><?php echo $GLOBALS['enviarAdjunto']; ?></button>
        </div>
    </div>
    <?php
    $htmlGenerado = ob_get_clean();
    return $htmlGenerado;
}