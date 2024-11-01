<? 

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