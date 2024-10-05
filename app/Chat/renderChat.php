<?

function obtenerChatColab()
{
    chatLog("Iniciando obtenerChatColab");

    // Verificar que el usuario está autenticado
    if (!is_user_logged_in()) {
        chatLog("Usuario no autenticado al intentar acceder.");
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    global $wpdb;
    $usuarioActual = get_current_user_id();
    chatLog("Usuario actual ID: " . $usuarioActual);

    // Obtener los parámetros del POST
    $conversacion = isset($_POST['conversacion']) ? intval($_POST['conversacion']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $mensajesPorPagina = 10;
    chatLog("Parametros POST - Conversacion: $conversacion, Page: $page");

    // Verificar si el post tiene participantes en su meta
    $post_id = $conversacion; // El ID de la conversación es el mismo que el del post (tipo 'colab')
    $participantes = get_post_meta($post_id, 'participantes', true);

    if (empty($participantes)) {
        chatLog("No se encontraron participantes en la meta. Revisando colabAutor y colabColaborador.");
        // Si no existe la meta de 'participantes', usar 'colabAutor' y 'colabColaborador'
        $colabAutor = get_post_meta($post_id, 'colabAutor', true);
        $colabColaborador = get_post_meta($post_id, 'colabColaborador', true);

        if (empty($colabAutor) || empty($colabColaborador)) {
            chatLog("No se encontraron colabAutor o colabColaborador.");
            wp_send_json_error(array('message' => 'No se encontraron participantes para esta colaboración.'));
            wp_die();
        }

        $participantes = array($colabAutor, $colabColaborador);
    }

    chatLog("Participantes: " . json_encode($participantes));

    // Asegurarse de que el usuario actual es uno de los participantes
    if (!in_array($usuarioActual, $participantes)) {
        chatLog("El usuario actual no está autorizado.");
        wp_send_json_error(array('message' => 'El usuario actual no está autorizado para acceder a esta conversación.'));
        wp_die();
    }

    // Si la conversación no existe, buscar o crear una nueva
    if ($conversacion <= 0) {
        chatLog("Buscando o creando una nueva conversación.");
        $tablaConversaciones = $wpdb->prefix . 'conversacion';
        $participantesJson = json_encode($participantes);

        // Buscar una conversación existente
        $conversacion = $wpdb->get_var($wpdb->prepare(
            "
            SELECT id 
            FROM $tablaConversaciones 
            WHERE tipo = 2
            AND conversacion_id = %d
            LIMIT 1
            ", $post_id
        ));

        if ($conversacion === null && $wpdb->last_error) {
            chatLog("Error en la consulta a la base de datos: " . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
            wp_die();
        }

        // Si no existe, crear una nueva conversación
        if (!$conversacion) {
            chatLog("Creando una nueva conversación.");
            $wpdb->insert($tablaConversaciones, array(
                'tipo' => 2,
                'participantes' => $participantesJson,
                'conversacion_id' => $post_id,
                'fecha_creacion' => current_time('mysql')
            ));

            if ($wpdb->last_error) {
                chatLog("Error al insertar en la base de datos: " . $wpdb->last_error);
                wp_send_json_error(array('message' => 'Error al crear la conversación.'));
                wp_die();
            }

            $conversacion = $wpdb->insert_id;
        }
    }

    chatLog("ID de la conversación: " . $conversacion);

    // Obtener los mensajes de la conversación
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

    if ($mensajes === null && $wpdb->last_error) {
        chatLog("Error en la consulta a la base de datos: " . $wpdb->last_error);
        wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
        wp_die();
    }

    chatLog("Mensajes obtenidos: " . json_encode($mensajes));

    // Formatear los mensajes y preparar para la respuesta
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

    chatLog("Respuesta enviada con éxito.");
    wp_die();
}
add_action('wp_ajax_obtenerChatColab', 'obtenerChatColab');;

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




function renderChat()
{
    ob_start();
?>
    <div class="bloque modal bloqueChat" id="bloqueChat" style="display: none;">
        <div class="infoChat">
            <div class="imagenMensaje">
                <img src="" alt="Imagen de perfil">
            </div>
            <div class="nombreConversacion">
                <p></p>
                <span class="estadoConexion">Desconectado</span>
            </div>
            <div class="botoneschat">
                <button id="minizarChat"><? echo $GLOBALS['minus']; ?></button>
                <button id="cerrarChat"><? echo $GLOBALS['cancelicon']; ?></button>
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
            <button class="enviarMensaje"><? echo $GLOBALS['enviarMensaje']; ?></button>
            <button class="enviarAdjunto" id="enviarAdjunto"><? echo $GLOBALS['enviarAdjunto']; ?></button>
        </div>
    </div>
<?
    $htmlGenerado = ob_get_clean();
    return $htmlGenerado;
}
