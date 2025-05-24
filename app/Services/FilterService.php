<?php
// Refactor(Org): Función guardarFiltro() y su hook AJAX ya presentes en este archivo. No se realiza ninguna acción.

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

// Refactor(Org): Mover función obtenerFiltroActual() y su hook AJAX aquí desde filtroLogic.php
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

// Refactor(Org): Mover función restablecerFiltros() y su hook AJAX aquí desde filtroLogic.php
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

// Refactor(Org): Mover lógica de filtros de usuario (obtenerFiltrosTotal, guardarFiltroPost, obtenerFiltros) desde filtroLogic.php
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

// Refactor(Org): Funciones de filtro global movidas desde app/Content/Logic/filtroGlobal.php
function aplicarFiltroPorAutor($queryArgs, $userId, $filtro)
{
    $queryArgs['author'] = $userId;
    $metaQuery = $queryArgs['meta_query'] ?? [];

    if ($filtro === 'imagenesPerfil') {
        $metaQuery = array_merge($metaQuery, [
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
            ['key' => 'post_audio_lite', 'compare' => 'NOT EXISTS'],
        ]);
    } elseif ($filtro === 'tiendaPerfil') {
        $metaQuery = array_merge($metaQuery, [
            ['key' => 'tienda', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ]);
    }

    $queryArgs['meta_query'] = $metaQuery;
    return $queryArgs;
}

function aplicarFiltrosDeUsuario($queryArgs, $usu, $filtro)
{
    $filtrosUsuario = get_user_meta($usu, 'filtroPost', true);

    if (in_array($filtro, ['sampleList', 'notas', 'colecciones'])) {
        if ($filtro === 'sampleList' && is_array($filtrosUsuario) && in_array('misPost', $filtrosUsuario)) {
            $queryArgs['author'] = $usu;
        } elseif ($filtro === 'notas') {
            $queryArgs['author'] = $usu;
        } elseif ($filtro === 'colecciones' && is_array($filtrosUsuario) && in_array('misColecciones', $filtrosUsuario)) {
            $queryArgs['author'] = $usu;
        }
    }
    if (($filtro === 'tarea' || $filtro === 'tareaPrioridad') && is_array($filtrosUsuario)) {
        $queryArgs['author'] = $usu;

        // Initialize meta_query correctly
        if (!isset($queryArgs['meta_query']) || !is_array($queryArgs['meta_query'])) {
            $queryArgs['meta_query'] = [];
        }

        $task_specific_conditions = [];

        if (in_array('ocultarCompletadas', $filtrosUsuario)) {
            $task_specific_conditions[] = [
                'key' => 'estado',
                'value' => 'completada',
                'compare' => '!=',
            ];
        }

        if (in_array('mostrarHabitosHoy', $filtrosUsuario)) {
            $today = date('Y-m-d');
            $task_types_to_filter_by_date = ['habito', 'habito rigido'];
            $task_specific_conditions[] = [
                'relation' => 'OR',
                [
                    'key' => 'tipo',
                    'compare' => 'NOT EXISTS', // Task is not a habit (no 'tipo' field)
                ],
                [
                    'key' => 'tipo',
                    'value' => $task_types_to_filter_by_date, // Task type is set but is NOT one of the habit types
                    'compare' => 'NOT IN', 
                ],
                [
                    'relation' => 'AND', // Task type IS one of the habit types, and it's due
                    [
                        'key' => 'tipo',
                        'value' => $task_types_to_filter_by_date,
                        'compare' => 'IN',
                    ],
                    [
                        'key' => 'fechaProxima',
                        'value' => $today,
                        'compare' => '<=',
                        'type' => 'DATE',
                    ]
                ]
            ];
        }

        if (!empty($task_specific_conditions)) {
            // Check if there are pre-existing conditions in meta_query
            // A simple way to check is to see if meta_query is empty or only contains 'relation'
            $has_preexisting_conditions = false;
            if (!empty($queryArgs['meta_query'])) {
                foreach ($queryArgs['meta_query'] as $key => $value) {
                    if ($key !== 'relation') {
                        $has_preexisting_conditions = true;
                        break;
                    }
                }
            }

            if ($has_preexisting_conditions) {
                // If there are pre-existing conditions, ensure they are wrapped in an array if not already
                // and then add task_specific_conditions as another clause.
                // The top-level relation must be AND.
                
                // Store existing conditions
                $existing_mq = $queryArgs['meta_query'];
                
                // If existing_mq is a single condition (not an array of conditions or already having a relation key)
                // This is a simple check; more robust might be needed if structure is very varied.
                if (!isset($existing_mq[0]) && isset($existing_mq['key'])) {
                    $existing_mq = [$existing_mq];
                }

                $queryArgs['meta_query'] = ['relation' => 'AND'];
                
                // Add back existing conditions. If it was already an AND group, its conditions are added.
                // If it was a single condition or an OR group, it's added as one item.
                if (isset($existing_mq['relation']) && count($existing_mq) > 1) { // Already a group
                    foreach($existing_mq as $k_mq => $v_mq){
                        if($k_mq === 'relation') continue;
                        $queryArgs['meta_query'][] = $v_mq;
                    }
                } else { // Single condition or an OR group
                     $queryArgs['meta_query'][] = $existing_mq;
                }


                if (count($task_specific_conditions) > 1) {
                    $queryArgs['meta_query'][] = array_merge(['relation' => 'AND'], $task_specific_conditions);
                } else {
                    $queryArgs['meta_query'][] = $task_specific_conditions[0];
                }

            } else { // No pre-existing conditions, meta_query was empty or just had a relation
                if (count($task_specific_conditions) > 1) {
                    $queryArgs['meta_query'] = array_merge(['relation' => 'AND'], $task_specific_conditions);
                } elseif (!empty($task_specific_conditions)) { // Only one specific condition
                    $queryArgs['meta_query'] = $task_specific_conditions[0];
                }
            }
        }
    }

    return $queryArgs;
}

function aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario)
{
    $metaQueryConditions = obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario);

    if (isset($metaQueryConditions[$filtro])) {
        $result = $metaQueryConditions[$filtro];
        if (is_callable($result)) {
            $result($queryArgs);
        } else {
            $queryArgs['post_status'] = 'publish';
            $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], $result);
        }
    }

    return $queryArgs;
}

function obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario)
{
    return [
        'rolasEliminadas' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'pending_deletion';
        },
        'rolasRechazadas' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'rejected';
        },
        'rolasPendiente' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'pending';
        },
        'likesRolas' => function (&$queryArgs) use ($usuarioActual) {
            $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
            if ($userLikedPostIds) {
                $queryArgs['post__in'] = $userLikedPostIds;
            } else {
                $queryArgs['posts_per_page'] = 0;
            }
        },
        'nada' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'publish';
        },
        'colabs' => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'libres' => [
            ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!='],
        ],
        'momento' => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
        ],
        'sample' => function (&$queryArgs) use ($tipoUsuario) {
            if ($tipoUsuario === 'Fan') {
                $queryArgs['post_status'] = 'publish';
            } else {
                $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                    ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                    ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
                ]);
            }
        },
        'rolaListLike' => function (&$queryArgs) use ($usuarioActual) {
            $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
            if (!empty($userLikedPostIds)) {
                $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                    'relation' => 'AND',
                    [
                        'key'     => 'rola',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'post_audio_lite',
                        'compare' => 'EXISTS',
                    ],
                ]);
                $queryArgs['post__in'] = $userLikedPostIds;
            } else {
                $queryArgs['posts_per_page'] = 0;
            }
        },
        'sampleList' => [
            'relation' => 'AND',
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
            [
                'relation' => 'OR',
                ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                ['key' => 'tienda', 'value' => '1', 'compare' => '='],
            ],
        ],
        'colab' => function (&$queryArgs) {
            $queryArgs['post_status'] = 'publish';
        },
        'colabPendiente' => function (&$queryArgs) {
            $queryArgs['author'] = get_current_user_id();
            $queryArgs['post_status'] = 'pending';
        },
        'rola' => [
            ['key' => 'rola', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ],
    ];
}

function aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario = null)
{
    $log = "Inicio aplicarFiltroGlobal \n";
    $filtro = $args['filtro'] ?? 'nada';
    $log .= "Filtro recibido: $filtro \n";
    if (!empty($userId)) {
        $log .= "Aplicando filtro por autor, userId: $userId \n";
        $queryArgs = aplicarFiltroPorAutor($queryArgs, $userId, $filtro);
    } else {
        $log .= "Aplicando filtros de usuario, usuarioActual: $usuarioActual \n";
        $queryArgs = aplicarFiltrosDeUsuario($queryArgs, $usuarioActual, $filtro);
        $log .= "Aplicando condiciones de meta query, filtro: $filtro, tipoUsuario: $tipoUsuario \n";
        $queryArgs = aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario);
    }
    //guardarLog("aplicarFiltroGlobal, $log, queryArgs resultantes: " . print_r($queryArgs, true));
    return $queryArgs;
}

