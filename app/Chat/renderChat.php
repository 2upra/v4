<?php


function obtenerChat()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    global $wpdb;
    
    $usuarioActual = get_current_user_id(); // Emisor
    $receptor = isset($_POST['receptor']) ? intval($_POST['receptor']) : 0;
    $conversacion = isset($_POST['conversacion']) ? intval($_POST['conversacion']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $mensajesPorPagina = 10;

    // Validar si tenemos el ID del receptor si no se ha pasado una conversación
    if ($conversacion <= 0 && $receptor <= 0) {
        wp_send_json_error(array('message' => 'ID de conversación o receptor inválido.'));
        wp_die();
    }

    // Si no hay ID de conversación, buscar una existente entre el emisor y receptor
    if ($conversacion <= 0) {
        $tablaConversaciones = $wpdb->prefix . 'conversacion'; // Tabla de conversaciones

        // Buscar una conversación de tipo 1 (uno a uno) con ambos participantes
        $conversacion = $wpdb->get_var($wpdb->prepare("
            SELECT id 
            FROM $tablaConversaciones 
            WHERE tipo = 1
            AND JSON_CONTAINS(participantes, %s)
            AND JSON_CONTAINS(participantes, %s)
            LIMIT 1
        ", 
        json_encode((string)$usuarioActual), 
        json_encode((string)$receptor)));

        // Si no existe conversación, devolver un resultado vacío
        if (!$conversacion) {
            wp_send_json_success(array('mensajes' => array()));
            wp_die();
        }
    }

    // Obtener los mensajes de la conversación
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $offset = ($page - 1) * $mensajesPorPagina;

    // Cambiar el orden a DESC para obtener los mensajes más recientes
    $query = $wpdb->prepare("
        SELECT mensaje, emisor AS remitente, fecha
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
    }

    if ($mensajes) {
        wp_send_json_success(array('mensajes' => $mensajes));
    } else {
        wp_send_json_success(array('mensajes' => array()));
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
