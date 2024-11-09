<?

/*
ahora los filtros nuevo deben ser mas dinamicos y aplicarse independiente de la condicion 

El usuario tiene las opciones nueva de: (por defecto desactivado)
Ocultar ya descargadas 
Ocultar guardados en coleccion 
Mostrar solo los que me gustan

el usuario tendra una meta llamada FiltroPost que sera un array que indique cuales filtros estan encendidos: 
[ocultarDescargados, ocultarEnColeccion, mostrarMeGustan]

para saber la informacion necesarias, 

asi se guardan las descargas (en la meta del usuario)
    $descargasAnteriores = get_user_meta($userID, 'descargas', true);
    if (!$descargasAnteriores) {
        $descargasAnteriores = [];
    }
    $yaDescargado = isset($descargasAnteriores[$postID]);
    if (!$yaDescargado) {
        $pinky = (int)get_user_meta($userID, 'pinky', true);
        if ($pinky < 1) {
            wp_send_json_error(['message' => 'No tienes suficientes Pinkys para esta descarga.']);
        }
        restarPinkys($userID, 1);
    }
    if (!$yaDescargado) {
        $descargasAnteriores[$postID] = 1;
    } else {
        $descargasAnteriores[$postID]++;
    }
    update_user_meta($userID, 'descargas', $descargasAnteriores);

asi se guarda los samples en coleccioens
$samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true);
if (!is_array($samplesGuardados)) {
    $samplesGuardados = array();
}
if (!isset($samplesGuardados[$sample_id])) {
    $samplesGuardados[$sample_id] = [];
}
$samplesGuardados[$sample_id][] = $collection_id;
update_user_meta($user_id, 'samplesGuardados', $samplesGuardados);


*/




//Para tiempo
function guardarFiltro() {

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
    update_user_meta($user_id, 'filtroTiempo', $filtro_tiempo);
    wp_send_json_success(['message' => 'Filtro guardado correctamente']);
}
add_action('wp_ajax_guardarFiltro', 'guardarFiltro');

function aplicarFiltros($query_args, $args, $user_id, $current_user_id)
{
    // Obtener los filtros personalizados del usuario
    $filtrosUsuario = get_user_meta($current_user_id, 'FiltroPost', true);
    
    // Aplicar filtros según la configuración del usuario en 'FiltroPost'
    if (!empty($filtrosUsuario)) {
        // Filtrar publicaciones ya descargadas
        if (in_array('ocultarDescargados', $filtrosUsuario)) {
            $descargasAnteriores = get_user_meta($current_user_id, 'descargas', true) ?: [];
            if (!empty($descargasAnteriores)) {
                $query_args['post__not_in'] = array_merge(
                    $query_args['post__not_in'] ?? [], 
                    array_keys($descargasAnteriores)
                );
            }
        }

        // Filtrar publicaciones guardadas en colección
        if (in_array('ocultarEnColeccion', $filtrosUsuario)) {
            $samplesGuardados = get_user_meta($current_user_id, 'samplesGuardados', true) ?: [];
            if (!empty($samplesGuardados)) {
                $guardadosIDs = array_keys($samplesGuardados);
                $query_args['post__not_in'] = array_merge(
                    $query_args['post__not_in'] ?? [], 
                    $guardadosIDs
                );
            }
        }

        // Filtrar para mostrar solo los que le gustan
        if (in_array('mostrarMeGustan', $filtrosUsuario)) {
            $userLikedPostIds = obtenerLikesDelUsuario($current_user_id);
            if (!empty($userLikedPostIds)) {
                $query_args['post__in'] = $userLikedPostIds;
            } else {
                $query_args['posts_per_page'] = 0;
            }
        }
    }

    // Aplicar el filtro original de `$filtro`
    $filtro = $args['filtro'] ?? 'nada';
    $meta_query_conditions = [
        'rolasEliminadas' => fn() => $query_args['post_status'] = 'pending_deletion',
        'rolasRechazadas' => fn() => $query_args['post_status'] = 'rejected',
        'rolasPendiente' => fn() => $query_args['post_status'] = 'pending',
        'likesRolas' => fn() => ($userLikedPostIds = obtenerLikesDelUsuario($current_user_id))
            ? $query_args['post__in'] = $userLikedPostIds
            : $query_args['posts_per_page'] = 0,
        'nada' => fn() => $query_args['post_status'] = 'publish',
        'colabs' => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'libres' => [
            ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!=']
        ],
        'momento' => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'sample' => [
            ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ],
        'sampleList' => ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
        'colab' => fn() => $query_args['post_status'] = 'publish',
        'colabPendiente' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = 'pending';
        },
    ];

    // Ejecutar el filtro
    if (isset($meta_query_conditions[$filtro])) {
        $result = $meta_query_conditions[$filtro];
        if (is_callable($result)) {
            $result();
        } else {
            $query_args['meta_query'][] = $result;
        }
    }

    // Definir autor si se proporciona el user_id
    if ($user_id !== null) {
        $query_args['author'] = $user_id;
    }

    return $query_args;
}

