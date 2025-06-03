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

    $user_id       = get_current_user_id();
    $filtro_tiempo = intval($_POST['filtroTiempo']);

    if (update_user_meta($user_id, 'filtroTiempo', $filtro_tiempo)) {
        wp_send_json_success([
            'message'      => 'Filtro guardado correctamente',
            'filtroTiempo' => $filtro_tiempo,
            'userId'       => $user_id
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

    $user_id         = get_current_user_id();
    $filtro_tiempo   = intval(get_user_meta($user_id, 'filtroTiempo', true) ?: 0);
    $nombres_filtros = ['Feed', 'Reciente', 'Semanal', 'Mensual'];
    $nombre_filtro   = $nombres_filtros[$filtro_tiempo] ?? 'Feed';

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

    $user_id       = get_current_user_id();
    $filtro_post   = get_user_meta($user_id, 'filtroPost', true) ?: '{}';
    $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true) ?: 0;

    wp_send_json_success([
        'filtroPost'   => $filtro_post,
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
    $metaQuery           = $queryArgs['meta_query'] ?? [];

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
    $filtUsu = get_user_meta($usu, 'filtroPost', true);

    if (in_array($filtro, ['sampleList', 'notas', 'colecciones'])) {
        if ($filtro === 'sampleList' && is_array($filtUsu) && in_array('misPost', $filtUsu)) {
            $queryArgs['author'] = $usu;
        } elseif ($filtro === 'notas') {
            $queryArgs['author'] = $usu;
        } elseif ($filtro === 'colecciones' && is_array($filtUsu) && in_array('misColecciones', $filtUsu)) {
            $queryArgs['author'] = $usu;
        }
    }

    if (($filtro === 'tarea' || $filtro === 'tareaPrioridad') && is_array($filtUsu)) {
        $apFiltTar = false;
        $nMetaQ    = [];

        if (in_array('ocultarCompletadas', $filtUsu)) {
            $nMetaQ[]  = [
                'key'     => 'estado',
                'value'   => 'completada',
                'compare' => '!=',
            ];
            $apFiltTar = true;
        }

        // Si el filtro está activo, debe MOSTRAR hábitos con fechaProxima <= hoy
        // y OCULTAR hábitos con fechaProxima > hoy.
        // Tu comentario indica: "el filtro esta activo y solo veo habitos para dias proximos"
        // lo que significa que actualmente la condición es fechaProxima > hoy.
        // Para invertirlo y mostrar los de hoy/pasados, cambiamos '>' a '<='.
        if (in_array('mostrarHabitosHoy', $filtUsu)) {
            $hoy    = date('Y-m-d');
            // Asegúrate que los valores en $tipHab coincidan con los almacenados en la BD para la meta_key 'tipo'.
            // Si almacenas números (ej: 2, 4), usa $tipHab = [2, 4];
            // Si almacenas strings (ej: 'habito'), usa $tipHab = ['habito', 'habito rigido'];
            $tipHab = ['habito', 'habito rigido'];

            $nMetaQ[]  = [
                'relation' => 'OR',
                [
                    'key'     => 'tipo',      // Corregido de 'tipoPost' si tu meta_key es 'tipo'
                    'value'   => $tipHab,
                    'compare' => 'NOT IN',
                ],
                [
                    'relation' => 'AND',
                    [
                        'key'     => 'tipo',  // Corregido de 'tipoPost'
                        'value'   => $tipHab,
                        'compare' => 'IN',
                    ],
                    [
                        'key'     => 'fechaProxima',
                        'value'   => $hoy,
                        'compare' => '<=',    // CAMBIO: de '>' a '<=' para mostrar hoy y pasados
                        'type'    => 'DATE',
                    ]
                ]
            ];
            $apFiltTar = true;
        }

        if ($apFiltTar) {
            $queryArgs['author'] = $usu;                             // Aplicar filtro de autor si cualquier filtro de tarea está activo
            if (!empty($nMetaQ)) {
                if (!isset($queryArgs['meta_query'])) {
                    $queryArgs['meta_query'] = [];
                } elseif (isset($queryArgs['meta_query']['key'])) {  // Si es una 'condición única', se envuelve
                    $queryArgs['meta_query'] = [$queryArgs['meta_query']];
                }
                $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'], $nMetaQ);

                // Si después de combinar, solo hay una condición en meta_query, la desanidamos
                // Esto es opcional y depende de si se esperan siempre múltiples condiciones o no.
                // WP_Query maneja bien [ [...condición...] ] si es solo una.
                // Si $nMetaQ tenía una sola condición y $queryArgs['meta_query'] estaba vacío,
                // el resultado es [ [...condición...] ], que es correcto.
                // Si $queryArgs['meta_query'] ya tenía una condición y $nMetaQ una,
                // el resultado es [ [cond_orig], [cond_nueva] ], también correcto.
            }
        }

        // Limpiar meta_query si está vacío (por si se inicializó pero no se usó)
        if (isset($queryArgs['meta_query']) && empty($queryArgs['meta_query'])) {
            unset($queryArgs['meta_query']);
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
            $queryArgs['meta_query']  = array_merge($queryArgs['meta_query'] ?? [], $result);
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
        'rolasPendiente'  => function (&$queryArgs) {
            $queryArgs['post_status'] = 'pending';
        },
        'likesRolas'      => function (&$queryArgs) use ($usuarioActual) {
            $userLikedPostIds = obtenerLikesDelUsuario($usuarioActual);
            if ($userLikedPostIds) {
                $queryArgs['post__in'] = $userLikedPostIds;
            } else {
                $queryArgs['posts_per_page'] = 0;
            }
        },
        'nada'            => function (&$queryArgs) {
            $queryArgs['post_status'] = 'publish';
        },
        'colabs'          => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'libres'          => [
            ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!='],
        ],
        'momento'         => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS'],
        ],
        'sample'          => function (&$queryArgs) use ($tipoUsuario) {
            if ($tipoUsuario === 'Fan') {
                $queryArgs['post_status'] = 'publish';
            } else {
                $queryArgs['meta_query'] = array_merge($queryArgs['meta_query'] ?? [], [
                    ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                    ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
                ]);
            }
        },
        'rolaListLike'    => function (&$queryArgs) use ($usuarioActual) {
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
                $queryArgs['post__in']   = $userLikedPostIds;
            } else {
                $queryArgs['posts_per_page'] = 0;
            }
        },
        'sampleList'      => [
            'relation' => 'AND',
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
            [
                'relation' => 'OR',
                ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
                ['key' => 'tienda', 'value' => '1', 'compare' => '='],
            ],
        ],
        'colab'           => function (&$queryArgs) {
            $queryArgs['post_status'] = 'publish';
        },
        'colabPendiente'  => function (&$queryArgs) {
            $queryArgs['author']      = get_current_user_id();
            $queryArgs['post_status'] = 'pending';
        },
        'rola'            => [
            ['key' => 'rola', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ],
    ];
}

function aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario = null)
{
    $log    = "Inicio aplicarFiltroGlobal \n";
    $filtro = $args['filtro'] ?? 'nada';
    $log   .= "Filtro recibido: $filtro \n";
    if (!empty($userId)) {
        $log      .= "Aplicando filtro por autor, userId: $userId \n";
        $queryArgs = aplicarFiltroPorAutor($queryArgs, $userId, $filtro);
    } else {
        $log      .= "Aplicando filtros de usuario, usuarioActual: $usuarioActual \n";
        $queryArgs = aplicarFiltrosDeUsuario($queryArgs, $usuarioActual, $filtro);
        $log      .= "Aplicando condiciones de meta query, filtro: $filtro, tipoUsuario: $tipoUsuario \n";
        $queryArgs = aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario);
    }
    // guardarLog("aplicarFiltroGlobal, $log, queryArgs resultantes: " . print_r($queryArgs, true));
    return $queryArgs;
}
