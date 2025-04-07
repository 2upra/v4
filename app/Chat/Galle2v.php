<?php

// Refactor(Org): Función guardarMensaje() movida a app/Services/ChatService.php

function procesarMensaje($request)
{
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
    if (!$emisor || !$mensaje) {
        chatLog('Error: Datos incompletos');
        return new WP_Error('datos_incompletos', 'Faltan datos requeridos', array('status' => 400));
    }

    // Verificar que exista al menos `receptor` o `conversacion_id`
    if (!$receptor && !$conversacion_id) {
        chatLog('Error: Falta receptor o conversacion_id');
        return new WP_Error('datos_incompletos', 'Debe proporcionar receptor o conversacion_id', array('status' => 400));
    }
    
    // Verificar si el emisor es el mismo que el usuario autenticado
    if ($emisor != $usuario_actual->ID) {
        chatLog('Error: El emisor no coincide con el usuario autenticado. Emisor: ' . $emisor . ', Usuario autenticado: ' . $usuario_actual->ID);
        return new WP_Error('emisor_no_autorizado', 'El emisor no coincide con el usuario autenticado', array('status' => 403));
    }

    chatLog('Intentando guardar mensaje');

    try {
        // Asumiendo que guardarMensaje está ahora disponible globalmente o se incluye desde ChatService.php
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

// La función guardarMensaje() ha sido movida a app/Services/ChatService.php
