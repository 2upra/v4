<?php

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
