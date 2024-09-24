<?php



function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    // Configurar valores predeterminados
    $defaults = [
        'filtro'     => '',
        'tab_id'     => '',
        'posts'      => 12,
        'exclude'    => [],
        'identifier' => '',
        'user_id'    => null,
    ];

    // Fusionar argumentos con valores predeterminados
    $args = array_merge($defaults, $args);

    // Obtener datos adicionales en solicitudes AJAX
    if ($is_ajax) {
        $args['identifier'] = $_POST['identifier'] ?? $args['identifier'];
        $args['user_id']    = $_POST['user_id'] ?? $args['user_id'];
        guardarLog("Publicaciones AJAX: " . print_r($args, true));
    }

    $user_id         = obtenerUserId($args);
    $current_user_id = get_current_user_id();

    $query_args = configuracionQueryArgs($args, $paged, $user_id, $current_user_id);
    $output     = procesarPublicaciones($query_args, $args);

    if ($is_ajax) {
        echo $output;
        die();
    }

    return $output;
}

function obtenerUserId($args)
{
    if (!empty($args['user_id'])) {
        return sanitize_text_field($args['user_id']);
    }

    $url_segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    $indices      = ['perfil', 'music', 'author', 'sello'];

    foreach ($indices as $index) {
        $pos = array_search($index, $url_segments);
        if ($pos !== false) {
            if ($index === 'sello') {
                return get_current_user_id();
            }
            if (isset($url_segments[$pos + 1])) {
                $usuario = get_user_by('slug', $url_segments[$pos + 1]);
                if ($usuario) {
                    return $usuario->ID;
                }
            }
            break;
        }
    }

    return null;
}

function configuracionQueryArgs($args, $paged, $user_id, $current_user_id)
{
    $identifier     = $args['identifier'];
    $posts_per_page = $args['posts'];

    $posts_personalizados = calcularFeedPersonalizado($current_user_id);
    $post_ids             = array_keys($posts_personalizados);

    // Control de paginación adecuado
    $offset   = ($paged - 1) * $posts_per_page;
    $post_ids = array_slice($post_ids, $offset, $posts_per_page);

    $query_args = [
        'post_type'      => 'social_post',
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'post__in'       => $post_ids,
        'orderby'        => 'post__in',
        'meta_query'     => [],
    ];

    if (!empty($identifier)) {
        $query_args['meta_query'][] = [
            'key'     => 'datosAlgoritmo',
            'value'   => $identifier,
            'compare' => 'LIKE',
        ];
    }

    if (!empty($args['exclude'])) {
        $query_args['post__not_in'] = $args['exclude'];
    }

    // Aplicar filtros adicionales
    $query_args = aplicarFiltros($query_args, $args, $user_id, $current_user_id);

    return $query_args;
}

function procesarPublicaciones($query_args, $args)
{
    ob_start();

    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        $filtro     = $args['identifier'] ?: $args['filtro'];
        $clase_extra = 'clase-' . esc_attr($filtro);

        // Ajustamos la clase para ciertos filtros
        if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
            $clase_extra = 'clase-rolastatus';
        }

        if (!wp_doing_ajax()) {
            echo '<ul class="social-post-list ' . $clase_extra . '" data-filtro="' . esc_attr($filtro) . '" data-tab-id="' . esc_attr($args['tab_id']) . '">';
        }

        while ($query->have_posts()) {
            $query->the_post();
            echo htmlPost($filtro);
        }

        if (!wp_doing_ajax()) {
            echo '</ul>';
        }
    } else {
        echo nohayPost($filtro, wp_doing_ajax());
    }

    wp_reset_postdata();

    return ob_get_clean();
}


function publicacionAjax()
{
    // Determinar la ruta del archivo de log
    $log_file_path = '/var/www/wordpress/wp-content/themes/wanlogAjax.txt';

    // Obtener los parámetros de la solicitud POST
    $paged = isset($_POST['paged']) ? (int) $_POST['paged'] : 1;
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $data_identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : ''; 
    $tab_id = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';

    // Verificar si 'cargadas' es un array
    $publicacionesCargadas = isset($_POST['cargadas']) && is_array($_POST['cargadas'])
        ? array_map('intval', $_POST['cargadas'])
        : array();

    // Registrar logs
    if (defined('ENABLE_LOGS') && ENABLE_LOGS) {
        error_log("---------------------------------------\n", 3, $log_file_path);
        error_log("publicacionAjax\n", 3, $log_file_path);
        error_log("paged: $paged\n", 3, $log_file_path);
        error_log("search_term: $search_term\n", 3, $log_file_path);
        error_log("filtro: $filtro\n", 3, $log_file_path);
        error_log("data_identifier: $data_identifier\n", 3, $log_file_path);
        error_log("tab_id: $tab_id\n", 3, $log_file_path);
        error_log("user_id: $user_id\n", 3, $log_file_path);
        error_log("publicacionesCargadas: " . implode(',', $publicacionesCargadas) . "\n", 3, $log_file_path);
    }

    // Llamar a la función para mostrar las publicaciones sociales
    publicaciones(
        array(
            'filtro' => $filtro,
            'tab_id' => $tab_id,
            'user_id' => $user_id,
            'identifier' => $data_identifier,
            'exclude' => $publicacionesCargadas
        ),
        true,
        $paged
    );
}

add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');

