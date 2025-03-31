<?


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