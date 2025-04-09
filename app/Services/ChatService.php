<?php

#ESTE ARCHIVO SE ESTA HACIENDO MUY GRANDE; HAY QUE ORGANIZAR MEJOR EL CODIGO

// Refactor: Función obtenerChats() movida desde app/Chat/renderListaChats.php
function obtenerChats($usuarioId, $pagina = 1, $resultadosPorPagina = 10)
{
    global $wpdb;
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $tablaMensajes = $wpdb->prefix . 'mensajes';

    // Calcular el offset para la paginación
    $offset = ($pagina - 1) * $resultadosPorPagina;

    // Obtener conversaciones que incluyan al usuario
    $query = $wpdb->prepare("
        SELECT id, participantes, fecha 
        FROM $tablaConversacion 
        WHERE JSON_CONTAINS(participantes, %s)
    ", json_encode($usuarioId));

    $conversaciones = $wpdb->get_results($query);

    if ($conversaciones) {
        foreach ($conversaciones as &$conversacion) {
            // Obtener el último mensaje de cada conversación incluyendo 'leido'
            $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                SELECT mensaje, fecha, emisor, COALESCE(leido, FALSE) AS leido
                FROM $tablaMensajes 
                WHERE conversacion = %d 
                ORDER BY fecha DESC
                LIMIT 1
            ", $conversacion->id));

            if ($ultimoMensaje) {
                // Limitar el mensaje a 32 caracteres
                if (mb_strlen($ultimoMensaje->mensaje) > 32) {
                    $ultimoMensaje->mensaje = mb_substr($ultimoMensaje->mensaje, 0, 32) . '...';
                }

                $conversacion->ultimoMensaje = $ultimoMensaje;
            } else {
                $conversacion->ultimoMensaje = null;
            }
        }

        // Ordenar las conversaciones por la fecha del último mensaje (descendente)
        usort($conversaciones, function ($a, $b) {
            $fechaA = isset($a->ultimoMensaje->fecha) ? strtotime($a->ultimoMensaje->fecha) : 0;
            $fechaB = isset($b->ultimoMensaje->fecha) ? strtotime($b->ultimoMensaje->fecha) : 0;
            return $fechaB - $fechaA;
        });

        // Aplicar paginación después de ordenar
        $conversaciones = array_slice($conversaciones, $offset, $resultadosPorPagina);
    }

    return $conversaciones;
}

// Refactor(Org): Funcion conversacionesUsuario() movida desde app/Chat/renderListaChats.php
function conversacionesUsuario($usuarioId)
{
    // Refactor: Función obtenerChats() movida a app/Services/ChatService.php
    // Asegúrate de que ChatService.php esté incluido o autocargado
    // si no lo está ya.
    // Se asume que la función obtenerChats() está disponible globalmente o vía autoload
    $conversaciones = obtenerChats($usuarioId);
    // Nota: renderListaChats() aún está en app/Chat/renderListaChats.php
    // Asegúrate de que ese archivo esté incluido o la función autocargada.
    return renderListaChats($conversaciones, $usuarioId);
}

// Refactor: Función infoUsuario() y su hook AJAX movidos desde app/Chat/auxiliares.php
function infoUsuario() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    $receptor = isset($_POST['receptor']) ? intval($_POST['receptor']) : 0;

    if ($receptor <= 0) {
        wp_send_json_error(array('message' => 'ID del receptor inválido.'));
        wp_die();
    }

    // Asumiendo que UserUtils.php está incluido globalmente o donde se necesite
    // Si no, se necesitaría: require_once __DIR__ . '/../Utils/UserUtils.php';
    // Nota: Se asume que las funciones imagenPerfil() y obtenerNombreUsuario() están disponibles globalmente o en UserUtils.php
    $imagenPerfil = function_exists('imagenPerfil') ? imagenPerfil($receptor) : 'ruta_por_defecto.jpg';
    $nombreUsuario = function_exists('obtenerNombreUsuario') ? obtenerNombreUsuario($receptor) : 'Usuario Desconocido';

    if (ob_get_length()) {
        ob_end_clean();
    }

    wp_send_json_success(array(
        'imagenPerfil' => $imagenPerfil,
        'nombreUsuario' => $nombreUsuario
    ));

    wp_die();
}

add_action('wp_ajax_infoUsuario', 'infoUsuario');

// Refactor: Función actualizarConexion() y su hook movidos desde app/Chat/verificarConexion.php
function actualizarConexion() {
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        //guardarLog("ID de usuario recibido: " . $user_id);  
        $usuario = get_user_by('ID', $user_id);
        if ($usuario) {
            update_user_meta($user_id, 'onlineStatus', 'conectado');
            update_user_meta($user_id, 'ultimaActividad', current_time('timestamp'));
            //guardarLog("Estado del usuario {$user_id} actualizado a 'conectado'."); 
            wp_send_json_success('Usuario actualizado como conectado.');
        } else {
            //guardarLog("Error: Usuario con ID {$user_id} no encontrado."); 
            wp_send_json_error('Usuario no encontrado.');
        }
    } else {
        //guardarLog("Error: No se proporcionó un ID de usuario."); 
        wp_send_json_error('No se proporcionó un ID de usuario.');
    }
}

add_action('wp_ajax_actualizarConexion', 'actualizarConexion');

// Refactor: Función verificarConexionReceptor() y su hook movidos desde app/Chat/verificarConexion.php
function verificarConexionReceptor() {
    if (isset($_POST['receptor_id'])) {
        $receptor_id = intval($_POST['receptor_id']);
        
        // Obtener la IP del cliente
        $ip_cliente = $_SERVER['REMOTE_ADDR'];

        // Guardar en el log la IP y el receptor_id
        //guardarLog("ID del receptor recibido: " . $receptor_id . " desde la IP: " . $ip_cliente);

        $usuario = get_user_by('ID', $receptor_id);
        if ($usuario) {
            $ultimaActividad = get_user_meta($receptor_id, 'ultimaActividad', true);
            $tiempoActual = current_time('timestamp');
            if ($tiempoActual - $ultimaActividad <= 180) {
                wp_send_json_success(['online' => true]);
            } else {
                wp_send_json_success(['online' => false]);
            }
        } else {
            wp_send_json_error('Receptor no encontrado.');
        }
    } else {
        wp_send_json_error('No se proporcionó un ID de receptor.');
    }
}

add_action('wp_ajax_verificarConexionReceptor', 'verificarConexionReceptor');

// Refactor(Org): Función guardarMensaje() movida desde app/Chat/Galle2v.php
function guardarMensaje($emisor, $receptor, $mensaje, $adjunto = null, $metadata = null, $conversacion_id = null)
{
    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $emisor = (int) $emisor;
    $receptor = (int) $receptor;

    // Iniciar la transacción
    $wpdb->query('START TRANSACTION');

    try {
        // Si se recibe una conversacion_id, utilizarla
        if ($conversacion_id) {
            $conversacionID = (int) $conversacion_id;
            chatLog("Usando la conversación existente con ID: $conversacionID");
        } else {
            // Buscar una conversación existente entre los participantes
            $query = $wpdb->prepare("
                SELECT id FROM $tablaConversacion
                WHERE tipo = 1
                AND JSON_CONTAINS(participantes, %s)
                AND JSON_CONTAINS(participantes, %s)
            ", json_encode($emisor), json_encode($receptor));

            $conversacionID = $wpdb->get_var($query);

            // Si no se encuentra, crear una nueva conversación
            if (!$conversacionID) {
                $participantes = json_encode([$emisor, $receptor], JSON_NUMERIC_CHECK);
                $wpdb->insert($tablaConversacion, [
                    'tipo' => 1,
                    'participantes' => $participantes,
                    'fecha' => current_time('mysql')
                ]);
                $conversacionID = $wpdb->insert_id;
                chatLog("Nueva conversación creada con ID: $conversacionID");
            } else {
                chatLog("Conversación existente encontrada con ID: $conversacionID");
            }
        }

        // Guardar el mensaje en la conversación obtenida o creada
        $resultado = $wpdb->insert($tablaMensajes, [
            'conversacion' => $conversacionID,
            'emisor' => $emisor,
            'mensaje' => $mensaje,
            'fecha' => current_time('mysql'),
            'adjunto' => isset($adjunto) ? json_encode($adjunto) : null,
            'metadata' => isset($metadata) ? json_encode($metadata) : null,
        ]);

        // Verificar si se guardó correctamente
        if ($resultado === false) {
            throw new Exception("Error al insertar el mensaje: " . $wpdb->last_error);
        }

        $mensajeID = $wpdb->insert_id;
        $wpdb->query('COMMIT');
        chatLog("Mensaje guardado con ID: $mensajeID en la conversación: $conversacionID");

        return $mensajeID;
    } catch (Exception $e) {
        // Si ocurre un error, hacer rollback de la transacción
        $wpdb->query('ROLLBACK');
        error_log($e->getMessage());
        chatLog("Error al guardar el mensaje: " . $e->getMessage());
        return false;
    }
}

// Refactor: Función reiniciarChats() y su hook AJAX movidos desde app/Chat/renderListaChats.php
// Función para manejar la solicitud AJAX
function reiniciarChats()
{
    $usuarioId = get_current_user_id();
    // Refactor: Se llama a la función obtenerChats desde ChatService (este mismo archivo)
    $conversaciones = obtenerChats($usuarioId);
    // Refactor: Se llama a la función renderListaChats desde renderListaChats.php
    // Asegúrate de que renderListaChats.php esté incluido o autocargado
    $htmlConversaciones = renderListaChats($conversaciones, $usuarioId);
    wp_send_json_success(['html' => $htmlConversaciones]);
    exit;
}
add_action('wp_ajax_reiniciarChats', 'reiniciarChats');

// Refactor(Org): Función obtenerChatColab() y su hook movidos desde app/Chat/renderChat.php
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

// Refactor(Org): Función obtenerChat() y su hook movidos desde app/Chat/renderChat.php
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
           AND emisor != %d 
           AND leido = %d",
        1,                  // leido = 1
        $conversacion,      // conversacion = 20
        $usuarioActual,     // emisor != 1
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
    //los mensajes tienen una columna conversacion
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
