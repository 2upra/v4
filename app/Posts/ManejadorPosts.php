<?php

define('ENABLE_LOGS', true);

function publicaciones($args = [], $is_ajax = false, $paged = 1) {
    $log_file_path = '/var/www/wordpress/wp-content/themes/wanlog' . ($is_ajax ? 'Ajax' : '') . '.txt';
    
    $log = function($message) use ($log_file_path) {
        if (ENABLE_LOGS) error_log($message . "\n", 3, $log_file_path);
    };

    $log("---------------------------------------\npublicaciones\nis_ajax: " . ($is_ajax ? 'true' : 'false') . ", paged: $paged\nDatos recibidos (args): " . print_r($args, true));

    $user_id = obtenerUserId($is_ajax);
    $log("User ID: $user_id");

    $current_user_id = get_current_user_id();
    $log("current_user_id: $current_user_id, user_id: $user_id");

    $defaults = [
        'filtro' => '',
        'tab_id' => '',
        'posts' => 12,
        'exclude' => [],
    ];

    $args = array_merge($defaults, $args);
    $log("args (después de merge): " . print_r($args, true));

    $query_args = configuracionPost($args, $paged, $user_id, $current_user_id);
    $log("query_args: " . print_r($query_args, true));

    $output = procesarPublicaciones($query_args, $args, $is_ajax);
    $log("Output generado");

    if ($is_ajax) {
        echo $output;
        die();
    } else {
        return $output;
    }
}

function configuracionPost($args, $paged, $user_id, $current_user_id) {
    $posts = $args['posts'];

    $query_args = [
        'post_type' => 'social_post',
        'posts_per_page' => $posts,
        'paged' => $paged,
        'meta_query' => [],
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];

    $meta_query_conditions = [
        'siguiendo' => function() use ($current_user_id, &$query_args) {
            $query_args['author__in'] = array_filter((array) get_user_meta($current_user_id, 'siguiendo', true));
            return ['key' => 'rola', 'value' => '1', 'compare' => '!='];
        },
        'nada' => function() use (&$query_args) {
            $query_args['post_status'] = 'publish';
            return [];
        },
        'no_bloqueado' => [
            ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!=']
        ],
        'likes' => function() use ($current_user_id, &$query_args) {
            $user_liked_post_ids = get_user_liked_post_ids($current_user_id);
            if (empty($user_liked_post_ids)) {
                $query_args['posts_per_page'] = 0;
                return null;
            }
            $query_args['post__in'] = $user_liked_post_ids;
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'rola' => function() use (&$query_args) {
            $query_args['post_status'] = 'publish';
            return [
                ['key' => 'rola', 'value' => '1', 'compare' => '='],
                ['key' => 'post_audio', 'compare' => 'EXISTS']
            ];
        },
    ];

    if (isset($meta_query_conditions[$filtro])) {
        $condition = $meta_query_conditions[$filtro];
        $query_args['meta_query'][] = is_callable($condition) ? $condition() : $condition;
    }

    if ($user_id !== null) $query_args['author'] = $user_id;
    if (!empty($args['exclude'])) $query_args['post__not_in'] = $args['exclude'];

    return $query_args;
}

function procesarPublicaciones($query_args, $args, $is_ajax) {
    ob_start();

    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];

        if (!wp_doing_ajax()) {
            $clase_extra = 'clase-' . $filtro;
            if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
                $clase_extra = 'clase-rolastatus';
            }

            echo '<ul class="social-post-list ' . esc_attr($clase_extra) . '" data-filtro="' . esc_attr($filtro) . '" data-tab-id="' . esc_attr($args['tab_id']) . '">';
        }

        while ($query->have_posts()) {
            $query->the_post();
            echo obtener_html_publicacion($filtro);
        }

        if (!wp_doing_ajax()) {
            echo '</ul>';
        }
    } else {
        echo nohayPost($filtro, $is_ajax);
    }

    wp_reset_postdata();

    return ob_get_clean();
}

function obtenerUserId($is_ajax) {
    if ($is_ajax && isset($_POST['user_id'])) {
        return sanitize_text_field($_POST['user_id']);
    }

    $url_segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    $indices = ['perfil', 'music', 'author', 'sello'];
    foreach ($indices as $index) {
        $pos = array_search($index, $url_segments);
        if ($pos !== false) {
            if ($index === 'sello') {
                return get_current_user_id();
            } elseif (isset($url_segments[$pos + 1])) {
                $usuario = get_user_by('slug', $url_segments[$pos + 1]);
                if ($usuario) return $usuario->ID;
            }
            break;
        }
    }

    return null;
}


function cargar_mas_publicaciones_ajax()
{
    // Determinar la ruta del archivo de log
    $log_file_path = '/var/www/wordpress/wp-content/themes/wanlogAjax.txt';

    // Obtener los parámetros de la solicitud POST
    $paged = isset($_POST['paged']) ? (int) $_POST['paged'] : 1;
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $data_identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : ''; // Obtener data-identifier
    $tab_id = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';

    // Verificar si 'cargadas' es un array
    $publicacionesCargadas = isset($_POST['cargadas']) && is_array($_POST['cargadas']) 
        ? array_map('intval', $_POST['cargadas']) 
        : array();

    // Registrar logs
    if (defined('ENABLE_LOGS') && ENABLE_LOGS) {
        error_log("---------------------------------------\n", 3, $log_file_path);
        error_log("cargar_mas_publicaciones_ajax\n", 3, $log_file_path);
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

add_action('wp_ajax_cargar_mas_publicaciones', 'cargar_mas_publicaciones_ajax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'cargar_mas_publicaciones_ajax');

function enqueue_diferido_post_script()
{
    wp_enqueue_script('diferido-post', get_template_directory_uri() . '/js/diferido-post.js', array('jquery'), '3.0.34', true);

    wp_localize_script(
        'diferido-post',
        'ajax_params',
        array(
            'ajax_url' => admin_url('admin-ajax.php')
        )
    );
}
add_action('wp_enqueue_scripts', 'enqueue_diferido_post_script'); 
