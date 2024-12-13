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
/*
codigo js
async function establecerFiltros() {
    console.log('establecerFiltros: Inicio');
    try {
        const response = await enviarAjax('obtenerFiltrosTotal');
        console.log('establecerFiltros: Respuesta de obtenerFiltrosTotal', response);
        if (response.success) {
            let {filtroPost, filtroTiempo} = response.data;
            // Asegurarse de que filtroPost sea un objeto
            if (typeof filtroPost === 'string' && filtroPost.startsWith('s:')) {
                try {
                    // Eliminar el prefijo 's:' antes de intentar deserializar
                    filtroPost = PHPUnserialize.unserialize(filtroPost.substring(2));
                } catch (error) {
                    console.error('establecerFiltros: Error al deserializar filtroPost', error);
                    filtroPost = {};
                }
            } else if (typeof filtroPost === 'string') {
                // Intenta analizar como JSON solo si no es un string serializado
                try {
                    filtroPost = JSON.parse(filtroPost);
                } catch (error) {
                    console.error('establecerFiltros: Error al parsear filtroPost como JSON', error);
                    filtroPost = {};
                }
            }

            //Si filtroPost es un array, convertirlo a objeto
            if (Array.isArray(filtroPost)) {
                const tempObj = {};
                filtroPost.forEach(item => {
                    tempObj[item] = true; // Puedes asignar cualquier valor, por ejemplo, true
                });
                filtroPost = tempObj;
            }

            const hayFiltrosActivados = filtroTiempo !== 0 || Object.keys(filtroPost).length > 0;
            console.log('establecerFiltros: Hay filtros activados:', hayFiltrosActivados);
            const botonRestablecer = document.querySelector('.restablecerBusqueda');
            console.log('establecerFiltros: botonRestablecer:', botonRestablecer);
            const botonPostRestablecer = document.querySelector('.postRestablecer');
            console.log('establecerFiltros: botonPostRestablecer:', botonPostRestablecer);
            const botonColeccionRestablecer = document.querySelector('.coleccionRestablecer');
            console.log('establecerFiltros: botonColeccionRestablecer:', botonColeccionRestablecer);

            // Ocultar ambos botones por defecto
            if (botonPostRestablecer) {
                botonPostRestablecer.style.display = 'none';
                console.log('establecerFiltros: Ocultando botonPostRestablecer');
            }
            if (botonColeccionRestablecer) {
                botonColeccionRestablecer.style.display = 'none';
                console.log('establecerFiltros: Ocultando botonColeccionRestablecer');
            }

            if (hayFiltrosActivados) {
                console.log('establecerFiltros: Hay filtros activos, procesando...');

                const filtrosPost = ['misPost', 'mostrarMeGustan', 'ocultarEnColeccion', 'ocultarDescargados'];
                const hayFiltrosPost = Object.keys(filtroPost).some(filtro => filtrosPost.includes(filtro));
                console.log('establecerFiltros: hayFiltrosPost', hayFiltrosPost);
                const hayFiltroColeccion = filtroPost.hasOwnProperty('misColecciones');
                console.log('establecerFiltros: hayFiltroColeccion', hayFiltroColeccion);

                // Mostrar el botón correspondiente si es necesario
                if (hayFiltrosPost && botonPostRestablecer) {
                    botonPostRestablecer.style.display = 'block';
                    console.log('establecerFiltros: Mostrando botonPostRestablecer');
                }
                if (hayFiltroColeccion && botonColeccionRestablecer) {
                    botonColeccionRestablecer.style.display = 'block';
                    console.log('establecerFiltros: Mostrando botonColeccionRestablecer');
                }

                // Evento para restablecer filtros
                if (botonRestablecer && !botonRestablecer.dataset.listenerAdded) {
                    console.log('establecerFiltros: Agregando event listener a botonRestablecer');

                    // Función para restablecer filtros (se puede reutilizar)
                    const restablecerFiltro = async function (data) {
                        try {
                            console.log('establecerFiltros: Enviando solicitud para restablecer filtros', data);
                            const restablecerResponse = await enviarAjax('restablecerFiltros', data);
                            console.log('establecerFiltros: Respuesta de restablecerFiltros', restablecerResponse);
                            if (restablecerResponse.success) {
                                alert(restablecerResponse.data.message);
                                window.limpiarBusqueda(); // Llamar a limpiarBusqueda después del restablecimiento
                                if (botonPostRestablecer) {
                                    botonPostRestablecer.style.display = 'none';
                                    console.log('establecerFiltros: Ocultando botonPostRestablecer tras restablecer');
                                }
                                if (botonColeccionRestablecer) {
                                    botonColeccionRestablecer.style.display = 'none';
                                    console.log('establecerFiltros: Ocultando botonColeccionRestablecer tras restablecer');
                                }
                            } else {
                                alert('Error: ' + (restablecerResponse.data?.message || 'No se pudo restablecer'));
                            }
                        } catch (error) {
                            console.error('establecerFiltros: Error al restablecer:', error);
                            alert('Error en la solicitud.');
                        }
                    };

                    // Evento click para botón de post
                    if (botonPostRestablecer) {
                        botonPostRestablecer.addEventListener('click', async function () {
                            console.log('establecerFiltros: Evento click en botonPostRestablecer');
                            await restablecerFiltro({post: true});
                        });
                    }

                    // Evento click para botón de coleccion
                    if (botonColeccionRestablecer) {
                        botonColeccionRestablecer.addEventListener('click', async function () {
                            console.log('establecerFiltros: Evento click en botonColeccionRestablecer');
                            await restablecerFiltro({coleccion: true});
                        });
                    }

                    botonRestablecer.dataset.listenerAdded = true;
                    console.log('establecerFiltros: Listener agregado');
                }
            }
        } else {
            console.error('establecerFiltros: Error al obtener filtros:', response.data?.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('establecerFiltros: Error en AJAX:', error);
    }
    console.log('establecerFiltros: Fin');
}
*/

function obtenerFiltrosTotal()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }

    $user_id = get_current_user_id();
    $filtro_post = get_user_meta($user_id, 'filtroPost', true) ?: '{}'; // Valor predeterminado: JSON vacío
    $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true) ?: 0;

    // Si usas JSON directamente
    // No se necesita hacer nada más aquí, $filtro_post ya es un JSON válido o un JSON vacío '{}'

    // Si usas serialización (asegúrate de que se guarde correctamente serializado)
    /*
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
    */

    wp_send_json_success([
        'filtroPost' => $filtro_post, // $filtro_post ya es un JSON
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
