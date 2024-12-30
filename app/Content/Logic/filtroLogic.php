<?
//saber el filtro tiempo

function obtenerFiltroActual()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Usuario no autenticado']);
        return;
    }

    $user_id = get_current_user_id();
    $filtro_tiempo = intval(get_user_meta($user_id, 'filtroTiempo', true) ?: 0);
    $nombres_filtros = ['Feed', 'Reciente', 'Semanal', 'Mensual'];
    $nombre_filtro = $nombres_filtros[$filtro_tiempo] ?? 'Feed';

    wp_send_json_success([
        'filtroTiempo' => $filtro_tiempo,
        'nombreFiltro' => $nombre_filtro
    ]);
}
add_action('wp_ajax_obtenerFiltroActual', 'obtenerFiltroActual');

// ASI FUNCIONA CORRECTAMENTE a:1:{i:0;s:15:"mostrarMeGustan";} PERO CUANDO SE RESTABLECE ALGO SE GUARDA ASI s:33:"a:1:{i:1;s:15:"mostrarMeGustan";}";, cosa que hace que evidenmente falla, arreglalo

function restablecerFiltros()
{
    error_log('restablecerFiltros: Inicio');
    error_log('restablecerFiltros: $_POST recibido: ' . print_r($_POST, true));

    // Validar si el usuario está autenticado
    if (!is_user_logged_in()) {
        error_log('restablecerFiltros: Usuario no autenticado');
        wp_send_json_error('Usuario no autenticado');
    }

    // Obtener el ID del usuario actual
    $user_id = get_current_user_id();
    error_log('restablecerFiltros: User ID: ' . $user_id);

    // Obtener el valor actual de filtroPost
    $filtroPost = get_user_meta($user_id, 'filtroPost', true);
    error_log('restablecerFiltros: filtroPost obtenido: ' . print_r($filtroPost, true));

    // Asegurarse de que filtroPost sea un array válido
    if (is_string($filtroPost)) {
        error_log('restablecerFiltros: filtroPost es string, intentando unserializar');
        $filtroPost_array = @unserialize($filtroPost);
        if ($filtroPost_array === false && $filtroPost !== 'b:0;') {
            error_log('restablecerFiltros: Error al unserializar filtroPost. Valor: ' . $filtroPost);
            $filtroPost_array = [];
        } else {
            error_log('restablecerFiltros: unserializado exitoso: ' . print_r($filtroPost_array, true));
        }
    } elseif (is_array($filtroPost)) {
        $filtroPost_array = $filtroPost;
        error_log('restablecerFiltros: filtroPost ya es un array');
    } else {
        $filtroPost_array = [];
        error_log('restablecerFiltros: filtroPost no es string ni array, inicializado como vacío');
    }

    error_log('restablecerFiltros: filtroPost después de manejo: ' . print_r($filtroPost_array, true));

    // Procesar el filtro de "post"
    if (isset($_POST['post']) && $_POST['post'] === 'true') {
        error_log('restablecerFiltros: $_POST[post] es true');
        $filtros_a_eliminar = ['misPost', 'mostrarMeGustan', 'ocultarEnColeccion', 'ocultarDescargados'];

        if (is_array($filtroPost_array)) {
            $filtroPost_array = array_values(array_filter($filtroPost_array, function ($filtro) use ($filtros_a_eliminar) {
                return !in_array($filtro, $filtros_a_eliminar);
            }));
        }
    }

    // Procesar el filtro de "coleccion"
    if (isset($_POST['coleccion']) && $_POST['coleccion'] === 'true') {
        error_log('restablecerFiltros: $_POST[coleccion] es true');
        if (is_array($filtroPost_array)) {
            $filtroPost_array = array_values(array_filter($filtroPost_array, function ($filtro) {
                return $filtro !== 'misColecciones';
            }));
        }
    }

    // Guardar o eliminar la meta de usuario según el contenido del array
    if (empty($filtroPost_array)) {
        delete_user_meta($user_id, 'filtroPost');
        error_log('restablecerFiltros: filtroPost_array vacío, eliminando meta filtroPost');
    } else {
        // Guardar el array directamente, sin serializar
        update_user_meta($user_id, 'filtroPost', $filtroPost_array);
        error_log('restablecerFiltros: filtroPost_array no vacío, actualizado: ' . print_r($filtroPost_array, true));
    }

    // Eliminar el filtro de tiempo
    delete_user_meta($user_id, 'filtroTiempo');
    error_log('restablecerFiltros: filtroTiempo eliminado');

    // Respuesta de éxito
    wp_send_json_success(['message' => 'Filtros restablecidos']);
    error_log('restablecerFiltros: Fin');
}
add_action('wp_ajax_restablecerFiltros', 'restablecerFiltros');


function obtenerFiltrosTotal()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }

    $user_id = get_current_user_id();
    $filtro_post = get_user_meta($user_id, 'filtroPost', true) ?: '{}'; 
    $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true) ?: 0;

    wp_send_json_success([
        'filtroPost' => $filtro_post,
        'filtroTiempo' => $filtro_tiempo,
    ]);
}
add_action('wp_ajax_obtenerFiltrosTotal', 'obtenerFiltrosTotal');

function guardarFiltroPost()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }
    $filtros = json_decode(stripslashes($_POST['filtros']), true);
    $user_id = get_current_user_id();
    if (update_user_meta($user_id, 'filtroPost', $filtros)) {
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
    $filtros = get_user_meta($user_id, 'filtroPost', true) ?: [];

    wp_send_json_success(['filtros' => $filtros]);
}
add_action('wp_ajax_obtenerFiltros', 'obtenerFiltros');


function guardarFiltro()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Usuario no autenticado']);
        return;
    }

    if (!isset($_POST['filtroTiempo'])) {
        wp_send_json_error(['message' => 'Valor de filtroTiempo no especificado']);
        return;
    }

    $user_id = get_current_user_id();
    $filtro_tiempo = intval($_POST['filtroTiempo']);

    if (update_user_meta($user_id, 'filtroTiempo', $filtro_tiempo)) {
        wp_send_json_success([
            'message' => 'Filtro guardado correctamente',
            'filtroTiempo' => $filtro_tiempo,
            'userId' => $user_id
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al guardar el filtro']);
    }
}
add_action('wp_ajax_guardarFiltro', 'guardarFiltro');
