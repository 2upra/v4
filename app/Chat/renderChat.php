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
    
    // Obtener los parámetros del POST (solo se espera 'conversacion' y 'page')
    $conversacion = isset($_POST['conversacion']) ? intval($_POST['conversacion']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $mensajesPorPagina = 10;

    // Verificar si el post tiene participantes en su meta
    $post_id = $conversacion; // El ID de la conversación es el mismo que el del post (tipo 'colab')
    $participantes = get_post_meta($post_id, 'participantes', true);

    if (empty($participantes)) {
        // Si no existe la meta de 'participantes', usar 'colabAutor' y 'colabColaborador'
        $colabAutor = get_post_meta($post_id, 'colabAutor', true);
        $colabColaborador = get_post_meta($post_id, 'colabColaborador', true);

        if (empty($colabAutor) || empty($colabColaborador)) {
            wp_send_json_error(array('message' => 'No se encontraron participantes para esta colaboración.'));
            wp_die();
        }

        $participantes = array($colabAutor, $colabColaborador);
    }

    // Asegurarse de que el usuario actual es uno de los participantes
    if (!in_array($usuarioActual, $participantes)) {
        wp_send_json_error(array('message' => 'El usuario actual no está autorizado para acceder a esta conversación.'));
        wp_die();
    }

    // Si la conversación no existe, buscar o crear una nueva
    if ($conversacion <= 0) {
        $tablaConversaciones = $wpdb->prefix . 'conversacion';
        $participantesJson = json_encode($participantes);

        // Buscar una conversación existente con el mismo post (id_conversacion) y tipo 2
        $conversacion = $wpdb->get_var($wpdb->prepare(
            "
            SELECT id 
            FROM $tablaConversaciones 
            WHERE tipo = 2
            AND conversacion_id = %d
            LIMIT 1
            ", $post_id
        ));

        // Si no existe, crear una nueva conversación
        if (!$conversacion) {
            $wpdb->insert($tablaConversaciones, array(
                'tipo' => 2,
                'participantes' => $participantesJson,
                'conversacion_id' => $post_id,
                'fecha_creacion' => current_time('mysql')
            ));

            $conversacion = $wpdb->insert_id;
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
