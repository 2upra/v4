<?

function obtenerChatColab()
{
    // Verificar que el usuario está autenticado
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    global $wpdb;
    $usuarioActual = get_current_user_id();

    // Obtener los parámetros del POST (se espera 'colab_id' y 'page')
    $colab_id = isset($_POST['colab_id']) ? intval($_POST['colab_id']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $mensajesPorPagina = 10;

    if ($colab_id <= 0) {
        wp_send_json_error(array('message' => 'No se ha proporcionado un ID de colaboración válido.'));
        wp_die();
    }

    // Obtener participantes de la colaboración
    $colabAutor = get_post_meta($colab_id, 'colabAutor', true);
    $colabColaborador = get_post_meta($colab_id, 'colabColaborador', true);

    if (empty($colabAutor) || empty($colabColaborador)) {
        wp_send_json_error(array('message' => 'No se encontraron participantes para esta colaboración.'));
        wp_die();
    }

    $participantes = array($colabAutor, $colabColaborador);

    // Asegurarse de que el usuario actual es uno de los participantes
    if (!in_array($usuarioActual, $participantes)) {
        wp_send_json_error(array('message' => 'El usuario actual no está autorizado para acceder a esta conversación.'));
        wp_die();
    }

    // Intentar obtener el ID de la conversación desde los metadatos del post (colaboración)
    $conversacion = get_post_meta($colab_id, 'conversacion_id', true);

    $tablaConversaciones = $wpdb->prefix . 'conversacion';

    if (!$conversacion) {
        // Si no existe, crear una nueva conversación
        $participantesJson = json_encode($participantes);

        $wpdb->insert($tablaConversaciones, array(
            'tipo' => 2,
            'participantes' => $participantesJson,
            'fecha' => current_time('mysql')
        ));

        $conversacion = $wpdb->insert_id;

        // Guardar el ID de la conversación en los metadatos del post
        update_post_meta($colab_id, 'conversacion_id', $conversacion);
    } else {
        // Verificar que la conversación existe en la base de datos
        $conversacionData = $wpdb->get_row($wpdb->prepare("
            SELECT participantes
            FROM $tablaConversaciones
            WHERE id = %d
        ", $conversacion));

        if (!$conversacionData) {
            wp_send_json_error(array('message' => 'No se encontró la conversación.'));
            wp_die();
        }

        // Obtener los participantes y verificar que el usuario actual está entre ellos
        $participantesExistentes = json_decode($conversacionData->participantes, true);

        if (!in_array($usuarioActual, $participantesExistentes)) {
            wp_send_json_error(array('message' => 'El usuario actual no está autorizado para acceder a esta conversación.'));
            wp_die();
        }
    }

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

    if ($mensajes === null) {
        wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
        wp_die();
    }

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
