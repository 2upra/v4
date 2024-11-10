<?

// Añadir al functions.php o archivo similar


function guardarFiltroPost() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
        return;
    }

    $filtros = json_decode(stripslashes($_POST['filtros']), true);
    $user_id = get_current_user_id();

    // Guardar los filtros en la meta del usuario
    $actualizado = update_user_meta($user_id, 'filtroPost', $filtros);

    if ($actualizado) {
        wp_send_json_success(['message' => 'Filtros guardados correctamente']);
    } else {
        wp_send_json_error('Error al guardar los filtros');
    }
}
add_action('wp_ajax_guardarFiltroPost', 'guardarFiltroPost');

function obtenerFiltros() {
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


//hay un pequeño desajuste que no se porque sucede, cuando el filtro de mostrar solo los post que me gusta esta activado al mismo tiempo que tol mensual


function construirQueryArgs($args, $paged, $current_user_id, $identifier, $is_admin, $posts, $filtroTiempo, $similar_to) {
    global $wpdb;
    $post_not_in = [];
    $query_args = [];

    if ($args['post_type'] === 'social_post') {
        // Determinar el tipo de consulta basada en filtroTiempo
        if ($filtroTiempo === 1) { // Post recientes
            $query_args = [
                'post_type' => $args['post_type'],
                'posts_per_page' => $posts,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC',
                'ignore_sticky_posts' => true,
            ];
        } elseif ($filtroTiempo === 2 || $filtroTiempo === 3) { // Top semanal o mensual
            $likes_table = $wpdb->prefix . 'post_likes';

            // Determinar el período de tiempo
            $time_condition = '';
            if ($filtroTiempo === 2) {
                $time_condition = "AND pl.like_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            } elseif ($filtroTiempo === 3) {
                $time_condition = "AND pl.like_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            }

            // Subconsulta para contar likes en el periodo especificado
            $posts_with_likes = $wpdb->get_results("
                SELECT p.ID, COUNT(pl.post_id) as like_count 
                FROM {$wpdb->posts} p 
                LEFT JOIN {$likes_table} pl ON p.ID = pl.post_id 
                WHERE p.post_type = 'social_post' 
                AND p.post_status = 'publish'
                {$time_condition}
                GROUP BY p.ID
                ORDER BY like_count DESC
                LIMIT " . ($posts * $paged),
                ARRAY_A
            );

            $post_ids = wp_list_pluck($posts_with_likes, 'ID');

            $query_args = [
                'post_type' => $args['post_type'],
                'posts_per_page' => $posts,
                'paged' => $paged,
                'post__in' => $post_ids,
                'orderby' => 'post__in', 
                'ignore_sticky_posts' => true,
            ];
        } else { // Filtro personalizado u otro
            $personalized_feed = obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);
            $post_ids = $personalized_feed['post_ids'];
            $post_not_in = $personalized_feed['post_not_in'];

            $query_args = [
                'post_type' => $args['post_type'],
                'posts_per_page' => $posts,
                'post__in' => $post_ids,
                'orderby' => 'post__in',
                'ignore_sticky_posts' => true,
            ];

            if (!empty($post_not_in)) {
                $query_args['post__not_in'] = array_unique($post_not_in);
            }
        }
    } else {
        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
        ];

        if (!empty($post_not_in)) {
            $query_args['post__not_in'] = array_unique($post_not_in);
        }
    }

    return $query_args;
}


function aplicarFiltros($query_args, $args, $user_id, $current_user_id)
{
    // Obtener los filtros personalizados del usuario
    $filtrosUsuario = get_user_meta($current_user_id, 'filtroPost', true);
    
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

