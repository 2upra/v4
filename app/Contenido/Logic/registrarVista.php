<?

function guardarVista() {
    // Verificar que se haya pasado el ID del post
    if (isset($_POST['id_post'])) {
        $idPost = intval($_POST['id_post']);
        $userId = get_current_user_id(); // Obtener el ID del usuario actual

        if ($userId) {
            // Obtener las vistas del usuario almacenadas en su meta
            $vistasUsuario = get_user_meta($userId, 'vistas_posts', true);

            // Si no tiene registros anteriores, inicializar el array
            if (!$vistasUsuario) {
                $vistasUsuario = array();
            }

            // Incrementar las vistas del post para el usuario actual
            if (isset($vistasUsuario[$idPost])) {
                $vistasUsuario[$idPost]++;
            } else {
                $vistasUsuario[$idPost] = 1; // Primera vista del post por este usuario
            }

            // Guardar la información actualizada en la meta del usuario
            update_user_meta($userId, 'vistas_posts', $vistasUsuario);
        }

        // Obtener las vistas totales del post
        $vistaTotales = get_post_meta($idPost, 'vistas_totales', true);

        if (!$vistaTotales) {
            $vistaTotales = 0;
        }

        // Incrementar las vistas totales del post
        $vistaTotales++;

        // Guardar las vistas totales actualizadas en las post metas
        update_post_meta($idPost, 'vistas_totales', $vistaTotales);

        // Respuesta en formato JSON
        wp_send_json_success(array(
            'vistas_usuario' => $vistasUsuario[$idPost], // Vistas del usuario para este post
            'vistas_totales' => $vistaTotales // Vistas totales del post
        ));
    }

    // Finalizar la ejecución
    wp_die();
}

// Registrar el handler de la acción AJAX para usuarios logueados y no logueados
add_action('wp_ajax_guardar_vistas', 'guardarVista');
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista');
