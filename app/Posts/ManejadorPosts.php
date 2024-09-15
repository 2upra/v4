<?php

define('ENABLE_LOGS', true);

function mostrar_publicaciones_sociales($atts, $is_ajax = false, $paged = 1) {
    $log_file_path = '/var/www/wordpress/wp-content/themes/wanlog' . ($is_ajax ? 'Ajax' : '') . '.txt';
    $log = function($message) use ($log_file_path) {
        if (ENABLE_LOGS) error_log($message . "\n", 3, $log_file_path);
    };

    $log("---------------------------------------\nmostrar_publicaciones_sociales\nis_ajax: " . ($is_ajax ? 'true' : 'false') . ", paged: $paged\nDatos recibidos (atts): " . print_r($atts, true));

    $user_id = null;
    $filtro = $_POST['filtro'] ?? '';
    $identifier = $_POST['identifier'] ?? '';

    if ($is_ajax && isset($_POST['user_id'])) {
        $user_id = sanitize_text_field($_POST['user_id']);
    } else {
        $url_segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        $indices = ['perfil', 'music', 'author', 'sello'];
        foreach ($indices as $index) {
            $pos = array_search($index, $url_segments);
            if ($pos !== false) {
                if ($index === 'sello') {
                    $user_id = get_current_user_id();
                } elseif (isset($url_segments[$pos + 1])) {
                    $usuario = get_user_by('slug', $url_segments[$pos + 1]);
                    if ($usuario) $user_id = $usuario->ID;
                }
                break;
            }
        }
    }

    $log("Filtro: $filtro\nIdentifier: $identifier\nUser ID: $user_id");

    $current_user_id = get_current_user_id();
    $log("current_user_id: $current_user_id, user_id: $user_id");

    $args = shortcode_atts(['filtro' => '', 'tab_id' => ''], $atts);
    $log("shortcode_atts: " . print_r($args, true));

    $posts_per_page = isset($atts['posts']) ? intval($atts['posts']) : 12;

    $query_args = [
        'post_type' => 'social_post',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'meta_query' => !empty($identifier) ? [['key' => 'additional_search_data', 'value' => $identifier, 'compare' => 'LIKE']] : [],
        'meta_key' => '_post_puntuacion_final',
        'orderby' => ['meta_value_num' => 'DESC', 'date' => 'DESC'],
    ];

    $log("query_args: " . print_r($query_args, true));

    $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];

    $meta_query_conditions = [
        'siguiendo' => function() use ($current_user_id, &$query_args) {
            $query_args['author__in'] = array_filter((array) get_user_meta($current_user_id, 'siguiendo', true));
            return ['key' => 'rola', 'value' => '1', 'compare' => '!='];
        },
        'con_imagen_sin_audio' => [
            ['key' => 'post_audio', 'compare' => 'NOT EXISTS'],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'solo_colab' => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'rolastatus' => function() use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['publish', 'pending'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'nada' => function() use (&$query_args) {
            $query_args['post_status'] = 'publish';
            return [];
        },
        'rolasEliminadas' => function() use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['pending_deletion'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'rolasRechazadas' => function() use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['rejected'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
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
        'bloqueado' => ['key' => 'esExclusivo', 'value' => '1', 'compare' => '='],
        'sample' => ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
        'venta' => ['key' => 'post_price', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'],
        'rola' => function() use (&$query_args) {
            $query_args['post_status'] = 'publish';
            return [
                ['key' => 'rola', 'value' => '1', 'compare' => '='],
                ['key' => 'post_audio', 'compare' => 'EXISTS']
            ];
        },
        'momento' => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'presentacion' => ['key' => 'additional_search_data', 'value' => 'presentacion010101', 'compare' => 'LIKE'],
    ];

    if (isset($meta_query_conditions[$filtro])) {
        $condition = $meta_query_conditions[$filtro];
        $query_args['meta_query'][] = is_callable($condition) ? $condition() : $condition;
    }

    if ($user_id !== null) $query_args['author'] = $user_id;
    if (!empty($args['exclude'])) $query_args['post__not_in'] = $args['exclude'];

    ob_start();

    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];

        // Si no es una solicitud AJAX, imprime la apertura de la lista
        if (!wp_doing_ajax()) {
            $clase_extra = '';
            if ($filtro === 'rolasEliminadas' || $filtro === 'rolasRechazadas' || $filtro === 'rola' || $filtro === 'likes') {
                $clase_extra = 'clase-rolastatus';
            } else {
                $clase_extra = 'clase-' . $filtro;
            }

            echo '<ul class="social-post-list ' . $clase_extra . '" data-filtro="' . esc_attr($filtro) . '" data-tab-id="' . esc_attr($args['tab_id']) . '">';

        }

        while ($query->have_posts()) {
            $query->the_post();
            echo obtener_html_publicacion($filtro);
        }

        if (!wp_doing_ajax()) {
        }
    } else {
        echo nohayPost($filtro, $is_ajax);
    }
    wp_reset_postdata();

    if ($is_ajax) {
        echo ob_get_clean();
        die();
    } else {
        return ob_get_clean();
    }
}
add_shortcode('mostrar_publicaciones_sociales', 'mostrar_publicaciones_sociales');


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
    mostrar_publicaciones_sociales(
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
