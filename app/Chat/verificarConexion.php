<?php // Refactor: Función actualizarConexion() y su hook movidos a app/Services/ChatService.php

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
