<?
//saber el filtro tiempo
function obtenerFiltroActual() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Usuario no autenticado']);
        return;
    }

    $user_id = get_current_user_id();
    $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true);
    
    if ($filtro_tiempo === '') {
        $filtro_tiempo = 0;
    }

    // Array de nombres de filtros
    $nombres_filtros = array(
        0 => 'Feed',
        1 => 'Reciente',
        2 => 'Semanal',
        3 => 'Mensual'
    );

    $filtro_tiempo = intval($filtro_tiempo);
    $nombre_filtro = isset($nombres_filtros[$filtro_tiempo]) ? $nombres_filtros[$filtro_tiempo] : 'Feed';

    wp_send_json_success([
        'filtroTiempo' => $filtro_tiempo,
        'nombreFiltro' => $nombre_filtro
    ]);
}
add_action('wp_ajax_obtenerFiltroActual', 'obtenerFiltroActual');

function restablecerFiltros()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }

    $user_id = get_current_user_id();
    $resultado_post = delete_user_meta($user_id, 'filtroPost');
    $resultado_tiempo = delete_user_meta($user_id, 'filtroTiempo');
    if ($resultado_post && $resultado_tiempo) {
        wp_send_json_success(['message' => 'Filtros restablecidos correctamente']);
    } else {
        wp_send_json_error('Error al restablecer los filtros');
    }
}
add_action('wp_ajax_restablecerFiltros', 'restablecerFiltros');

// Función para obtener los filtros del usuario
function obtenerFiltrosTotal() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }

    $user_id = get_current_user_id();
    $filtro_post = get_user_meta($user_id, 'filtroPost', true);
    $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true);

    wp_send_json_success([
        'filtroPost' => $filtro_post ? $filtro_post : 'a:0:{}', // Valor por defecto si no existe
        'filtroTiempo' => $filtro_tiempo ? $filtro_tiempo : 0,   // Valor por defecto si no existe
    ]);
}
add_action('wp_ajax_obtenerFiltrosTotal', 'obtenerFiltrosTotal');

//Filtro usuario
function guardarFiltroPost()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }
    $filtros = json_decode(stripslashes($_POST['filtros']), true);
    $user_id = get_current_user_id();
    $actualizado = update_user_meta($user_id, 'filtroPost', $filtros);
    if ($actualizado) {
        wp_send_json_success(['message' => 'Filtros guardados correctamente']);
    } else {
        wp_send_json_error('Error al guardar los filtros');
    }
}
add_action('wp_ajax_guardarFiltroPost', 'guardarFiltroPost');

function obtenerFiltros()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }

    $user_id = get_current_user_id();
    $filtros = get_user_meta($user_id, 'filtroPost', true);

    if ($filtros === '') {
        $filtros = [];
    }

    wp_send_json_success(['filtros' => $filtros]);
}
add_action('wp_ajax_obtenerFiltros', 'obtenerFiltros');

//Para tiempo
function guardarFiltro()
{
    error_log('Iniciando guardarFiltro');

    if (!is_user_logged_in()) {
        error_log('Usuario no autenticado');
        wp_send_json_error(['message' => 'Usuario no autenticado']);
        return;
    }

    if (!isset($_POST['filtroTiempo'])) {
        error_log('filtroTiempo no especificado');
        wp_send_json_error(['message' => 'Valor de filtroTiempo no especificado']);
        return;
    }

    $user_id = get_current_user_id();
    $filtro_tiempo = intval($_POST['filtroTiempo']);

    error_log('Guardando filtroTiempo: ' . $filtro_tiempo . ' para usuario: ' . $user_id);

    $resultado = update_user_meta($user_id, 'filtroTiempo', $filtro_tiempo);

    if ($resultado === false) {
        error_log('Error al guardar el filtro');
        wp_send_json_error(['message' => 'Error al guardar el filtro']);
        return;
    }

    error_log('Filtro guardado correctamente');
    wp_send_json_success([
        'message' => 'Filtro guardado correctamente',
        'filtroTiempo' => $filtro_tiempo,
        'userId' => $user_id
    ]);
}
add_action('wp_ajax_guardarFiltro', 'guardarFiltro');


