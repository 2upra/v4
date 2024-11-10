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



function construirQueryArgs($args, $paged, $current_user_id, $identifier, $is_admin, $posts, $filtroTiempo, $similar_to) {
    global $wpdb;
    $likes_table = $wpdb->prefix . 'post_likes';
    $query_args = [];

    postLog("Iniciando construirQueryArgs con filtroTiempo: $filtroTiempo");

    // Configuración base
    $query_args = [
        'post_type' => $args['post_type'],
        'posts_per_page' => $posts,
        'paged' => $paged,
        'ignore_sticky_posts' => true,
    ];

    // Manejar diferentes tipos de ordenamiento
    if ($args['post_type'] === 'social_post') {
        switch ($filtroTiempo) {
            case 1: // Posts recientes
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                postLog("Caso 1: Ordenando por fecha reciente");
                break;

            case 2: // Top semanal
            case 3: // Top mensual
                // Determinar el intervalo
                $interval = ($filtroTiempo === 2) ? '1 WEEK' : '1 MONTH';
                postLog("Caso $filtroTiempo: Usando intervalo de $interval");
                $sql = "
                    SELECT p.ID, 
                           COUNT(pl.post_id) as like_count 
                    FROM {$wpdb->posts} p 
                    LEFT JOIN {$likes_table} pl ON p.ID = pl.post_id 
                    WHERE p.post_type = 'social_post' 
                    AND p.post_status = 'publish'
                    AND pl.like_date >= DATE_SUB(NOW(), INTERVAL $interval)
                    GROUP BY p.ID
                    HAVING like_count > 0
                    ORDER BY like_count DESC, p.post_date DESC
                ";

                postLog("SQL Query: " . $sql);
                $posts_with_likes = $wpdb->get_results($sql, ARRAY_A);
                postLog("Resultados encontrados: " . count($posts_with_likes));
                
                if (!empty($posts_with_likes)) {
                    foreach ($posts_with_likes as $post) {
                        //postLog("Post ID: {$post['ID']}, Likes: {$post['like_count']}");
                    }
                    $post_ids = wp_list_pluck($posts_with_likes, 'ID');
                    $offset = ($paged - 1) * $posts;
                    $paged_post_ids = array_slice($post_ids, $offset, $posts);
                    if (!empty($paged_post_ids)) {
                        $query_args['post__in'] = $paged_post_ids;
                        $query_args['orderby'] = 'post__in';
                    }
                    
                    postLog("IDs de posts para esta página: " . implode(', ', $paged_post_ids));
                } else {
                    postLog("No se encontraron posts con likes en el período especificado");
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;

            default:
                postLog("Caso default: Obteniendo feed personalizado");
                $personalized_feed = obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);
                if (!empty($personalized_feed['post_ids'])) {
                    $query_args['post__in'] = $personalized_feed['post_ids'];
                    $query_args['orderby'] = 'post__in';
                    postLog("Feed personalizado IDs: " . implode(', ', $personalized_feed['post_ids']));
                }
                if (!empty($personalized_feed['post_not_in'])) {
                    $query_args['post__not_in'] = array_unique($personalized_feed['post_not_in']);
                    postLog("Posts excluidos: " . implode(', ', $personalized_feed['post_not_in']));
                }
                break;
        }
    }

    postLog("Query args finales: " . print_r($query_args, true));
    return $query_args;
}

//aqui hay un problema, cuando los filtros de top mensual o semanal estan activos y activo el de mostrarMeGustan, se dejan de ordenar de mayor like a menor like o tal vez no se que es lo que pasa exactamente, no debería aplicarFiltrosUsuario cambiar el orden de los post 

function aplicarFiltrosUsuario($query_args, $current_user_id) {
    // Obtener los filtros personalizados del usuario
    $filtrosUsuario = get_user_meta($current_user_id, 'filtroPost', true);

    // Aplicar filtros según la configuración del usuario en 'FiltroPost'
    if (!empty($filtrosUsuario)) {
        // Filtrar publicaciones ya descargadas
        if (in_array('ocultarDescargados', $filtrosUsuario)) {
            $descargasAnteriores = get_user_meta($current_user_id, 'descargas', true) ?: [];
            if (!empty($descargasAnteriores)) {
                // Agregar las publicaciones descargadas a `post__not_in` sin afectar `post__in`
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
                // Agregar las publicaciones guardadas a `post__not_in` sin afectar `post__in`
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
                // Si ya existen posts en 'post__in', hacer una intersección para conservar el orden original
                if (isset($query_args['post__in'])) {
                    $query_args['post__in'] = array_intersect($query_args['post__in'], $userLikedPostIds);
                } else {
                    $query_args['post__in'] = $userLikedPostIds;
                }
                
                // Si la intersección da como resultado un conjunto vacío, establecer `posts_per_page` a 0
                if (empty($query_args['post__in'])) {
                    $query_args['posts_per_page'] = 0;
                }
            } else {
                // Si el usuario no tiene posts con 'me gusta', establecer `posts_per_page` a 0
                $query_args['posts_per_page'] = 0;
            }
        }
    }

    return $query_args;
}


function aplicarFiltroGlobal($query_args, $args, $current_user_id) {
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

    return $query_args;
}

