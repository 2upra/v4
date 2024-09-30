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

    // Cambiamos el orden a DESC para obtener los mensajes más recientes
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

    // Cambiamos el orden de los mensajes para que se muestren en el orden correcto
    $mensajes = array_reverse($mensajes);

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



function actualizarConexion() {
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        guardarLog("ID de usuario recibido: " . $user_id);  // Log del ID recibido
        
        $usuario = get_user_by('ID', $user_id);

        if ($usuario) {
            // Actualiza el estado de conexión del usuario
            update_user_meta($user_id, 'onlineStatus', 'conectado');
            update_user_meta($user_id, 'ultimaActividad', current_time('timestamp'));
            
            guardarLog("Estado del usuario {$user_id} actualizado a 'conectado'."); // Log de estado actualizado

            // Envía la respuesta de éxito en formato JSON
            wp_send_json_success('Usuario actualizado como conectado.');
        } else {
            guardarLog("Error: Usuario con ID {$user_id} no encontrado.");  // Log si no encuentra el usuario
            wp_send_json_error('Usuario no encontrado.');
        }
    } else {
        guardarLog("Error: No se proporcionó un ID de usuario.");  // Log de error por falta de ID
        wp_send_json_error('No se proporcionó un ID de usuario.');
    }
}

add_action('wp_ajax_actualizarConexion', 'actualizarConexion');








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
