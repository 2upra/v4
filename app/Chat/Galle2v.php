<?


function procesarMensaje($request) {
    chatLog('Iniciando procesarMensaje');
    $usuario_actual = wp_get_current_user();
    if (!$usuario_actual->exists()) {
        $user_id = $request->get_header('X-User-ID');
        if ($user_id) {
            wp_set_current_user($user_id); // Forzar autenticación de usuario
            $usuario_actual = wp_get_current_user();
            if (!$usuario_actual->exists()) {
                chatLog('Error: Usuario no autenticado después de intentar forzar autenticación');
                return new WP_Error('usuario_no_autenticado', 'Usuario no autenticado', array('status' => 403));
            }
        } else {
            chatLog('Error: Usuario no autenticado y no se pudo obtener el ID de usuario');
            return new WP_Error('usuario_no_autenticado', 'Usuario no autenticado', array('status' => 403));
        }
    }

    $params = $request->get_json_params();
    chatLog('Parámetros recibidos: ' . json_encode($params));
    $emisor = isset($params['emisor']) ? $params['emisor'] : null;
    $receptor = isset($params['receptor']) ? $params['receptor'] : null;
    $mensaje = isset($params['mensaje']) ? $params['mensaje'] : null;
    $adjunto = isset($params['adjunto']) ? $params['adjunto'] : null;
    $metadata = isset($params['metadata']) ? $params['metadata'] : null;
    $conversacion_id = isset($params['conversacion_id']) ? $params['conversacion_id'] : null;

    // Verificar si los parámetros requeridos están presentes
    if (!$emisor || !$receptor || !$mensaje) {
        chatLog('Error: Datos incompletos');
        return new WP_Error('datos_incompletos', 'Faltan datos requeridos', array('status' => 400));
    }

    // Verificar si el emisor es el mismo que el usuario autenticado
    if ($emisor != $usuario_actual->ID) {
        chatLog('Error: El emisor no coincide con el usuario autenticado. Emisor: ' . $emisor . ', Usuario autenticado: ' . $usuario_actual->ID);
        return new WP_Error('emisor_no_autorizado', 'El emisor no coincide con el usuario autenticado', array('status' => 403));
    }

    chatLog('Intentando guardar mensaje');

    try {
        $resultado = guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata, $conversacion_id);

        if ($resultado) {
            chatLog('Mensaje guardado con éxito');
            return new WP_REST_Response(['success' => true], 200);
        } else {
            chatLog('Error: No se pudo guardar el mensaje');
            return new WP_Error('error_guardado', 'No se pudo guardar el mensaje', array('status' => 500));
        }
    } catch (Exception $e) {
        chatLog('Excepción al guardar mensaje: ' . $e->getMessage());
        return new WP_Error('error_interno', 'Se produjo un error interno', array('status' => 500));
    }
}

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



