<?php

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
    <div class="bloque bloqueChat" id="bloqueChat" style="display: none;">
        <div class="infoChat">
            <div class="imagenMensaje">
                <img src="" alt="Imagen de perfil">
            </div>
            <div class="nombreConversacion">
                <p></p>
                <span class="estadoConexion">Desconectado</span>
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
