<?

function guardarVIsta() {
    // Verificar que se haya pasado el ID del post y las vistas del usuario
    if (isset($_POST['id_post']) && isset($_POST['vistas_usuario'])) {
        $idPost = intval($_POST['id_post']);
        $vistaUsuario = intval($_POST['vistas_usuario']);

        // Obtener la cantidad de vistas globales del post
        $vistaTotales = get_post_meta($idPost, 'vistas_totales', true);

        if (!$vistaTotales) {
            $vistaTotales = 0;
        }

        // Incrementar la cantidad de vistas totales
        $vistaTotales++;

        // Actualizar las vistas totales en las metas del post
        update_post_meta($idPost, 'vistas_totales', $vistaTotales);

        // Enviar una respuesta JSON
        wp_send_json_success(array(
            'vistas_usuario' => $vistaUsuario,
            'vistas_totales' => $vistaTotales
        ));
    }

    // Finalizar la ejecución
    wp_die();
}

// Registrar el handler de la acción AJAX
add_action('wp_ajax_guardar_vistas', 'guardarVIsta');
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVIsta');