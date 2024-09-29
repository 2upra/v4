<?php

/*

dice error desconocido 
galleV2.js?ver=2.0.1.1904606246:36  No se pudieron obtener los mensajes: Error desconocido al obtener los mensajes.


    function abrirConversacion() {
        document.querySelectorAll('.mensaje').forEach(item => {
            item.addEventListener('click', async () => {
                const conversacion = item.getAttribute('data-conversacion');
                currentPage = 1;
                const data = await enviarAjax('obtenerChat', {
                    conversacion: conversacion,
                    page: currentPage
                });

                if (data && data.success) {
                    const chatHtml = renderChat(data.mensajes, emisor);
                    const chatContainer = document.querySelector('.bloqueChat');
                    chatContainer.innerHTML = chatHtml;
                    chatContainer.style.display = 'block';
                } else {
                    console.error('No se pudieron obtener los mensajes:', data.message);
                }
            });
        });
    }

*/

function obtenerChat() {
    // Verifica si el usuario está autenticado
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    // Registrar log para comprobar si se está ejecutando la función
    chatLog("Iniciando obtenerChat");

    // Obtener valores POST
    $conversacion = isset($_POST['conversacion']) ? intval($_POST['conversacion']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $mensajesPorPagina = 20;

    // Registrar log con los datos recibidos
    chatLog("Datos recibidos - Conversación: $conversacion, Página: $page");

    // Validar ID de conversación
    if ($conversacion <= 0) {
        chatLog("ID de conversación inválido: $conversacion");
        wp_send_json_error(array('message' => 'ID de conversación inválido.'));
        wp_die();
    }

    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $offset = ($page - 1) * $mensajesPorPagina;

    // Registrar log con la información sobre la consulta que se va a ejecutar
    chatLog("Preparando consulta. Conversación: $conversacion, Mensajes por página: $mensajesPorPagina, Offset: $offset");

    // Preparar y ejecutar la consulta
    $query = $wpdb->prepare("
        SELECT mensaje, remitente, fecha
        FROM $tablaMensajes 
        WHERE conversacion = %d
        ORDER BY fecha ASC
        LIMIT %d OFFSET %d
    ", $conversacion, $mensajesPorPagina, $offset);

    // Registrar la consulta ejecutada para depurar
    chatLog("Consulta SQL: $query");

    $mensajes = $wpdb->get_results($query);

    // Comprobar si la consulta devolvió un error o no se recuperaron mensajes
    if ($mensajes === null) {
        chatLog("Error en la consulta a la base de datos.");
        wp_send_json_error(array('message' => 'Error en la consulta a la base de datos.'));
        wp_die();
    }

    // Registrar el número de mensajes encontrados
    $numMensajes = count($mensajes);
    chatLog("Número de mensajes encontrados: $numMensajes");

    // Comprobar si hay mensajes
    if ($mensajes) {
        // Registrar log para indicar que se devuelve una respuesta exitosa
        chatLog("Mensajes devueltos correctamente.");
        wp_send_json_success(array('mensajes' => $mensajes));
    } else {
        // Registrar log si no se encontraron mensajes
        chatLog("No se encontraron más mensajes.");
        wp_send_json_error(array('message' => 'No se encontraron más mensajes.'));
    }

    wp_die();
}
add_action('wp_ajax_obtenerChat', 'obtenerChat');


/*
function renderChat($mensajes, $usuarioId)
{
    ob_start();

    if ($mensajes) {
?>
        <div class="bloque bloqueChat" style="display: none;">
            <ul class="listaMensajes">
                <?php
                foreach ($mensajes as $mensaje):
                    $esRemitente = ($mensaje->remitente == $usuarioId); 
                    $claseMensaje = $esRemitente ? 'mensajeDerecha' : 'mensajeIzquierda';
                    $imagenPerfil = !$esRemitente ? imagenPerfil($mensaje->remitente) : null;
                    $fechaRelativa = tiempoRelativo($mensaje->fecha);
                ?>
                    <li class="mensaje <?= esc_attr($claseMensaje); ?>">
                        <?php if (!$esRemitente): ?>
                            <div class="imagenMensaje">
                                <img src="<?= esc_url($imagenPerfil); ?>" alt="Imagen de perfil">
                            </div>
                        <?php endif; ?>
                        <div class="contenidoMensaje">
                            <p><?= esc_html($mensaje->mensaje); ?></p>
                            <span class="fechaMensaje"><?= esc_html($fechaRelativa); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div>
                <textarea class="mensajeContenido"></textarea>
                <button class="enviarMensaje"></button>
            </div>
        </div>
    <?php
    } else {
    ?>
        <p>No hay mensajes en esta conversación.</p>
<?php
    }

    $htmlGenerado = ob_get_clean();
    return $htmlGenerado;
}
    */