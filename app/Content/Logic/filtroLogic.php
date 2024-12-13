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


/*
[13-Dec-2024 14:40:56 UTC] restablecerFiltros: Inicio
[13-Dec-2024 14:40:56 UTC] restablecerFiltros: User ID: 1
[13-Dec-2024 14:40:56 UTC] restablecerFiltros: filtroPost obtenido: a:2:{i:0;s:14:"misColecciones";i:1;s:18:"ocultarEnColeccion";}
[13-Dec-2024 14:40:56 UTC] restablecerFiltros: filtroPost unserialized/handled: Array
(
    [0] => misColecciones
    [1] => ocultarEnColeccion
)

[13-Dec-2024 14:40:56 UTC] restablecerFiltros: filtroPost_array no vacio, serializando: a:2:{i:0;s:14:"misColecciones";i:1;s:18:"ocultarEnColeccion";}
[13-Dec-2024 14:40:56 UTC] restablecerFiltros: filtroPost actualizado con: a:2:{i:0;s:14:"misColecciones";i:1;s:18:"ocultarEnColeccion";}
NO RESTABLECE LOS FILTROS!!! PORQUE?
*/

function restablecerFiltros() {
    error_log('restablecerFiltros: Inicio');
    error_log('restablecerFiltros: $_POST recibido: ' . print_r($_POST, true));


    if (!is_user_logged_in()) {
        error_log('restablecerFiltros: Usuario no autenticado');
        wp_send_json_error('Usuario no autenticado');
    }

    $user_id = get_current_user_id();
    error_log('restablecerFiltros: User ID: ' . $user_id);

    $filtroPost = get_user_meta($user_id, 'filtroPost', true);
    error_log('restablecerFiltros: filtroPost obtenido: ' . print_r($filtroPost, true));
    
    // Manejo de la variable $filtroPost y su conversion a array
    if (is_string($filtroPost)) {
      error_log('restablecerFiltros: filtroPost es string, intentando unserializar');
      $filtroPost_array = @unserialize($filtroPost);
        if ($filtroPost_array === false && $filtroPost !== 'b:0;') {
            error_log('restablecerFiltros: Error al unserializar filtroPost. valor:' . $filtroPost);
            $filtroPost_array = []; // Inicializar como array vacío para evitar errores posteriores
        } else{
            error_log('restablecerFiltros: unserializado exitoso: '. print_r($filtroPost_array, true));
        }
    } else if (is_array($filtroPost)) {
        $filtroPost_array = $filtroPost;
        error_log('restablecerFiltros: filtroPost es un array directamente');
    } else {
        $filtroPost_array = [];
        error_log('restablecerFiltros: filtroPost no es string ni array, inicializado como vacio');
    }
    
    error_log('restablecerFiltros: filtroPost despues de manejo: ' . print_r($filtroPost_array, true));


    // Procesamiento de filtros de post
    if (isset($_POST['post']) && $_POST['post'] === 'true') {
        error_log('restablecerFiltros: $_POST[post] es true');
        error_log('restablecerFiltros: Restablecer filtros de post');
        $filtros_a_eliminar = ['misPost', 'mostrarMeGustan', 'ocultarEnColeccion', 'ocultarDescargados'];
        
        if(is_array($filtroPost_array)){
          error_log('restablecerFiltros: filtroPost_array es array, procesando...');
            foreach ($filtros_a_eliminar as $filtro) {
              $index = array_search($filtro, $filtroPost_array);
              if ($index !== false) {
                  unset($filtroPost_array[$index]);
                  error_log('restablecerFiltros: Filtro "' . $filtro . '" eliminado. Indice: ' . $index);
              } else{
                  error_log('restablecerFiltros: Filtro "' . $filtro . '" no encontrado.');
              }
          }
        } else {
           error_log('restablecerFiltros: filtroPost_array no es array');
        }
    } else {
       if(isset($_POST['post'])){
         error_log('restablecerFiltros: $_POST[post] existe pero no es "true", valor: '. $_POST['post']);
       } else {
            error_log('restablecerFiltros: $_POST[post] no esta definido');
       }
    }

    // Procesamiento de filtros de coleccion
    if (isset($_POST['coleccion']) && $_POST['coleccion'] === 'true') {
      error_log('restablecerFiltros: $_POST[coleccion] es true');
      error_log('restablecerFiltros: Restablecer filtros de coleccion');
         if(is_array($filtroPost_array)){
             $index = array_search('misColecciones', $filtroPost_array);
            if ($index !== false) {
                unset($filtroPost_array[$index]);
                error_log('restablecerFiltros: Filtro "misColecciones" eliminado. Indice: ' . $index);
            } else {
                error_log('restablecerFiltros: Filtro "misColecciones" no encontrado.');
            }
         } else {
           error_log('restablecerFiltros: filtroPost_array no es array para coleccion');
        }
    } else {
          if(isset($_POST['coleccion'])){
         error_log('restablecerFiltros: $_POST[coleccion] existe pero no es "true", valor: '. $_POST['coleccion']);
       } else {
            error_log('restablecerFiltros: $_POST[coleccion] no esta definido');
       }
    }

    // Actualizacion o eliminacion de la meta data
    if (empty($filtroPost_array)) {
        delete_user_meta($user_id, 'filtroPost');
        error_log('restablecerFiltros: filtroPost_array vacio, eliminando meta filtroPost');
    } else {
       
      $serialized_filtroPost = serialize(array_values($filtroPost_array));
       error_log('restablecerFiltros: filtroPost_array no vacio, serializando: ' . print_r($serialized_filtroPost, true));
      update_user_meta($user_id, 'filtroPost', $serialized_filtroPost);
        error_log('restablecerFiltros: filtroPost actualizado con: ' .  print_r($serialized_filtroPost, true) );
    }

    // Eliminacion de filtro de tiempo
    delete_user_meta($user_id, 'filtroTiempo');
    error_log('restablecerFiltros: filtroTiempo eliminado');
    
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
    $filtro_post = get_user_meta($user_id, 'filtroPost', true) ?: 'a:0:{}';
    $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true) ?: 0;

    if (empty($filtro_post) || $filtro_post === 'a:0:{}') {
        $filtro_post_json = '{}';
    } else {
        if (is_string($filtro_post) && preg_match('/^a:\d+:{/', $filtro_post)) {
            $unserialized = @unserialize($filtro_post);
            if ($unserialized === false) {
                error_log("Error al deserializar filtroPost para el usuario: " . $user_id);
                $filtro_post_json = '{}';
            } else {
                 $filtro_post_json = json_encode($unserialized);
            }
        } else {
             $filtro_post_json = '{}';
        }
    }

    wp_send_json_success([
        'filtroPost' => $filtro_post_json,
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
