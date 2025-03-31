<?

function obtenerNombreUsuario($usuarioId)
{
    $usuario = get_userdata($usuarioId);

    if ($usuario) {
        return !empty($usuario->display_name) ? $usuario->display_name : $usuario->user_login;
    }

    return 'Usuario desconocido';
}


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

    $imagenPerfil = imagenPerfil($receptor) ?: 'ruta_por_defecto.jpg';
    $nombreUsuario = obtenerNombreUsuario($receptor) ?: 'Usuario Desconocido';

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